import { chromium } from '@playwright/test';

export async function createOrganizerStorageState(outputPath = 'artifacts/.auth/organizer.json') {
  const baseURL = process.env.BOOKTKIT_BASE_URL || 'https://www.booktkit.com';
  const username = process.env.BOOKTKIT_TEST_ORGANIZER_EMAIL || process.env.BOOKTKIT_TEST_ORGANIZER_USERNAME;
  const password = process.env.BOOKTKIT_TEST_ORGANIZER_PASSWORD;
  if (!username || !password) throw new Error('Organizer test credentials are not configured.');

  const browser = await chromium.launch();
  const context = await browser.newContext({ baseURL });
  const page = await context.newPage();
  await page.goto('/organizer/login', { waitUntil: 'domcontentloaded' });
  await page.locator('#username').fill(username);
  await page.locator('#password').fill(password);
  await page.locator('#login-form button[type="submit"]').click();
  await page.waitForURL(/\/organizer\//, { timeout: 20_000 });
  await context.storageState({ path: outputPath });
  await browser.close();
  return outputPath;
}
