import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { dispatchSuite, recentRuns } from './github-actions.js';

export function createRemoteServer() {
  const server = new McpServer({ name: 'booktkit-remote-devtools', version: '0.3.0' });

  server.tool('booktkit_start_test_run',
    'Start an approved BookTKIT GitHub Actions test suite. Production runs are read-only.',
    {
      suite: z.enum(['smoke','contracts','mobile','auth','organizer','api','security','razorpay','scanner','all']).default('smoke'),
      baseUrl: z.string().url().default('https://www.booktkit.com')
    },
    async ({ suite, baseUrl }) => ({
      content: [{ type:'text', text: JSON.stringify(await dispatchSuite({ suite, baseUrl }), null, 2) }]
    })
  );

  server.tool('booktkit_recent_test_runs',
    'List recent BookTKIT DevTools workflow runs.',
    { limit: z.number().int().min(1).max(25).default(10) },
    async ({ limit }) => ({
      content: [{ type:'text', text: JSON.stringify(await recentRuns({ limit }), null, 2) }]
    })
  );

  return server;
}
