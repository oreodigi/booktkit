import { test, expect } from '@playwright/test';

test.describe('Box Office POS organizer flow', () => {
  test('authenticated organizer can open POS and the POS is not coupled to payout setup', async ({ page }, info) => {
    test.skip(info.project.metadata.role !== 'organizer', 'organizer-only suite');
    const response = await page.goto('/organizer/box-office', { waitUntil: 'domcontentloaded' });
    expect(response).not.toBeNull();
    expect(response.status()).toBeLessThan(400);
    await expect(page).not.toHaveURL(/\/organizer\/login/);
    await expect(page.locator('body')).not.toContainText(/complete payment setup/i);
    await expect(page.locator('body')).toContainText(/box office|point of sale|pos/i);
  });

  test('box-office reports and shifts are reachable by the owning organizer', async ({ page }, info) => {
    test.skip(info.project.metadata.role !== 'organizer', 'organizer-only suite');
    for (const path of ['/organizer/box-office/reports', '/organizer/box-office/shifts']) {
      const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
      expect(response, path).not.toBeNull();
      expect(response.status(), path).toBeLessThan(400);
      await expect(page, path).not.toHaveURL(/\/organizer\/login/);
    }
  });
});
