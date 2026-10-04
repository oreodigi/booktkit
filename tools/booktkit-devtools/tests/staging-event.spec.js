import { test, expect } from '@playwright/test';
import { assertMutationAllowed } from '../src/safety.js';

test.describe('Organizer event builder @mutation', () => {
  test.beforeEach(() => assertMutationAllowed());

  test('venue event form exposes core scheduling and pricing controls', async ({ page }) => {
    test.skip(!process.env.BOOKTKIT_TEST_ORGANIZER_PASSWORD, 'organizer credentials not configured');
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
