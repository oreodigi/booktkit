import fs from 'node:fs/promises';
import { runSuite } from './runner.js';

const suite = process.argv[2] || 'smoke';
const startedAt = new Date().toISOString();
const result = await runSuite(suite);
const summary = {
  suite,
  baseURL: process.env.BOOKTKIT_BASE_URL || 'https://www.booktkit.com',
  startedAt,
  finishedAt: new Date().toISOString(),
  ...result
};
await fs.writeFile('test-summary.json', JSON.stringify(summary, null, 2));
process.stdout.write(JSON.stringify(summary, null, 2) + '\n');
process.exit(result.ok ? 0 : 1);
