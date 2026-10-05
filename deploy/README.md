# BookTKIT deployment

Only oreodigi/booktkit and the booktkit account on server.tejum.cloud are in scope.

The installed runner is `/home/booktkit/booktkit-deploy/deploy.py`. It runs as booktkit from `/etc/cron.d/booktkit-git-deploy`, through the private `deploy-all.sh` wrapper. The former root cron calling `git-deploy/deploy.sh` is retired. The shared bare repository is `/home/booktkit/deploy-repository.git` and a shared lock serializes both targets.

| Target | Docroot | Database | State directory |
|---|---|---|---|
| production | /home/booktkit/public_html | booktkit_ems | /home/booktkit/booktkit-deploy |
| staging | /home/booktkit/staging | booktkit_stage | /home/booktkit/booktkit-staging-deploy |

Production deploys only main. Staging deploys only staging. The runner rejects any other branch for either target.

```sh
python3 /home/booktkit/booktkit-deploy/deploy.py --target staging --check
python3 /home/booktkit/booktkit-deploy/deploy.py --target staging
python3 /home/booktkit/booktkit-deploy/deploy.py --target production --check
python3 /home/booktkit/booktkit-deploy/deploy.py --target production
# Explicit production migration authorization only:
python3 /home/booktkit/booktkit-deploy/deploy.py --target production --migrate
```

The runner stages tracked source, validates changed PHP files and unchanged dependency locks, checks for live edits, backs up overwritten/removed files, clears caches, verifies five HTTP routes and the served commit marker, and records state. Legacy unmodified supplier examples are not relinted; one bundled example is incompatible with PHP 8.3. Protected runtime files, credentials, uploads and dependencies are preserved. The two explicit installer-provider stubs are maintained exceptions. Dependency changes require a separately validated installation.

Staging always backs up its DB and runs `artisan migrate --force`. Production migrations only run with `--migrate`. Database dumps are private under `booktkit-backups/git-deploy/TARGET/RELEASE/database.sql`. Success and migration outcome are recorded in state.json; errors go to failure.json. Each target has deploy.log and an independent PAUSED marker. Automatic recovery restores changed files and, when safe, the database. A production health failure after reopening a migrated database leaves it paused in maintenance for reviewed recovery, rather than discarding new customer transactions.

Manual recovery, executed as booktkit:

```sh
python3 /home/booktkit/booktkit-deploy/rollback.py /home/booktkit/booktkit-backups/git-deploy/staging/RELEASE --target staging --restore-database
```

Restoring a DB discards later changes. Review first; `--restore-database` is mandatory when that release contains a DB snapshot. Rollback takes a recovery backup and leaves scheduled deployment PAUSED. Remove PAUSED only after verifying the restored environment. Never delete it merely to bypass a failure.

Runner/rollback updates require copying reviewed files into the private installed directory; pushes do not overwrite these executables automatically. See SETUP-STATUS.md for verified activation and results.

## Staging isolation

The owner selected a separate directory after cPanel refused a separate account for a subdomain of an existing account. This is database/docroot isolation within one Unix account, not separate OS-account isolation. The old public_html/test tree is retained privately at qa-isolation/retired-staging.

HTTP Basic authentication and noindex headers protect staging. Its credentials and disposable account passwords are held privately outside the docroot and in GitHub Actions secrets. APP_ENV=staging, APP_DEBUG=false, MAIL_MAILER=log, unique session cookie/domain, a separate APP_KEY, no Firebase/push credentials, and Razorpay TEST credentials are enforced. EnvironmentMailer also suppresses direct PHPMailer sends in staging/testing; MAIL_MAILER alone did not cover them.

`artisan booktkit:prepare-staging --reset --credentials=PRIVATE_JSON` is destructive and rejects any environment/database/hostname except the exact staging target. It removes customer/organizer/admin data, credentials, tokens, bookings and payment records, scrubs integration credentials, and creates synthetic customer, organizer, admin and scanner accounts plus venue/online events with free/paid tickets. Run only after a backup. Do not expose its credential file or auth storage states.

Google supplies always-pass keys for reCAPTCHA v2, not v3 (https://developers.google.com/recaptcha/docs/faq). Automated staging uses an explicit test mode requiring the staging environment, exact hostname and staging DB. Production CAPTCHA remains active. Real v3 scoring is outside the deterministic auth tests.


## Promotion flow

work → staging → tests pass → PR → main → production

Push work to staging. The account cron deploys staging to the isolated docroot.
BookTKIT Staging Gate waits up to five minutes for the authenticated staging marker
to equal the pushed SHA, then runs smoke, auth, organizer and event-creation.
It verifies the marker before and after each suite and requires 5/3/1/4 passes
with zero failed, skipped or flaky tests. A newer deployment invalidates an older run.

Open a same-repository staging → main PR after the gate passes. The required
check is staging-gate (GitHub Actions). PR gate runs reject other source branches.
Do not push directly to main. Production's runner follows main only.

Main protection must require a PR and staging-gate, enforce administrators,
require up-to-date checks, and disallow force pushes and deletion.
Private-repository branch protection requires a supporting GitHub plan and
repository Administration write permission. If GitHub rejects protection,
this is NOT an enforced production gate; report the blocker.

Use merge commits to preserve staging ancestry. After promotion, synchronize
main back into staging before the next change. Never force-push either branch.
