const expected = process.env.EXPECTED_SHA;
if (!/^[a-f0-9]{40}$/.test(expected || '')) throw new Error('Valid EXPECTED_SHA required');
const username = process.env.BOOKTKIT_STAGING_HTTP_USERNAME;
const password = process.env.BOOKTKIT_STAGING_HTTP_PASSWORD;
if (!username || !password) throw new Error('Staging HTTP credentials required');
const deadline = Date.now() + (process.argv.includes('--wait') ? 300000 : 1);
do {
  try {
    const response = await fetch('https://test.booktkit.com/__deployment.json?gate=' + expected + '&t=' + Date.now(), {
      headers: {Authorization: 'Basic ' + Buffer.from(username + ':' + password).toString('base64'), 'Cache-Control': 'no-cache'},
      signal: AbortSignal.timeout(10000)
    });
    if (!response.ok) throw new Error('Marker HTTP ' + response.status);
    const marker = await response.json();
    if (marker.commit === expected && marker.target === 'staging') {
      console.log(JSON.stringify({expected, deployed: marker.commit, target: marker.target, verified: true}));
      process.exit(0);
    }
    console.log('Waiting for expected staging commit ' + expected);
  } catch { console.log('Staging marker not ready'); }
  if (Date.now() >= deadline) break;
  await new Promise(resolve => setTimeout(resolve, 5000));
} while (Date.now() < deadline);
throw new Error('Staging marker did not match expected commit; refusing tests');
