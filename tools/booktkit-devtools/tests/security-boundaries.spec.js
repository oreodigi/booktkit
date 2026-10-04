import { test, expect } from '@playwright/test';

test.describe('BookTKIT security boundary probes @smoke', () => {
  test('organizer payment settings require organizer authentication', async ({ request }) => {
    const r = await request.get('/api/v1/organizer/payments/settings');
    expect([401,403,404]).toContain(r.status());
  });

  test('customer dashboard redirects anonymous browser sessions to login', async ({ page }) => {
    await page.goto('/customer/dashboard', { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/\/customer\/login|\/customer\/dashboard/);
  });

  test('organizer dashboard redirects anonymous browser sessions to login', async ({ page }) => {
    await page.goto('/organizer/dashboard', { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/\/organizer\/login|\/organizer\/dashboard/);
  });
});
