import { test, expect } from '@playwright/test';

test.describe('Mobile API contract @smoke', () => {
  test('customer app public endpoints are reachable', async ({ request }) => {
    for (const path of ['/api', '/api/events', '/api/organizers', '/api/get-basic']) {
      const r = await request.get(path, { headers: { Accept: 'application/json' } });
      expect(r.status(), path).toBeLessThan(500);
      expect(r.status(), path).not.toBe(404);
    }
  });

  test('organizer protected API rejects anonymous access', async ({ request }) => {
    for (const path of ['/api/organizer/dashboard', '/api/organizer/event-booking', '/api/organizer/event-management/events']) {
      const r = await request.get(path, { headers: { Accept: 'application/json' } });
      expect([401,403], path + ' returned ' + r.status()).toContain(r.status());
    }
  });

  test('scanner protected APIs reject anonymous access', async ({ request }) => {
    for (const path of ['/api/scanner/admin/events', '/api/scanner/organizer/events']) {
      const r = await request.get(path, { headers: { Accept: 'application/json' } });
      expect([401,403], path + ' returned ' + r.status()).toContain(r.status());
    }
  });
});
