import { spawn } from 'node:child_process';

const allowedSuites = new Set(['all', 'smoke', 'mobile', 'auth']);

export function runSuite(suite = 'smoke') {
  if (!allowedSuites.has(suite)) throw new Error('Unsupported suite');
  const args = ['playwright', 'test'];
  if (suite === 'smoke') args.push('--grep', '@smoke');
  if (suite === 'mobile') args.push('--project=mobile-chrome');
  if (suite === 'auth') args.push('tests/organizer-auth.spec.js', 'tests/customer-auth.spec.js');

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
