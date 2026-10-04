import { test, expect } from '@playwright/test';
import { attachDiagnostics } from '../src/diagnostics.js';

test.describe('Public customer journey @smoke', () => {
  for (const [path, label] of [['/', 'home'], ['/events', 'events'], ['/organizers', 'organizers'], ['/contact', 'contact'], ['/about-us', 'about']]) {
    test(label + ' page is healthy', async ({ page }) => {
      const d = attachDiagnostics(page);
      const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
      expect(response).not.toBeNull();
      expect(response.status()).toBeLessThan(400);
      await expect(page.locator('body')).toBeVisible();
      const snap = d.snapshot();
      expect(snap.pageErrors, JSON.stringify(snap, null, 2)).toEqual([]);
    });
  }
});
