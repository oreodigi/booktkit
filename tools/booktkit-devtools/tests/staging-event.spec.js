import { test, expect } from '@playwright/test';
import { assertMutationAllowed } from '../src/safety.js';

async function loginOrganizer(page) {
  const username = process.env.BOOKTKIT_TEST_ORGANIZER_EMAIL;
  const password = process.env.BOOKTKIT_TEST_ORGANIZER_PASSWORD;
  if (!username || !password) throw new Error('Organizer staging credentials are required');

  await page.goto('/organizer/login', { waitUntil: 'domcontentloaded' });
  await expect(page.locator('#login-form')).toBeVisible();
  await page.locator('#username').fill(username);
  await page.locator('#password').fill(password);
  await page.locator('#login-form button[type="submit"]').click();
  await page.waitForLoadState('domcontentloaded');
  await expect(page).not.toHaveURL(/\/organizer\/login(?:[?#]|$)/);
}

test.describe('Organizer event builder @mutation', () => {
  test.skip(process.env.BOOKTKIT_ALLOW_MUTATIONS !== 'true', 'requires explicitly enabled staging tests');
  test.beforeEach(() => assertMutationAllowed());

  test('authenticated organizer can open venue event builder', async ({ page }) => {
    test.skip(!process.env.BOOKTKIT_TEST_ORGANIZER_PASSWORD, 'organizer credentials not configured');
    await loginOrganizer(page);
    await page.goto('/organizer/add-event/?type=venue', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#eventForm')).toBeVisible();
    for (const selector of [
      'input[name="event_type"]',
      'input[name="start_date"]',
      'input[name="start_time"]',
      'input[name="end_date"]',
      'input[name="end_time"]',
      '#ticket-pricing',
      '#EventSubmit'
    ]) await expect(page.locator(selector), selector).toBeVisible();
    expect(await page.locator('input[name="event_type"]').inputValue()).toBe('venue');
  });
});
