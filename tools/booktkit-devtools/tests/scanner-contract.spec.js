import { test, expect } from '@playwright/test';

test.describe('Scanner API contract @smoke', () => {
  test('organizer scanner login validates required credentials without creating a session', async ({ request }) => {
    const r = await request.post('/api/scanner/organizer/login/submit', {
      headers: { Accept: 'application/json' },
      data: {}
    });
    expect(r.status()).toBe(422);
  });

  test('admin scanner login validates required credentials without creating a session', async ({ request }) => {
    const r = await request.post('/api/scanner/admin/login/submit', {
      headers: { Accept: 'application/json' },
      data: {}
    });
    expect(r.status()).toBe(422);
  });

  test('QR mutation endpoints reject anonymous requests', async ({ request }) => {
    for (const path of ['/api/scanner/organizer/check-qrcode', '/api/scanner/admin/check-qrcode']) {
      const r = await request.post(path, {
        headers: { Accept: 'application/json' },
        data: { booking_id: 'BOOKTKIT-DEVTOOLS__INVALID' }
      });
      expect([401,403], path + ' returned ' + r.status()).toContain(r.status());
    }
  });
});
