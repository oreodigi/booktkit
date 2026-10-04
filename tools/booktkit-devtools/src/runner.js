import { spawn } from 'node:child_process';

const allowedSuites = new Set(['all', 'smoke', 'mobile', 'auth', 'organizer', 'api', 'security', 'razorpay', 'scanner', 'contracts']);

export function runSuite(suite = 'smoke') {
  if (!allowedSuites.has(suite)) throw new Error('Unsupported suite');
  const args = ['playwright', 'test'];
  if (suite === 'smoke') args.push('--grep', '@smoke');
  if (suite === 'mobile') args.push('--project=mobile-chrome');
  if (suite === 'auth') args.push('tests/organizer-auth.spec.js', 'tests/customer-auth.spec.js');
  if (suite === 'organizer') args.push('tests/organizer-capabilities.spec.js', 'tests/staging-event.spec.js');
  if (suite === 'api') args.push('tests/api-surface.spec.js');
  if (suite === 'security') args.push('tests/security-boundaries.spec.js', 'tests/mutation-guard.spec.js');
  if (suite === 'razorpay') args.push('tests/razorpay.spec.js', 'tests/razorpay-contract.spec.js');
  if (suite === 'scanner') args.push('tests/scanner-contract.spec.js');
  if (suite === 'contracts') args.push('tests/mobile-api-contract.spec.js', 'tests/razorpay-contract.spec.js', 'tests/scanner-contract.spec.js');

  return new Promise((resolve) => {
    const child = spawn(process.platform === 'win32' ? 'npx.cmd' : 'npx', args, {
      cwd: new URL('..', import.meta.url),
      env: process.env,
      shell: false
    });
    let stdout = '', stderr = '';
    child.stdout.on('data', d => stdout += d);
    child.stderr.on('data', d => stderr += d);
    child.on('close', code => resolve({ ok: code === 0, code, stdout: stdout.slice(-12000), stderr: stderr.slice(-12000) }));
  });
}
