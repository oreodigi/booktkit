import { test, expect } from '@playwright/test';

test('@smoke public homepage is reachable', async ({ page }) => {
  const response = await page.goto('/', { waitUntil: 'load' });
  expect(response, 'homepage must return a response').not.toBeNull();
  expect(response.status(), 'homepage HTTP status').toBeLessThan(400);
  await expect(page.locator('body')).toBeVisible();
});

test('@smoke login page renders without fatal browser errors', async ({ page }) => {
  const errors = [];
  page.on('pageerror', error => errors.push(error.message));
  const response = await page.goto('/customer/login', { waitUntil: 'load' });
  expect(response).not.toBeNull();
  expect(response.status()).toBe(200);
  await expect(page.locator('body')).toBeVisible();
  expect(errors).toEqual([]);
});

test('@smoke organizer signup is reachable', async ({ page }) => {
  const response = await page.goto('/organizer/signup', { waitUntil: 'load' });
  expect(response).not.toBeNull();
  expect(response.status()).toBe(200);
  await expect(page.locator('body')).toBeVisible();
});
