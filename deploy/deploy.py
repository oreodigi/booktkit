#!/usr/bin/env python3
"""BookTKIT account-scoped deployment. Python 3.6+, never prints credentials."""
import argparse
import base64
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
PHP = '/opt/cpanel/ea-php83/root/usr/bin/php'
REPO = HOME / 'deploy-repository.git'
PATCHES = {
    'vendor/kreativdev/installer/src/KdInstallerServiceProvider.php': 'deploy/vendor-patches/KdInstallerServiceProvider.php',
    'vendor/rachidlaasri/laravel-installer/src/Providers/LaravelInstallerServiceProvider.php': 'deploy/vendor-patches/LaravelInstallerServiceProvider.php',
}


def run(args, cwd=None, timeout=120):
    proc = subprocess.run(args, cwd=str(cwd or REPO), stdout=subprocess.PIPE,
                          stderr=subprocess.PIPE, universal_newlines=True, timeout=timeout)
    if proc.returncode:
        # Command arguments/output can contain DB credentials or SQL bindings.
        raise RuntimeError('Command failed: {} (exit {})'.format(Path(args[0]).name, proc.returncode))
    return proc.stdout.strip()


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest() if path.is_file() else None


def write_json(path, value):
    temporary = path.with_name(path.name + '.tmp')
    temporary.write_text(json.dumps(value, indent=2) + '\n')
    temporary.chmod(0o600)
    os.replace(str(temporary), str(path))


def allowed(name):
    p = PurePosixPath(name)
    if p.is_absolute() or '..' in p.parts or '\\' in name:
        return False
    if name == '.env' or (name.startswith('.env.') and name != '.env.example'):
        return False
    if name.startswith(('storage/', 'vendor/', 'bootstrap/cache/', 'public/storage/',
                        'public/assets/file/', 'public/assets/admin/file/', 'public/assets/admin/img/')):
        return False
    if name.endswith(('.jks', '.keystore', '.pem', '.p12')):
        return False
    return True


def replace(source, target):
    missing = []
    parent = target.parent
    while not parent.exists():
        missing.append(parent); parent = parent.parent
    target.parent.mkdir(parents=True, exist_ok=True)
    for parent in missing: parent.chmod(0o755)
    handle, tmp = tempfile.mkstemp(prefix='.booktkit-', dir=str(target.parent))
    os.close(handle)
    try:
        shutil.copyfile(str(source), tmp)
        os.chmod(tmp, 0o644)
        os.replace(tmp, str(target))
    finally:
        if os.path.exists(tmp): os.unlink(tmp)


