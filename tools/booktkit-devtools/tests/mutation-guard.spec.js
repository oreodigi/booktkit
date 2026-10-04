import { test, expect } from '@playwright/test';
import { assertMutationAllowed } from '../src/safety.js';

test('production mutation safety guard', async () => {
  const oldBase = process.env.BOOKTKIT_BASE_URL;
  const oldFlag = process.env.BOOKTKIT_ALLOW_MUTATIONS;
  try {
    process.env.BOOKTKIT_ALLOW_MUTATIONS = 'true';
    for (const host of ['booktkit.com', 'www.booktkit.com']) {
      process.env.BOOKTKIT_BASE_URL = 'https://' + host;
      expect(() => assertMutationAllowed()).toThrow(/blocked.*production/i);
    }
    process.env.BOOKTKIT_BASE_URL = 'https://example.com';
    expect(() => assertMutationAllowed()).toThrow(/approved HTTPS/);
    process.env.BOOKTKIT_BASE_URL = 'https://staging.booktkit.com';
    process.env.BOOKTKIT_ALLOW_MUTATIONS = 'false';
    expect(() => assertMutationAllowed()).toThrow(/disabled/);
  } finally {
    if (oldBase === undefined) delete process.env.BOOKTKIT_BASE_URL;
    else process.env.BOOKTKIT_BASE_URL = oldBase;
    if (oldFlag === undefined) delete process.env.BOOKTKIT_ALLOW_MUTATIONS;
    else process.env.BOOKTKIT_ALLOW_MUTATIONS = oldFlag;
  }
});
