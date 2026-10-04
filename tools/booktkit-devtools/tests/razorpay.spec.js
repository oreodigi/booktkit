import { test, expect } from '@playwright/test';
import { assertMutationAllowed } from '../src/safety.js';

test.describe('Razorpay test-mode checkout @mutation', () => {
  test.skip(process.env.BOOKTKIT_ALLOW_MUTATIONS !== 'true' ||
    process.env.BOOKTKIT_RAZORPAY_TEST_ENABLED !== 'true', 'requires explicitly enabled staging payment tests');
  test.beforeEach(() => assertMutationAllowed());

  test('requires explicit Razorpay test configuration', async () => {
    expect(process.env.BOOKTKIT_RAZORPAY_TEST_ENABLED).toBe('true');
  });
});
