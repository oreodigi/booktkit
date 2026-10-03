# GitHub and cPanel deployment

Repository: `oreodigi/booktkit`, private, branch `main`.
PC workspace: `C:\Users\pradeep\Desktop\Booktikt`.
Live account: `booktkit` on `server.tejum.cloud`.
Website directory: `/home/booktkit/public_html`.

The repository contains maintained website and mobile source, logos, project instructions and deployment tools. Purchased archives, dependency directories, credentials, customer documents and uploads remain outside Git.

The intended deployment flow is PC commit → GitHub `main` → cPanel poll every minute → validated website files. Mobile applications are versioned but are not copied into the website. PC updates require `git pull --ff-only`; local uncommitted work is never overwritten automatically.

`deploy.py` is installed privately at `/home/booktkit/booktkit-deploy/deploy.py` and runs as the cPanel account. It fetches `main`, stages tracked website files, validates PHP, rejects changed dependency lockfiles pending a separately tested dependency installation, checks for live edits, backs up changed files, applies changes, clears Laravel caches, checks public routes, and records the deployed commit. Failed health checks restore the previous files. No migrations or dependency updates run automatically.

Production `.env`, uploaded files, runtime dependencies and storage are preserved. Legacy gateway settings are held in `/home/booktkit/booktkit-shared/payment-config.php`; the tracked public configuration loads that private file. Installer provider stubs in `vendor-patches` preserve the retired supplier installers. Original dependency license notices remain intact.

Deployment state: `/home/booktkit/booktkit-deploy/state.json`.
Deployment log: `/home/booktkit/booktkit-deploy/deploy.log`.
Failure status: `/home/booktkit/booktkit-deploy/failure.json`.
Backups: `/home/booktkit/booktkit-backups/git-deploy/`.
Restore the active deployment: `python3 /home/booktkit/booktkit-deploy/rollback.py BACKUP_DIRECTORY`. Rollback pauses the scheduled job.
Pause: create `/home/booktkit/booktkit-deploy/PAUSED`.
Resume: remove that pause file after reviewing the reason for the pause.

Files changed directly in cPanel must be imported into Git before another deployment replaces them. Changes to the deployment runner itself must be reviewed and copied to its private installed location; a repository push alone does not replace the runner.

See `SETUP-STATUS.md` for the verified activation status. This README describes the intended setup, not evidence that access or the scheduled job is active.

GitHub host verification uses the published Ed25519 host key from [GitHub's SSH fingerprints documentation](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/githubs-ssh-key-fingerprints).
