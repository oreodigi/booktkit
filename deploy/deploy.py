#!/usr/bin/env python3
"""Private cPanel deployment runner; Python 3.6+, no production secrets in Git."""
import datetime
import fcntl
import hashlib
import io
import json
import os
from pathlib import Path, PurePosixPath
import shutil
import subprocess
import sys
import tarfile
import tempfile
import urllib.request

HOME = Path('/home/booktkit')
LIVE = HOME / 'public_html'
CONTROL = HOME / 'booktkit-deploy'
REPO = HOME / 'repositories/booktkit.git'
PHP = '/opt/cpanel/ea-php83/root/usr/bin/php'
STATE = CONTROL / 'state.json'
PATCHES = {
    'vendor/kreativdev/installer/src/KdInstallerServiceProvider.php':
        'deploy/vendor-patches/KdInstallerServiceProvider.php',
    'vendor/rachidlaasri/laravel-installer/src/Providers/LaravelInstallerServiceProvider.php':
        'deploy/vendor-patches/LaravelInstallerServiceProvider.php',
}


def run(args, cwd=None):
    return subprocess.check_output(args, cwd=str(cwd or REPO),
                                   stderr=subprocess.STDOUT).decode().strip()


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest() if path.is_file() else None


def write_json(path, value):
    temp = path.with_name(path.name + '.tmp')
    temp.write_text(json.dumps(value, indent=2) + '\n')
    os.chmod(str(temp), 0o600)
    os.replace(str(temp), str(path))


def safe_target(name):
    parts = PurePosixPath(name).parts
    if not parts or name.startswith('/') or '..' in parts or '\\' in name:
        raise RuntimeError('Invalid deployment path: ' + name)
    target = LIVE / name
    if LIVE.resolve() not in target.resolve().parents:
        raise RuntimeError('Deployment path escapes website: ' + name)
    return target


def allowed(name):
    forbidden = ('storage/', 'vendor/', 'bootstrap/cache/', 'public/storage/',
                 'public/assets/file/', 'public/assets/admin/file/',
                 'public/assets/admin/img/')
    if name.startswith(forbidden) or name == '.env' or name.startswith('.env.') and name != '.env.example':
        return False
    if name.startswith('public/assets/img/') and not name.startswith('public/assets/img/mobile-interface/booktkit-'):
        return False
    if name.endswith(('.jks', '.keystore', '.pem', '.p12')):
        return False
    return True


def replace(source, target):
    new_directories = []
    parent = target.parent
    while not parent.exists():
        new_directories.append(parent)
        parent = parent.parent
    target.parent.mkdir(parents=True, exist_ok=True)
    for parent in new_directories:
        os.chmod(str(parent), 0o755)
    mode = target.stat().st_mode & 0o777 if target.exists() else 0o644
    handle, temporary = tempfile.mkstemp(prefix='.booktkit-deploy-', dir=str(target.parent))
    os.close(handle)
    try:
        shutil.copyfile(str(source), temporary)
        os.chmod(temporary, mode)
        os.replace(temporary, str(target))
    finally:
        if os.path.exists(temporary):
            os.unlink(temporary)


def clear_caches():
    for command in ('config:clear', 'route:clear', 'view:clear'):
        run([PHP, 'artisan', command], LIVE)


def health(commit):
    result = {}
    for path in ('/', '/events', '/organizer/login', '/api/get-basic'):
        url = 'https://booktkit.com' + path + '?booktkit_deploy=' + commit[:12]
        request = urllib.request.Request(url, headers={'User-Agent': 'Booktkit-Deploy/1.0', 'Cache-Control': 'no-cache'})
        with urllib.request.urlopen(request, timeout=20) as response:
            body = response.read()
            if response.status != 200 or not body:
                raise RuntimeError('Health check failed: ' + path)
            if path.startswith('/api/'):
                json.loads(body.decode())
            result[path] = response.status
    return result


