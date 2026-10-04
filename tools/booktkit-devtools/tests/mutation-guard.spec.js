import { test, expect } from '@playwright/test';
import { assertMutationAllowed } from '../src/safety.js';

test('production mutation safety guard', async () => {
  const oldBase = process.env.BOOKTKIT_BASE_URL;
  const oldFlag = process.env.BOOKTKIT_ALLOW_MUTATIONS;
  process.env.BOOKTKIT_BASE_URL = 'https://www.booktkit.com';
  process.env.BOOKTKIT_ALLOW_MUTATIONS = 'true';
  expect(() => assertMutationAllowed()).toThrow(/blocked.*production/i);
  process.env.BOOKTKIT_BASE_URL = oldBase;
  process.env.BOOKTKIT_ALLOW_MUTATIONS = oldFlag;
});
