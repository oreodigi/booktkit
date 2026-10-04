const OWNER = 'oreodigi';
const REPO = 'booktkit';
const WORKFLOW = 'booktkit-e2e.yml';
const ALLOWED_SUITES = new Set(['smoke','contracts','mobile','auth','organizer','api','security','razorpay','scanner','all']);

function headers() {
  const token = process.env.BOOKTKIT_GITHUB_TOKEN;
  if (!token) throw new Error('BOOKTKIT_GITHUB_TOKEN is not configured');
  return {
    Accept: 'application/vnd.github+json',
    Authorization: `Bearer ${token}`,
    'X-GitHub-Api-Version': '2022-11-28',
    'User-Agent': 'booktkit-devtools'
  };
}

function validateTarget(baseUrl) {
  const u = new URL(baseUrl);
  if (u.protocol !== 'https:') throw new Error('HTTPS target required');
  if (!(u.hostname === 'booktkit.com' || u.hostname === 'www.booktkit.com' || u.hostname.endsWith('.booktkit.com'))) {
    throw new Error('Target must be booktkit.com or a BookTKIT subdomain');
  }
  return u.toString().replace(/\/$/, '');
}

export async function dispatchSuite({ suite='smoke', baseUrl='https://www.booktkit.com', ref='main' }={}) {
  if (!ALLOWED_SUITES.has(suite)) throw new Error('Unsupported suite');
  const target = validateTarget(baseUrl);
  const r = await fetch(`https://api.github.com/repos/${OWNER}/${REPO}/actions/workflows/${WORKFLOW}/dispatches`, {
    method: 'POST', headers: headers(),
    body: JSON.stringify({ ref, inputs: { suite, base_url: target } })
  });
  if (r.status !== 204) throw new Error(`GitHub dispatch failed: ${r.status} ${await r.text()}`);
  return { ok: true, suite, baseUrl: target, ref };
}

export async function recentRuns({ branch='main', limit=10 }={}) {
  const q = new URLSearchParams({ branch, event: 'workflow_dispatch', per_page: String(Math.min(limit, 25)) });
  const r = await fetch(`https://api.github.com/repos/${OWNER}/${REPO}/actions/workflows/${WORKFLOW}/runs?${q}`, { headers: headers() });
  if (!r.ok) throw new Error(`GitHub runs lookup failed: ${r.status} ${await r.text()}`);
  const data = await r.json();
  return (data.workflow_runs || []).map(x => ({
    id: x.id, status: x.status, conclusion: x.conclusion, html_url: x.html_url,
    created_at: x.created_at, updated_at: x.updated_at, head_sha: x.head_sha
  }));
}
