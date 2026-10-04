export function assertMutationAllowed() {
  const base = process.env.BOOKTKIT_BASE_URL || 'https://www.booktkit.com';
  const enabled = process.env.BOOKTKIT_ALLOW_MUTATIONS === 'true';
  const productionHosts = new Set(['booktkit.com', 'www.booktkit.com']);
  let host;
  try { host = new URL(base).hostname.toLowerCase(); }
  catch { throw new Error('BOOKTKIT_BASE_URL must be a valid absolute URL'); }

  if (!enabled) throw new Error('Mutating tests are disabled. Set BOOKTKIT_ALLOW_MUTATIONS=true only for an approved test environment.');
  if (productionHosts.has(host)) throw new Error('Mutating tests are blocked on BookTKIT production.');
  return true;
}
