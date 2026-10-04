import { test, expect } from '@playwright/test';
import { assertMutationAllowed } from '../src/safety.js';

test.describe('Razorpay test-mode checkout @mutation', () => {
  test.beforeEach(() => assertMutationAllowed());

  test('requires explicit Razorpay test configuration', async () => {
    test.skip(!process.env.BOOKTKIT_RAZORPAY_TEST_ENABLED, 'Razorpay test mode not enabled');
    expect(process.env.BOOKTKIT_RAZORPAY_TEST_ENABLED).toBe('true');
  });
});
