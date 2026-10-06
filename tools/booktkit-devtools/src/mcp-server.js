import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { z } from 'zod';
import { startSuite, getRunStatus, getRunLog, allowedSuites } from './runner.js';

const server = new McpServer({ name: 'booktkit-devtools', version: '1.0.0' });
const suites = [...allowedSuites];

function response(result) {
  return { content: [{ type:'text', text:JSON.stringify(result, null, 2) }], isError:!result.ok };
}
function register(name, description, suite) {
  server.tool(name, description, {}, async () => response(startSuite(suite)));
}

server.tool('start_suite', 'Start a bounded background test run and return its run_id immediately.', {suite:z.enum(suites).default('smoke')}, async ({suite}) => response(startSuite(suite)));
server.tool('get_run_status', 'Get test progress and real result counts.', {run_id:z.string()}, async ({run_id}) => response(getRunStatus(run_id)));
server.tool('get_run_log', 'Read a bounded log tail.', {run_id:z.string(),tail:z.number().int().min(1).max(500).default(100)}, async ({run_id,tail}) => response({ok:true,...getRunLog(run_id,tail)}));

server.tool('booktkit_run_tests',
  'Run an approved BookTKIT Playwright suite against BOOKTKIT_BASE_URL. Production is read-only.',
  { suite:z.enum(suites).default('smoke') },
  async ({ suite }) => response(startSuite(suite))
);

register('booktkit_health_check', 'Check public BookTKIT pages and smoke coverage.', 'smoke');
register('booktkit_test_login', 'Test customer and organizer login surfaces.', 'login');
register('booktkit_test_signup', 'Test customer and organizer signup/registration surfaces.', 'signup');
register('booktkit_test_auth', 'Test current customer and organizer authentication surfaces.', 'auth');
register('booktkit_test_mobile', 'Test responsive/mobile browser behavior.', 'mobile');
register('booktkit_test_api', 'Test the current Laravel API surface.', 'api');
register('booktkit_test_app_contracts', 'Test customer, organizer and scanner API contracts.', 'contracts');
register('booktkit_test_organizer_flow', 'Test organizer capabilities and approved organizer flows.', 'organizer');
register('booktkit_test_event_creation', 'Test event creation; mutation remains blocked on production.', 'event-creation');
register('booktkit_test_pos', 'Test authenticated Box Office POS, reports and shifts on isolated staging.', 'pos');
register('booktkit_test_staff_rbac', 'Test organizer workforce surfaces and staff authorization boundaries.', 'staff-rbac');
register('booktkit_test_admission', 'Test organizer/staff admission boundaries. Full issued-ticket lifecycle requires seeded staging fixtures.', 'admission');
register('booktkit_test_checkout', 'Test checkout and payment contracts; live charges are never executed on production.', 'checkout');
register('booktkit_test_razorpay', 'Test BookTKIT Razorpay validation/test-mode coverage.', 'razorpay');
register('booktkit_test_scanner', 'Test scanner API and QR contracts without production admissions.', 'scanner');
register('booktkit_test_security', 'Test anonymous boundaries and production mutation guards.', 'security');
register('booktkit_get_runtime_errors', 'Capture console, page and network failure diagnostics from key routes.', 'diagnostics');
register('booktkit_run_regression', 'Run the complete approved BookTKIT regression suite.', 'all');

await server.connect(new StdioServerTransport());
