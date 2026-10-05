export const stagingURL = 'https://test.booktkit.com';
export function targetConfig(env = process.env) {
  const baseURL = env.BOOKTKIT_BASE_URL || stagingURL;
  const url = new URL(baseURL);
  const production = ['booktkit.com', 'www.booktkit.com'].includes(url.hostname);
  if (url.protocol !== 'https:' || url.username || url.password || url.port || url.search || url.hash || url.pathname !== '/' ||
      !['booktkit.com', 'www.booktkit.com', 'test.booktkit.com'].includes(url.hostname)) {
    throw new Error('Only the exact HTTPS BookTKIT production/staging origins are allowed.');
  }
  if (production && !env.BOOKTKIT_BASE_URL) throw new Error('Production must be explicitly selected.');
  const httpCredentials = !production && env.BOOKTKIT_STAGING_HTTP_USERNAME && env.BOOKTKIT_STAGING_HTTP_PASSWORD ? {
    username: env.BOOKTKIT_STAGING_HTTP_USERNAME, password: env.BOOKTKIT_STAGING_HTTP_PASSWORD, origin: url.origin
  } : undefined;
  return { baseURL: url.origin, production, httpCredentials };
}
