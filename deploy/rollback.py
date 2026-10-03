#!/usr/bin/env python3
"""Restore a recorded deployment and pause further automatic updates."""
import fcntl
import json
from pathlib import Path
import sys
import deploy

if len(sys.argv) != 2:
    sys.exit('Usage: rollback.py /home/booktkit/booktkit-backups/git-deploy/BACKUP')
backup = Path(sys.argv[1]).resolve()
root = (deploy.HOME / 'booktkit-backups/git-deploy').resolve()
if root not in backup.parents or not (backup / 'manifest.json').is_file():
    sys.exit('Choose a recorded Git deployment backup.')
with open(str(deploy.CONTROL / 'deploy.lock'), 'a') as lock:
    fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
    manifest = json.loads((backup / 'manifest.json').read_text())
    state = json.loads(deploy.STATE.read_text())
    if state['commit'] != manifest['after']:
        sys.exit('This backup does not match the active deployment.')
    for name, existed in manifest['existed'].items():
        deploy.safe_target(name)
        if existed and not (backup / 'files' / name).is_file():
            sys.exit('Backup file missing: ' + name)
    (deploy.CONTROL / 'PAUSED').write_text('Manual rollback; review before resuming.\n')
    for name, existed in manifest['existed'].items():
        target = deploy.safe_target(name)
        if existed:
            deploy.replace(backup / 'files' / name, target)
        elif target.exists():
            target.unlink()
    deploy.clear_caches()
    print(deploy.health(manifest['before']['commit'] or 'original'))
    if manifest['before']['commit']:
        deploy.write_json(deploy.STATE, manifest['before'])
    else:
        deploy.STATE.unlink()
    print('Previous files restored; automatic deployment is paused.')
