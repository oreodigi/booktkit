import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { z } from 'zod';
import { runSuite } from './runner.js';

const server = new McpServer({ name: 'booktkit-devtools', version: '0.1.0' });

server.tool(
  'booktkit_run_tests',
  'Run an approved BookTKIT Playwright suite. Read-only browser tests by default; no production data mutation.',
  { suite: z.enum(['smoke', 'mobile', 'all']).default('smoke') },
  async ({ suite }) => {
    const result = await runSuite(suite);
    return { content: [{ type: 'text', text: JSON.stringify(result, null, 2) }], isError: !result.ok };
  }
);

server.tool(
  'booktkit_health_check',
  'Run BookTKIT public smoke checks against BOOKTKIT_BASE_URL.',
  {},
  async () => {
    const result = await runSuite('smoke');
    return { content: [{ type: 'text', text: JSON.stringify(result, null, 2) }], isError: !result.ok };
  }
);

await server.connect(new StdioServerTransport());