class Deployment:
    def __init__(self, args):
        self.args = args
        self.live = HOME / ('staging' if args.target == 'staging' else 'public_html')
        self.control = HOME / ('booktkit-staging-deploy' if args.target == 'staging' else 'booktkit-deploy')
        self.url = 'https://test.booktkit.com' if args.target == 'staging' else 'https://booktkit.com'
        self.state = self.control / 'state.json'
        self.control.mkdir(mode=0o700, exist_ok=True)

    def target(self, name):
        target = self.live / name
        if self.live.resolve() not in target.resolve().parents:
            raise RuntimeError('Deployment path escapes target')
        return target

    def caches(self):
        for command in ('config:clear', 'route:clear', 'view:clear'):
            run([PHP, 'artisan', command, '--no-ansi'], self.live)

    def database_backup(self, backup):
        code = "require 'vendor/autoload.php'; echo json_encode(Dotenv\\Dotenv::parse(file_get_contents('.env')));"
        env = json.loads(run([PHP, '-r', code], self.live))
        expected = 'booktkit_stage' if self.args.target == 'staging' else 'booktkit_ems'
        if env.get('DB_DATABASE') != expected:
            raise RuntimeError('Unexpected target database; refusing migration')
        with tempfile.NamedTemporaryFile(mode='w', prefix='mysql-', dir=str(self.control)) as cfg:
            cfg.write('[client]\n')
            for key, value in [('host', env.get('DB_HOST', 'localhost')), ('user', env['DB_USERNAME']), ('password', env['DB_PASSWORD'])]:
                cfg.write(key + '=' + json.dumps(value) + '\n')
            cfg.flush()
            dump = backup / 'database.sql'
            with dump.open('wb') as out:
                proc = subprocess.run(['mysqldump', '--defaults-extra-file='+cfg.name, '--single-transaction', '--hex-blob', expected],
                                      stdout=out, stderr=subprocess.PIPE, timeout=180)
            if proc.returncode or not dump.stat().st_size:
                raise RuntimeError('Database backup failed; migration not attempted')
        dump.chmod(0o600)
        return env

    def restore_database(self, backup, env):
        with tempfile.NamedTemporaryFile(mode='w', prefix='mysql-', dir=str(self.control)) as cfg:
            cfg.write('[client]\n')
            for key,value in [('host',env.get('DB_HOST','localhost')),('user',env['DB_USERNAME']),('password',env['DB_PASSWORD'])]:
                cfg.write(key+'='+json.dumps(value)+'\n')
            cfg.flush()
            with (backup/'database.sql').open('rb') as data:
                proc=subprocess.run(['mysql','--defaults-extra-file='+cfg.name,env['DB_DATABASE']],stdin=data,stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=180)
                if proc.returncode: raise RuntimeError('Database restore failed; keep environment paused and in maintenance')

    def health(self, commit):
        headers = {'User-Agent':'BookTKIT-Deploy/2.0','Cache-Control':'no-cache'}
        if self.args.target == 'staging':
            credentials = json.loads((HOME/'qa-isolation/test-credentials.json').read_text())
            pair=credentials['BOOKTKIT_STAGING_HTTP_USERNAME']+':'+credentials['BOOKTKIT_STAGING_HTTP_PASSWORD']
            headers['Authorization']='Basic '+base64.b64encode(pair.encode()).decode()
        checks={}
        for path in ('/', '/events', '/organizer/login', '/api/get-basic', '/__deployment.json'):
            with urllib.request.urlopen(urllib.request.Request(self.url+path,headers=headers),timeout=20) as response:
                body=response.read()
                if response.status!=200 or not body: raise RuntimeError('Health check failed: '+path)
                if path=='/__deployment.json' and json.loads(body)['commit']!=commit:
                    raise RuntimeError('Served commit does not match deployment')
                checks[path]=response.status
        return checks

    def execute(self):
        if (self.control/'PAUSED').exists():
            print('Deployment paused: '+self.args.target); return
        ref=self.args.branch
        if not ref or ref.startswith('-') or any(x in ref for x in ['..','~','^',':','\\']):
            raise RuntimeError('Invalid branch')
        run(['git','fetch','--quiet','origin','refs/heads/'+ref],timeout=90)
        commit=run(['git','rev-parse','FETCH_HEAD'])
        previous=json.loads(self.state.read_text()) if self.state.exists() else {'commit':None,'files':{}}
        if previous['commit']==commit and not self.args.migrate and not self.args.adopt:
            print('Already deployed '+commit); return
        if previous['commit']:
            run(['git','merge-base','--is-ancestor',previous['commit'],commit])
        archive=subprocess.check_output(['git','archive',commit,'source/website','deploy'],cwd=str(REPO),timeout=60)
        with tempfile.TemporaryDirectory(prefix='stage-',dir=str(self.control)) as tmp:
            stage=Path(tmp);files={}
            with tarfile.open(fileobj=io.BytesIO(archive)) as bundle:
                for member in bundle.getmembers():
                    if member.isdir(): continue
                    if not member.isfile(): raise RuntimeError('Non-file entry rejected')
                    name=None
                    if member.name.startswith('source/website/'):
                        name=member.name[len('source/website/'):]
                        if name.endswith('/.gitignore'): continue
                        if not allowed(name): raise RuntimeError('Protected runtime path tracked: '+name)
                        if self.args.target=='staging' and name=='.htaccess':continue
                    elif member.name=='deploy/staging.htaccess':
                        if self.args.target=='staging': name='.htaccess'
                    else:
                        name=next((n for n,p in PATCHES.items() if p==member.name),None)
                    if not name:continue
                    self.target(name);dest=stage/name;dest.parent.mkdir(parents=True,exist_ok=True)
                    dest.write_bytes(bundle.extractfile(member).read());files[name]=digest(dest)
            required={'artisan','public/index.php','server.php','composer.lock','public/pgw/composer.lock','app/Providers/AppServiceProvider.php'}|set(PATCHES)
            if required-set(files):raise RuntimeError('Required deployment files missing')
            for name in ('composer.lock','public/pgw/composer.lock'):
                if digest(stage/name)!=digest(self.live/name):raise RuntimeError('Dependency lock mismatch; install and verify dependencies first')
            changed=[name for name,sha in files.items() if digest(self.target(name))!=sha]
            removed=[name for name in previous['files'] if name not in files]
            for name in changed+removed:
                if name in previous['files'] and digest(self.target(name))!=previous['files'][name]:
                    raise RuntimeError('Live edit detected; reconcile into Git: '+name)
            if self.args.adopt:
                if changed or removed:raise RuntimeError('Cannot adopt: live files differ from commit')
                write_json(self.state,{'target':self.args.target,'commit':commit,'files':files,'adopted_at':datetime.datetime.utcnow().isoformat()+'Z'})
                failure=self.control/'failure.json'
                if failure.exists():failure.unlink()
                print('Adopted verified baseline '+commit);return
            for name in changed:
                if name.endswith('.php') and not name.endswith('.blade.php'):run([PHP,'-l',str(stage/name)])
            if self.args.check:
                print(json.dumps({'target':self.args.target,'commit':commit,'changed':len(changed),'removed':len(removed),'php_lint':'passed'}));return
            stamp=datetime.datetime.utcnow().strftime('%Y%m%dT%H%M%SZ')
            backup=HOME/'booktkit-backups/git-deploy'/self.args.target/(stamp+'-'+commit[:12]);backup.mkdir(parents=True,mode=0o700)
            existed={}
            for name in changed+removed+['public/__deployment.json']:
                path=self.target(name);existed[name]=path.is_file()
                if path.is_file():
                    dest=backup/'files'/name;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(str(path),str(dest))
            write_json(backup/'manifest.json',{'before':previous,'after':commit,'existed':existed,'target':self.args.target})
            migrate=self.args.target=='staging' or self.args.migrate
            env=None;maintenance=False;migration={'requested':migrate,'status':'not_requested'}
            try:
                if migrate:
                    run([PHP,'artisan','down','--retry=60','--no-ansi'],self.live);maintenance=True
                    env=self.database_backup(backup)
                for name in changed:replace(stage/name,self.target(name))
                for name in removed:self.target(name).unlink()
                self.caches()
                if migrate:
                    run([PHP,'artisan','migrate','--force','--no-ansi'],self.live,180)
                    migration={'requested':True,'status':'passed','database_backup':str(backup/'database.sql')}
                release=stage/'release.json';release.write_text(json.dumps({'commit':commit,'target':self.args.target,'deployed_at':stamp}))
                replace(release,self.live/'public/__deployment.json')
                if maintenance:run([PHP,'artisan','up','--no-ansi'],self.live);maintenance=False
                checks=self.health(commit)
                for name,sha in files.items():
                    if digest(self.target(name))!=sha:raise RuntimeError('Post-deploy file mismatch: '+name)
                write_json(self.state,{'target':self.args.target,'commit':commit,'files':files,'deployed_at':stamp,'checks':checks,'backup':str(backup),'migration':migration})
                failure=self.control/'failure.json'
                if failure.exists():failure.unlink()
                print('Deployed {} target={} changed={} migrations={}'.format(commit,self.args.target,len(changed),migration['status']))
            except Exception:
                (self.control/'PAUSED').write_text('Deployment failed; review failure.json before resuming.\n')
                if env and self.args.target == 'production' and not maintenance:
                    run([PHP,'artisan','down','--retry=60','--no-ansi'],self.live)
                    raise RuntimeError('Post-migration production health failed: paused in maintenance; preserve DB for reviewed recovery')
                # Restore the file set and DB together. Keep paused if any restoration fails.
                for name,present in existed.items():
                    path=self.target(name)
                    if present:replace(backup/'files'/name,path)
                    elif path.exists():path.unlink()
                if env:self.restore_database(backup,env)
                self.caches()
                if maintenance:run([PHP,'artisan','up','--no-ansi'],self.live)
                raise


