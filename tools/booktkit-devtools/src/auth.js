import fs from 'node:fs/promises';
import path from 'node:path';
import { chromium } from '@playwright/test';
import { targetConfig } from './environment.js';

export const rolePaths = {
  organizer: { login: '/organizer/login', dashboard: '/organizer/dashboard' },
  customer: { login: '/customer/login', dashboard: '/customer/dashboard' },
  admin: { login: '/admin', dashboard: '/admin/dashboard' },
};
export async function createRoleStorageState(role, outputPath) {
  const target = targetConfig();
  if (target.production) throw new Error('Authenticated setup is staging-only.');
  const key = role.toUpperCase();
  const username = process.env[`BOOKTKIT_TEST_${key}_USERNAME`] || process.env[`BOOKTKIT_TEST_${key}_EMAIL`];
  const password = process.env[`BOOKTKIT_TEST_${key}_PASSWORD`];
  if (!username || !password) throw new Error(`Missing dedicated ${role} credentials`);
  const browser = await chromium.launch();
  try {
    const context = await browser.newContext({ baseURL: target.baseURL, httpCredentials: target.httpCredentials });
    const page = await context.newPage();
    page.setDefaultTimeout(12_000);
    await page.goto(rolePaths[role].login, { waitUntil: 'domcontentloaded' });
    await page.locator('input[name="username"]').fill(username);
    await page.locator('input[name="password"]').fill(password);
    await Promise.all([
      page.waitForURL(url => url.pathname.replace(/\/$/,'') === rolePaths[role].dashboard, { timeout: 20_000, waitUntil: 'domcontentloaded' }),
      page.locator('form button[type="submit"], form input[type="submit"]').click(),
    ]);
    const response = await page.goto(rolePaths[role].dashboard, { waitUntil: 'domcontentloaded' });
    if (!response?.ok() || new URL(page.url()).pathname.replace(/\/$/,'') !== rolePaths[role].dashboard) {
      throw new Error(`${role} authentication did not reach its protected dashboard`);
    }
    await fs.mkdir(path.dirname(outputPath), { recursive: true, mode: 0o700 });
    await context.storageState({ path: outputPath });
    await fs.chmod(outputPath, 0o600);
  } finally { await browser.close(); }
}
export const createOrganizerStorageState = outputPath => createRoleStorageState('organizer', outputPath);
