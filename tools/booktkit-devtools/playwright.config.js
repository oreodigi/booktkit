import { defineConfig, devices } from '@playwright/test';
import path from 'node:path';
import { targetConfig } from './src/environment.js';

const target = targetConfig();
const suite = process.env.BOOKTKIT_SUITE || 'smoke';
const runDir = process.env.BOOKTKIT_RUN_DIR || path.resolve('artifacts/local');
const authDir = path.join(runDir, '.auth');
const authenticatedFiles = /(?:authenticated-role|organizer-authenticated|staging-event|pos-flow|staff-rbac|admission-flow)\.spec\.js/;
const publicProject = (name, device) => ({ name, testIgnore: authenticatedFiles, ...(target.production ? {testMatch:/readonly-smoke\.spec\.js/} : {}), use: { ...devices[device] } });
const projects = [publicProject('desktop-chromium', 'Desktop Chrome')];
if (['all','mobile'].includes(suite)) projects.push(
  publicProject('desktop-firefox', 'Desktop Firefox'), publicProject('desktop-webkit', 'Desktop Safari'),
  publicProject('mobile-chromium', 'Pixel 7'), publicProject('mobile-webkit', 'iPhone 14')
);
if (suite === 'mobile') projects.splice(0, 3);
if (!target.production && ['all','auth','login','organizer','event-creation','pos','staff-rbac','admission'].includes(suite)) {
  for (const role of ['organizer','customer','admin']) {
    if (['organizer','event-creation','pos','staff-rbac','admission'].includes(suite) && role === 'customer') continue;
    projects.push({ name: `${role}-chromium`, metadata: { role },
      testMatch: role === 'organizer' ? authenticatedFiles : role === 'admin' ? /(?:authenticated-role|staging-event)\.spec\.js/ : /authenticated-role\.spec\.js/,
      use: { ...devices['Desktop Chrome'], storageState: path.join(authDir, `${role}.json`) } });
  }
}
export default defineConfig({
  testDir: './tests', timeout: 45_000, globalTimeout: 20 * 60_000, expect: { timeout: 8_000 },
  fullyParallel: false, forbidOnly: !!process.env.CI, retries: 1, workers: 2,
  globalSetup: './src/global-setup.js',
  reporter: [['list'], ['json', { outputFile: path.join(runDir, 'results.json') }],
    ['html', { outputFolder: path.join(runDir, 'html-report'), open: 'never' }]],
  outputDir: path.join(runDir, 'test-results'),
  use: { baseURL: target.baseURL, httpCredentials: target.httpCredentials,
    actionTimeout: 10_000, navigationTimeout: 20_000,
    // Authenticated traces/screenshots can contain session material. Public diagnostics retain them.
    trace: target.production ? 'retain-on-failure' : 'off', screenshot: 'only-on-failure',
    video: 'off', ignoreHTTPSErrors: false },
  projects,
});
