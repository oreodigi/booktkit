import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { z } from 'zod';
import { runSuite } from './runner.js';

const server = new McpServer({ name: 'booktkit-devtools', version: '0.2.0' });
const suites = ['smoke','mobile','auth','organizer','api','security','razorpay','scanner','contracts','all'];

function response(result) {
  return { content: [{ type: 'text', text: JSON.stringify(result, null, 2) }], isError: !result.ok };
}
function register(name, description, suite) {
  server.tool(name, description, {}, async () => response(await runSuite(suite)));
}

server.tool(
  'booktkit_run_tests',
  'Run an approved BookTKIT Playwright suite against BOOKTKIT_BASE_URL. Production is read-only.',
  { suite: z.enum(suites).default('smoke') },
  async ({ suite }) => response(await runSuite(suite))
);

register('booktkit_health_check', 'Check public BookTKIT pages and core smoke coverage.', 'smoke');
register('booktkit_test_auth', 'Test current customer and organizer authentication surfaces.', 'auth');
register('booktkit_test_mobile', 'Test BookTKIT responsive/mobile browser behavior.', 'mobile');
register('booktkit_test_api', 'Test the current Laravel API surface.', 'api');
register('booktkit_test_app_contracts', 'Test API contracts used by customer, organizer and scanner apps.', 'contracts');
register('booktkit_test_organizer', 'Test current organizer and Online/Venue event surfaces.', 'organizer');
register('booktkit_test_razorpay', 'Test BookTKIT Razorpay v1 validation and safe test-mode coverage.', 'razorpay');
register('booktkit_test_scanner', 'Test scanner API authentication and QR endpoint contracts without production admissions.', 'scanner');
register('booktkit_test_security', 'Test anonymous boundaries and production mutation guards.', 'security');

await server.connect(new StdioServerTransport());
