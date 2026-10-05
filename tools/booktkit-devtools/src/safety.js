import { targetConfig } from './environment.js';
export function assertMutationAllowed() {
  const target = targetConfig();
  if (target.production) throw new Error('Mutating tests are blocked on BookTKIT production.');
  if (target.baseURL !== 'https://test.booktkit.com') throw new Error('An approved HTTPS staging origin is required.');
  if (process.env.BOOKTKIT_ALLOW_MUTATIONS !== 'true') throw new Error('Mutating tests are disabled.');
  return true;
}
