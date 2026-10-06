import { test, expect } from '@playwright/test';

test.describe('Organizer workforce / RBAC', () => {
  test('organizer team management is reachable for authenticated organizer', async ({ page }, info) => {
    test.skip(info.project.metadata.role !== 'organizer', 'organizer-only suite');
    const response = await page.goto('/organizer/team', { waitUntil: 'domcontentloaded' });
    expect(response).not.toBeNull();
    expect(response.status()).toBeLessThan(400);
    await expect(page).not.toHaveURL(/\/organizer\/login/);
    await expect(page.locator('body')).toContainText(/team|staff/i);
  });

  test('staff protected web routes do not allow an anonymous browser', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();
    for (const path of ['/staff', '/staff/shifts', '/staff/box-office']) {
      const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
      expect(response, path).not.toBeNull();
      await expect(page, path).toHaveURL(/\/staff\/login/);
    }
    await context.close();
  });

  test('staff scanner validates login and protects assigned-event API', async ({ request }) => {
    const login = await request.post('/api/staff-scanner/login', { headers:{Accept:'application/json'}, data:{} });
    expect(login.status()).toBe(422);
    const events = await request.get('/api/staff-scanner/events', { headers:{Accept:'application/json'}, maxRedirects:0 });
    expect([401,403], 'anonymous staff event list').toContain(events.status());
    const scan = await request.post('/api/staff-scanner/scan', { headers:{Accept:'application/json'}, data:{booking_id:'btk_invalid'} });
    expect([401,403], 'anonymous staff scan').toContain(scan.status());
  });
});
