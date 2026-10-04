import { test, expect } from '@playwright/test';

test.describe('BookTKIT Razorpay v1 contract @smoke', () => {
  test('order creation validates authoritative input contract', async ({ request }) => {
    const r = await request.post('/api/v1/payments/razorpay/order', {
      headers: { Accept: 'application/json' },
      data: {}
    });
    expect(r.status()).toBe(422);
    const body = await r.json();
    expect(body.errors || body.message).toBeTruthy();
  });

  test('verification rejects incomplete payment proof', async ({ request }) => {
    const r = await request.post('/api/v1/payments/razorpay/verify', {
      headers: { Accept: 'application/json' },
      data: {}
    });
    expect(r.status()).toBe(422);
  });

  test('webhook rejects unsigned payload', async ({ request }) => {
    const r = await request.post('/api/v1/webhooks/razorpay', {
      headers: { Accept: 'application/json', 'content-type': 'application/json' },
      data: { event: 'payment.captured' }
    });
    expect(r.status()).toBe(400);
  });
});
