#!/usr/bin/env python3
"""Restore a target's last recorded release, retaining a recovery backup."""
import argparse, datetime, fcntl, json, os, shutil
from pathlib import Path
from types import SimpleNamespace
from deploy import Deployment, HOME, REPO, PHP, run, replace, write_json

def main():
    p=argparse.ArgumentParser()
    p.add_argument('backup_directory')
    p.add_argument('--target',required=True,choices=['production','staging'])
    p.add_argument('--restore-database',action='store_true',help='Explicitly authorize restoring the backup DB; loses changes since backup')
    args=p.parse_args();os.umask(0o077)
    backup=Path(args.backup_directory).resolve()
    base=(HOME/'booktkit-backups/git-deploy'/args.target).resolve()
    if base not in backup.parents:raise RuntimeError('Backup must belong to the selected target')
    manifest=json.loads((backup/'manifest.json').read_text())
    if manifest['target']!=args.target:raise RuntimeError('Target mismatch')
    d=Deployment(SimpleNamespace(target=args.target))
    current=json.loads(d.state.read_text())
    if current['commit']!=manifest['after']:raise RuntimeError('Backup is not for the active deployment')
    if (backup/'database.sql').exists() and not args.restore_database:raise RuntimeError('This release has a DB backup; review data loss and explicitly use --restore-database')
    with open(str(REPO)+'.lock','a') as lock:
        fcntl.flock(lock,fcntl.LOCK_EX|fcntl.LOCK_NB)
        recovery=base/('rollback-recovery-'+datetime.datetime.utcnow().strftime('%Y%m%dT%H%M%SZ'))
        recovery.mkdir(mode=0o700)
        for name in manifest['existed']:
            live=d.target(name)
            if live.is_file():
                dst=recovery/'files'/name;dst.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(str(live),str(dst))
        write_json(recovery/'state.json',current)
        (d.control/'PAUSED').write_text('Manual rollback; inspect before resuming deploys.\n')
        run([PHP,'artisan','down','--no-ansi'],d.live)
        env=d.database_backup(recovery) if args.restore_database else None
        for name,existed in manifest['existed'].items():
            live=d.target(name)
            if existed:replace(backup/'files'/name,live)
            elif live.exists():live.unlink()
        if env:d.restore_database(backup,env)
        d.caches()
        write_json(d.state,manifest['before'])
        run([PHP,'artisan','up','--no-ansi'],d.live)
        print('Rollback restored; scheduled deployment remains PAUSED. Recovery backup: '+str(recovery))

if __name__=='__main__':main()
