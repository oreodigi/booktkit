import { test, expect } from '@playwright/test';

test.describe('BookTKIT security boundary probes @smoke', () => {
  test('organizer payment settings require organizer authentication', async ({ request }) => {
    const r = await request.get('/api/v1/organizer/payments/settings', {
      headers: { Accept: 'application/json' }, maxRedirects: 0
    });
    expect(r.status()).toBe(401);
    expect(await r.json()).toMatchObject({ message: 'Unauthenticated.' });
  });

  test('customer dashboard redirects anonymous browser sessions to login', async ({ page }) => {
    await page.goto('/customer/dashboard', { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/\/customer\/login(?:[?#]|$)/);
  });

  test('organizer dashboard redirects anonymous browser sessions to login', async ({ page }) => {
    await page.goto('/organizer/dashboard', { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/\/organizer\/login(?:[?#]|$)/);
  });
});
