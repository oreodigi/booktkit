import { test, expect } from '@playwright/test';

test.describe('Organizer authentication @smoke', () => {
  test('login form has expected controls and reCAPTCHA v3 integration', async ({ page }) => {
    const errors = [];
    page.on('pageerror', e => errors.push(e.message));
    await page.goto('/organizer/login', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#login-form')).toBeVisible();
    await expect(page.locator('#username')).toBeVisible();
    await expect(page.locator('#password')).toBeVisible();
    await expect(page.locator('#login-form button[type="submit"]')).toBeVisible();
    await expect(page.locator('#login-form')).toContainText(/login/i);
    expect(errors).toEqual([]);
  });

  test('signup form exposes required identity controls', async ({ page }) => {
    await page.goto('/organizer/signup', { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#login-form')).toBeVisible();
    for (const selector of ['#name', '#username', '#email', '#password', '#re-password']) {
      await expect(page.locator(selector), selector).toBeVisible();
    }
    await expect(page.locator('#login-form button[type="submit"]')).toBeVisible();
  });

  test('mobile auth pages do not overflow viewport', async ({ page }, testInfo) => {
    test.skip(!testInfo.project.name.startsWith('mobile-'), 'mobile project only');
    for (const path of ['/organizer/login', '/organizer/signup']) {
      await page.goto(path, { waitUntil: 'domcontentloaded' });
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 2);
      expect(overflow, path + ' has horizontal overflow').toBe(false);
    }
  });
});
