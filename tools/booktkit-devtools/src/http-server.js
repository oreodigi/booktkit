import http from 'node:http';
import { createRemoteServer } from './remote-mcp-server.js';
import { healthPayload } from './http-health.js';

const port = Number(process.env.PORT || 8787);

function sendJson(res, status, body) {
  res.writeHead(status, { 'content-type': 'application/json', 'cache-control': 'no-store' });
  res.end(JSON.stringify(body));
}
function isAuthorized(req) {
  const expected = process.env.BOOKTKIT_MCP_TOKEN;
  const supplied = (req.headers.authorization || '').replace(/^Bearer\s+/i, '');
  return Boolean(expected && supplied && supplied === expected);
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url || '/', 'http://localhost');
  if (req.method === 'GET' && url.pathname === '/health') return sendJson(res, 200, healthPayload());
  if (url.pathname !== '/mcp') return sendJson(res, 404, { error: 'not_found' });
  if (!isAuthorized(req)) return sendJson(res, 401, { error: 'unauthorized' });

  try {
    const { StreamableHTTPServerTransport } = await import('@modelcontextprotocol/sdk/server/streamableHttp.js');
    const mcp = createRemoteServer();
    const transport = new StreamableHTTPServerTransport({ sessionIdGenerator: undefined, enableJsonResponse: true });
    res.on('close', () => { transport.close().catch(() => {}); mcp.close().catch(() => {}); });
    await mcp.connect(transport);
    await transport.handleRequest(req, res);
  } catch (error) {
    if (!res.headersSent) sendJson(res, 500, { error: 'mcp_request_failed' });
  }
});

server.requestTimeout = 30000;
server.headersTimeout = 10000;
server.listen(port, '0.0.0.0');