def deploy():
    if (CONTROL / 'PAUSED').exists():
        return
    run(['git', 'fetch', '--quiet', 'origin', 'main'])
    commit = run(['git', 'rev-parse', 'FETCH_HEAD'])
    previous = json.loads(STATE.read_text()) if STATE.exists() else {'commit': None, 'files': {}}
    if previous['commit'] == commit:
        return
    if previous['commit']:
        run(['git', 'merge-base', '--is-ancestor', previous['commit'], commit])
    archive = subprocess.check_output(['git', 'archive', commit, 'source/website', 'deploy/vendor-patches'], cwd=str(REPO))
    with tempfile.TemporaryDirectory(prefix='stage-', dir=str(CONTROL)) as temporary:
        stage = Path(temporary)
        files = {}
        with tarfile.open(fileobj=io.BytesIO(archive)) as bundle:
            for member in bundle.getmembers():
                if member.isdir():
                    continue
                if not member.isfile():
                    raise RuntimeError('Links are not deployable: ' + member.name)
                if member.name.startswith('source/website/'):
                    name = member.name[len('source/website/'):]
                    if not allowed(name):
                        raise RuntimeError('Protected runtime file tracked: ' + name)
                else:
                    matches = [name for name, source in PATCHES.items() if source == member.name]
                    if not matches:
                        raise RuntimeError('Unexpected patch: ' + member.name)
                    name = matches[0]
                safe_target(name)
                target = stage / name
                target.parent.mkdir(parents=True, exist_ok=True)
                target.write_bytes(bundle.extractfile(member).read())
                files[name] = digest(target)
        for name in ('composer.lock', 'public/pgw/composer.lock'):
            if (stage / name).exists() and digest(stage / name) != digest(LIVE / name):
                raise RuntimeError('Dependency lock changed; install and validate dependencies before deploying: ' + name)
        changed = [name for name, sha in files.items() if digest(safe_target(name)) != sha]
        removed = [name for name in previous['files'] if name not in files]
        for name in changed + removed:
            if name in previous['files'] and digest(safe_target(name)) != previous['files'][name]:
                raise RuntimeError('Live edit detected; import into Git first: ' + name)
        for name in files:
            if name.endswith('.php') and not name.endswith('.blade.php'):
                run([PHP, '-l', str(stage / name)])
        if '--check' in sys.argv:
            print(json.dumps({'commit': commit, 'changed': changed, 'removed': removed, 'php_lint': 'passed'}))
            return
        stamp = datetime.datetime.utcnow().strftime('%Y%m%dT%H%M%SZ')
        backup = HOME / 'booktkit-backups/git-deploy' / (stamp + '-' + commit[:12])
        backup.mkdir(parents=True, mode=0o700)
        existed = {}
        for name in changed + removed:
            target = safe_target(name)
            existed[name] = target.is_file()
            if target.is_file():
                saved = backup / 'files' / name
                saved.parent.mkdir(parents=True, exist_ok=True)
                shutil.copy2(str(target), str(saved))
        write_json(backup / 'manifest.json', {'before': previous, 'after': commit, 'existed': existed})
        try:
            for name in changed:
                replace(stage / name, safe_target(name))
            for name in removed:
                safe_target(name).unlink()
            clear_caches()
            checks = health(commit)
            for name, expected in files.items():
                if digest(safe_target(name)) != expected:
                    raise RuntimeError('Post-deployment file mismatch: ' + name)
            write_json(STATE, {'commit': commit, 'files': files, 'deployed_at': stamp, 'checks': checks, 'backup': str(backup)})
            if (CONTROL / 'failure.json').exists():
                (CONTROL / 'failure.json').unlink()
            print('Deployed ' + commit + ' (' + str(len(changed)) + ' changed files)')
        except Exception:
            (CONTROL / 'PAUSED').write_text('Deployment failed during activation; review failure.json before resuming.\n')
            for name, was_present in existed.items():
                target = safe_target(name)
                if was_present:
                    replace(backup / 'files' / name, target)
                elif target.exists():
                    target.unlink()
            clear_caches()
            raise


if __name__ == '__main__':
    os.umask(0o077)
    CONTROL.mkdir(parents=True, exist_ok=True)
    with open(str(CONTROL / 'deploy.lock'), 'a') as lock:
        try:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            sys.exit(0)
        try:
            deploy()
        except Exception as error:
            write_json(CONTROL / 'failure.json', {'at': datetime.datetime.utcnow().isoformat() + 'Z', 'error': str(error)})
            print('Deployment stopped: ' + str(error), file=sys.stderr)
            sys.exit(1)
