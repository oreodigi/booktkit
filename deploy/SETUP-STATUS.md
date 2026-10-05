# Verified deployment and QA status

Evidence date: 2026-10-05 UTC / 2026-10-06 IST. This replaces the stale claim that deployment was never activated.

## Audit findings and actions

| Item | Expected | Audit actual | Action |
|---|---|---|---|
| Deployment cron | Private validated runner as booktkit | An undocumented root cron ran git-deploy/deploy.sh; documented state/log absent | Replaced with target-aware private runner under booktkit; cron backed up |
| Production commit | GitHub main | 1f8f8daee156d0079acd1218a60864390c0948b9, matching main at audit | Verified tracked files; adopted exact baseline after aligning two comment-only vendor stub differences |
| Migrations | All current migrations applied | All 19 current application migration files applied; historical DB migration ledger also contains legacy entries | No production migration needed; staging automatic backup/migrate enabled |
| Staging | Isolated protected environment | Existing test subdomain, SSL and separate DB existed, but nested docroot returned 404 and copied sensitive state | Rebuilt outside public_html with sanitized data and access controls |
| Runtime | Supported PHP and working dependencies | PHP 8.3 CLI; staging web handler initially 8.2; Composer phar available, no normal composer executable | Staging switched to ea-php83; dependencies verified through real page/auth runs |
| Queue/cron | Deliberate app scheduling | Queue sync, no worker, empty Laravel schedule; deployment cron active | Retained sync behavior; no unnecessary worker/scheduler added |
| Runner/MCP | Bounded asynchronous execution | Local MCP awaited buffered full runs without timeout | Background run IDs, streamed logs, status/log tools and process-tree timeout |
| CI | Fast production checks, full staging suites | Full 17-spec/five-project matrix on production pushes; latest ten DevTools runs failed before tests | Push smoke reduced to Chromium; full suites staging-only |
| GitHub billing | Jobs receive runners | Annotation confirmed failed payment or spending-limit block | Owner updated budget; new PHPUnit job reached MySQL/Composer/PHPUnit, confirming runner access restored |
| Auth/CAPTCHA | Dedicated logged-in states | No global setup/storageState; CAPTCHA and copied credentials blocked safe tests | Fresh role states, private dedicated accounts and strictly staging-only CAPTCHA test mode |
| Mobile script | Configured project name | mobile-chrome did not exist | npm mobile command selects configured mobile-chromium/mobile-webkit suite |
| PHPUnit | Isolated DB with base schema | SQLite disabled; no base schema or safe DB guard | Dedicated booktkit_test, schema-only dump, guard, transactional schema loader and MySQL CI |
| Event tests | Real wizard and save behavior | Expected hidden fields visible together; actual wizard/save bugs also discovered | Step-by-step UI tests and fixes for container visibility, language assignment, ticket defaults |

## Staging and backups

- cPanel account: booktkit; selected directory /home/booktkit/staging, outside public_html.
- DB: booktkit_stage, separate from booktkit_ems; customer/organizer/admin data, tokens and payment records removed and synthetic fixtures seeded.
- Private account credentials: /home/booktkit/qa-isolation/test-credentials.json, mode 600; 14 GitHub secret names verified and values synchronized securely.
- Basic authentication credential hash: /home/booktkit/staging-access/http.htpasswd. Anonymous 401, authenticated homepage 200, noindex headers and protected .env verified.
- Initial files and production/staging DB backups: /home/booktkit/booktkit-backups/qa-isolation-20261005T180443Z.
- First validated GitHub staging deployment: 097eb17e7ab98da7871fc9bea31d9d0018af4274, at 20261005T192844Z. All five HTTP health routes returned 200 and /__deployment.json matched. Migration result passed after DB backup.
- Installed runner: /home/booktkit/booktkit-deploy/deploy.py. Separate state/log/failure/PAUSED files under booktkit-deploy and booktkit-staging-deploy.
- /etc/cron.d/booktkit-git-deploy now invokes the private wrapper as booktkit. The old root deployment script is retained but no longer scheduled.

## Verification evidence

| Verification | Actual result |
|---|---|
| Staging smoke | 5 passed, 0 failed/skipped/flaky; run a9c013da-0880-4688-9adb-fae5c0a5e344 |
| Customer/organizer/admin authentication | 3 passed, 0 failed/skipped/flaky; run 3e2bfd62-7c96-481b-9519-0b771dd4f1db |
| Organizer chooser | 1 passed, 0 failed/skipped/flaky; run 874af6dc-a602-4ae6-a242-f5c3653f8d31 |
| Actual admin/organizer venue/online event creation | 4 passed, 0 failed/skipped/flaky; run e4a62742-117e-4583-90b5-594992a72183 |
| Runner async response and hard timeout | 1 Node regression passed |
| Local isolated PHPUnit | 5 tests, 12 assertions, passed |
| First new PHPUnit CI | Run 37363352293 reached PHPUnit after successful MySQL, PHP and Composer setup; failed on earlier test harness, subsequently fixed locally |

## Completion record — 2026-10-05 19:48 UTC / 2026-10-06 01:18 IST

- Implementation commit: 5f2a3c7720420bab7e79f9c8f11f1f62936f8f74.
- Trivial probe commit: 5d81a834094c16dc4b8e0851d67bf226069a0904. The scheduled staging poll deployed it at 20261005T194445Z with changed=0, successful migration check and DB backup. Both staging state.json and the authenticated live /__deployment.json returned this exact commit.
- main was fast-forwarded to the probe commit. Production's scheduled runner deployed the same commit and its live marker matched. Both targets had no PAUSED or failure.json; production migration status was not_requested.
- PHPUnit CI run https://github.com/oreodigi/booktkit/actions/runs/37365095836 completed SUCCESS: 5 tests, 12 assertions. MySQL initialization, Composer install, provider setup and PHPUnit all succeeded. This also verifies that the owner’s billing change restored GitHub runner access.
- Post-deployment production read-only smoke: 5 passed, 0 failed/skipped/flaky, run 2c42bd47-ae1f-444b-8184-c120ca3d57b1.
- The private MCP github-actions.js and remote-mcp-server.js were backed up and updated, and booktkit-mcp.service restarted successfully. Runtime tools/list exposed start_suite, get_run_status and get_run_log. Status returned the successful PHPUnit run in 0.41 seconds; log retrieval returned the actual 5-test / 12-assertion result in 2.39 seconds.
- Both production and staging scheduled targets now default to main. Installed deploy.py and rollback.py were refreshed from reviewed source, with backups.
- Extra staging-auth and main CI runs were queued at this checkpoint; those are not counted as passed. The core staging counts above are actual server-run Playwright results. The complete multi-browser/payment/scanner suite and production rollback drills remain unverified and are outside these core pass counts.

Later documentation-only commits may advance the deployed marker without changing the verified implementation. Read current state.json and the live marker for the latest exact SHA.
