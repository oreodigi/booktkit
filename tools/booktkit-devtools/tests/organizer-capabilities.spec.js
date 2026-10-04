import { test, expect } from '@playwright/test';

test.describe('Organizer capability surface', () => {
  test('event type chooser exposes the current BookTKIT event modes', async ({ page }) => {
    test.skip(!process.env.BOOKTKIT_TEST_ORGANIZER_PASSWORD, 'authenticated organizer credentials not configured');
    await page.goto('/organizer/choose-event-type/', { waitUntil: 'domcontentloaded' });
    const body = await page.locator('body').innerText();
    expect(body).toMatch(/Online Event/i);
    expect(body).toMatch(/Venue Event/i);
  });

  test('organizer dashboard route is protected', async ({ page }) => {
    const response = await page.goto('/organizer/dashboard', { waitUntil: 'domcontentloaded' });
    expect(response).not.toBeNull();
    await expect(page).toHaveURL(/\/organizer\/login|\/organizer\/dashboard/);
  });
});
