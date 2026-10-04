import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);

export const allowedSuites = new Set(['all','smoke','mobile','auth','signup','login','organizer','event-creation','checkout','api','security','razorpay','scanner','contracts','diagnostics']);

const suiteArgs = {
  smoke: ['--grep', '@smoke'],
  mobile: ['--project=mobile-chromium', '--project=mobile-webkit'],
  auth: ['tests/organizer-auth.spec.js', 'tests/customer-auth.spec.js'],
  signup: ['tests/customer-auth.spec.js', 'tests/organizer-auth.spec.js', '--grep', 'signup|register'],
  login: ['tests/customer-auth.spec.js', 'tests/organizer-auth.spec.js', '--grep', 'login|sign in'],
  organizer: ['tests/organizer-capabilities.spec.js', 'tests/staging-event.spec.js'],
  'event-creation': ['tests/staging-event.spec.js'],
  checkout: ['tests/razorpay.spec.js', 'tests/razorpay-contract.spec.js'],
  api: ['tests/api-surface.spec.js'],
  security: ['tests/security-boundaries.spec.js', 'tests/mutation-guard.spec.js'],
  razorpay: ['tests/razorpay.spec.js', 'tests/razorpay-contract.spec.js'],
  scanner: ['tests/scanner-contract.spec.js'],
  contracts: ['tests/mobile-api-contract.spec.js', 'tests/razorpay-contract.spec.js', 'tests/scanner-contract.spec.js'],
  diagnostics: ['tests/runtime-diagnostics.spec.js']
};

export function runSuite(suite = 'smoke') {
  if (!allowedSuites.has(suite)) throw new Error('Unsupported suite');
  const args = [require.resolve('@playwright/test/cli'), 'test', ...(suiteArgs[suite] || [])];
  return new Promise((resolve) => {
    const child = spawn(process.execPath, args, { cwd: new URL('..', import.meta.url), env: process.env, shell: false });
    let stdout = '', stderr = '';
    child.stdout.on('data', d => stdout += d);
    child.stderr.on('data', d => stderr += d);
    child.on('error', error => resolve({ ok:false, code:null, stdout, stderr:error.message }));
    child.on('close', code => resolve({ ok:code===0, code, stdout:stdout.slice(-12000), stderr:stderr.slice(-12000) }));
  });
}
