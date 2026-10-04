import { test, expect } from '@playwright/test';

test.describe('Customer authentication @smoke', () => {
  for (const path of ['/customer/login', '/customer/signup']) {
    test(path + ' loads without fatal errors', async ({ page }) => {
      const errors = [];
      page.on('pageerror', e => errors.push(e.message));
      const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
      expect(response).not.toBeNull();
      expect(response.status()).toBeLessThan(500);
      await expect(page.locator('body')).toBeVisible();
      expect(errors).toEqual([]);
    });
  }
});
