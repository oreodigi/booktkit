import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { dispatchSuite, recentRuns } from './github-actions.js';

const suites = ['smoke','contracts','mobile','auth','signup','login','organizer','event-creation','checkout','api','security','razorpay','scanner','diagnostics','all'];

export function createRemoteServer() {
  const server = new McpServer({ name:'booktkit-testing', version:'1.0.0' });

  server.tool('booktkit_start_test_run',
    'Start an approved BookTKIT GitHub Actions test suite. Production runs are read-only.',
    { suite:z.enum(suites).default('smoke'), baseUrl:z.string().url().default('https://www.booktkit.com') },
    async ({ suite, baseUrl }) => ({ content:[{type:'text',text:JSON.stringify(await dispatchSuite({suite,baseUrl}),null,2)}] })
  );

  const fixed = [
    ['booktkit_test_login','login'], ['booktkit_test_signup','signup'], ['booktkit_test_organizer_flow','organizer'],
    ['booktkit_test_event_creation','event-creation'], ['booktkit_test_checkout','checkout'], ['booktkit_test_mobile','mobile'],
    ['booktkit_run_regression','all'], ['booktkit_get_runtime_errors','diagnostics']
  ];
  for (const [name,suite] of fixed) server.tool(name, `Run BookTKIT ${suite} testing through the protected CI control plane.`,
    { baseUrl:z.string().url().default('https://www.booktkit.com') },
    async ({baseUrl}) => ({content:[{type:'text',text:JSON.stringify(await dispatchSuite({suite,baseUrl}),null,2)}]})
  );

  server.tool('booktkit_recent_test_runs','List recent BookTKIT testing workflow runs.',
    {limit:z.number().int().min(1).max(25).default(10)},
    async ({limit}) => ({content:[{type:'text',text:JSON.stringify(await recentRuns({limit}),null,2)}]})
  );
  return server;
}
