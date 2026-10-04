import { test, expect } from '@playwright/test';
import { attachDiagnostics } from '../src/diagnostics.js';

const routes = ['/', '/login', '/organizer/login'];
for (const route of routes) {
  test(`runtime diagnostics: ${route}`, async ({ page }) => {
    const diagnostics = attachDiagnostics(page);
    const response = await page.goto(route, { waitUntil: 'networkidle' });
    expect(response?.ok(), `HTTP failure for ${route}`).toBeTruthy();
    await expect(page.locator('body')).not.toBeEmpty();
    const snapshot = diagnostics.snapshot();
    await test.info().attach('runtime-diagnostics', { body: JSON.stringify({ route, ...snapshot }, null, 2), contentType:'application/json' });
    expect(snapshot.pageErrors, 'uncaught page errors').toEqual([]);
  });
}
