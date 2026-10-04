import { test, expect } from '@playwright/test';

test.describe('BookTKIT API surface @smoke', () => {
  test('customer booking endpoint is not a browser GET page', async ({ request }) => {
    const r = await request.get('/api/event-booking', {
      headers: { Accept: 'application/json' }, maxRedirects: 0
    });
    expect([404,405]).toContain(r.status());
    expect(r.headers()['content-type']).toContain('application/json');
    expect((await r.json()).message).toBeTruthy();
  });

  test('organizer scanner protected endpoint rejects anonymous access', async ({ request }) => {
    const r = await request.post('/api/organizer/check-qrcode', {
      headers: { Accept: 'application/json' }, maxRedirects: 0,
      data: { qrcode: 'invalid-test-code' }
    });
    expect(r.status()).toBe(401);
  });

  test('Razorpay v1 order endpoint exists and does not 404 on POST', async ({ request }) => {
    const r = await request.post('/api/v1/payments/razorpay/order', {
      headers: { Accept: 'application/json' }, data: {}
    });
    expect(r.status()).toBe(422);
  });

  test('Razorpay webhook endpoint exists and does not 404 on POST', async ({ request }) => {
    const r = await request.post('/api/v1/webhooks/razorpay', {
      headers: { Accept: 'application/json', 'content-type': 'application/json' },
      data: { event: 'booktkit.devtools.probe' }
    });
    expect(r.status()).toBe(400);
  });
});
