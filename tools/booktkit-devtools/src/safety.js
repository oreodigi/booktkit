export function assertMutationAllowed() {
  const base = process.env.BOOKTKIT_BASE_URL || 'https://www.booktkit.com';
  const enabled = process.env.BOOKTKIT_ALLOW_MUTATIONS === 'true';
  let url;
  try { url = new URL(base); }
  catch { throw new Error('BOOKTKIT_BASE_URL must be a valid absolute URL'); }

  if (!enabled) throw new Error('Mutating tests are disabled. Enable them only for the isolated staging environment.');
  if (url.protocol !== 'https:' || url.hostname.toLowerCase() !== 'test.booktkit.com') {
    throw new Error('Mutating tests are allowed only on https://test.booktkit.com.');
  }
  return true;
}
