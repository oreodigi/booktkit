import { test, expect } from '@playwright/test';

test.describe('Organizer capability surface', () => {
  test('organizer dashboard route is protected', async ({ page }) => {
    const response = await page.goto('/organizer/dashboard', { waitUntil: 'domcontentloaded' });
    expect(response).not.toBeNull();
    await expect(page).toHaveURL(/\/organizer\/login(?:[?#]|$)/);
  });
});