def main():
    parser=argparse.ArgumentParser()
    parser.add_argument('--target',choices=['production','staging'],required=True)
    parser.add_argument('--branch',default=None)
    parser.add_argument('--migrate',action='store_true',help='Explicit production migration approval; staging always migrates')
    parser.add_argument('--check',action='store_true')
    parser.add_argument('--adopt',action='store_true',help='Adopt only an exactly matching live baseline')
    args=parser.parse_args()
    expected_branch = 'staging' if args.target == 'staging' else 'main'
    args.branch = args.branch or expected_branch
    if args.branch != expected_branch: parser.error(args.target + ' deploys ' + expected_branch + ' only')
    os.umask(0o077)
    os.environ['GIT_SSH_COMMAND']='ssh -i /home/booktkit/.ssh/booktkit_github -o IdentitiesOnly=yes -o StrictHostKeyChecking=yes -o UserKnownHostsFile=/home/booktkit/.ssh/known_hosts_booktkit'
    deployment=Deployment(args)
    # Shared repository lock prevents concurrent FETCH_HEAD races between targets.
    with open(str(REPO)+'.lock','a') as lock:
        try:fcntl.flock(lock,fcntl.LOCK_EX|fcntl.LOCK_NB)
        except BlockingIOError:print('Another deployment is running');return
        try:deployment.execute()
        except Exception as error:
            write_json(deployment.control/'failure.json',{'at':datetime.datetime.utcnow().isoformat()+'Z','target':args.target,'error':str(error)})
            print('Deployment stopped: '+str(error),file=sys.stderr);sys.exit(1)

if __name__=='__main__':main()
