import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { z } from 'zod';
import { dispatchSuite, recentRuns, getRemoteRunStatus, getRemoteRunLog } from './github-actions.js';

const suites = ['smoke','contracts','mobile','auth','signup','login','organizer','event-creation','pos','staff-rbac','admission','checkout','api','security','razorpay','scanner','diagnostics','all'];

export function createRemoteServer() {
  const server = new McpServer({ name:'booktkit-testing', version:'1.0.0' });

  const output = result => ({content:[{type:'text',text:JSON.stringify(result,null,2)}]});
  server.tool('start_suite','Start staging CI and return a correlation run_id immediately.',{suite:z.enum(suites).default('smoke')},async({suite})=>output(await dispatchSuite({suite})));
  server.tool('get_run_status','Check a test run without waiting for it to finish.',{run_id:z.string()},async({run_id})=>output(await getRemoteRunStatus(run_id)));
  server.tool('get_run_log','Get available CI log tail.',{run_id:z.string(),tail:z.number().int().min(1).max(500).default(100)},async({run_id,tail})=>output(await getRemoteRunLog(run_id,tail)));
  server.tool('booktkit_start_test_run',
    'Start an approved BookTKIT GitHub Actions test suite. Production runs are read-only.',
    { suite:z.enum(suites).default('smoke'), baseUrl:z.string().url().default('https://test.booktkit.com') },
    async ({ suite, baseUrl }) => ({ content:[{type:'text',text:JSON.stringify(await dispatchSuite({suite,baseUrl}),null,2)}] })
  );

  const fixed = [
    ['booktkit_test_login','login'], ['booktkit_test_signup','signup'], ['booktkit_test_organizer_flow','organizer'],
    ['booktkit_test_event_creation','event-creation'], ['booktkit_test_pos','pos'], ['booktkit_test_staff_rbac','staff-rbac'], ['booktkit_test_admission','admission'], ['booktkit_test_checkout','checkout'], ['booktkit_test_mobile','mobile'],
    ['booktkit_run_regression','all'], ['booktkit_get_runtime_errors','diagnostics']
  ];
  for (const [name,suite] of fixed) server.tool(name, `Run BookTKIT ${suite} testing through the protected CI control plane.`,
    { baseUrl:z.string().url().default('https://test.booktkit.com') },
    async ({baseUrl}) => ({content:[{type:'text',text:JSON.stringify(await dispatchSuite({suite,baseUrl}),null,2)}]})
  );

  server.tool('booktkit_recent_test_runs','List recent BookTKIT testing workflow runs.',
    {limit:z.number().int().min(1).max(25).default(10)},
    async ({limit}) => ({content:[{type:'text',text:JSON.stringify(await recentRuns({limit}),null,2)}]})
  );
  return server;
}
