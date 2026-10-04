import { test, expect } from '@playwright/test';

test.describe('BookTKIT API surface @smoke', () => {
  test('customer booking endpoint is not a browser GET page', async ({ request }) => {
    const r = await request.get('/api/event-booking');
    expect([404,405]).toContain(r.status());
  });

  test('organizer scanner protected endpoint rejects anonymous access', async ({ request }) => {
    const r = await request.post('/api/organizer/check-qrcode', { data: { qrcode: 'invalid-test-code' } });
    expect([401,403,404,422]).toContain(r.status());
  });

  test('Razorpay v1 order endpoint exists and does not 404 on POST', async ({ request }) => {
    const r = await request.post('/api/v1/payments/razorpay/order', { data: {} });
    expect(r.status()).not.toBe(404);
  });

  test('Razorpay webhook endpoint exists and does not 404 on POST', async ({ request }) => {
    const r = await request.post('/api/v1/webhooks/razorpay', {
      headers: { 'content-type': 'application/json' },
      data: { event: 'booktkit.devtools.probe' }
    });
    expect(r.status()).not.toBe(404);
  });
});
