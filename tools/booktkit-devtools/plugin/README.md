# BookTKIT ChatGPT Plugin Package

This directory is the ChatGPT-facing package for BookTKIT QA.

## Components
1. `SKILL.md` — BookTKIT-specific testing instructions and tool routing.
2. Remote MCP — run `npm run mcp:http`; authenticated endpoint is `POST /mcp`, health is `GET /health`.
3. GitHub Actions — remote MCP dispatches only allow-listed Playwright suites.

## Required secrets
- `BOOKTKIT_MCP_TOKEN`: bearer token protecting the MCP endpoint.
- `BOOKTKIT_GITHUB_TOKEN`: fine-grained token restricted to `oreodigi/booktkit` with Actions read/write and metadata read.

## Connect
Deploy the Node service on an HTTPS host, set the two secrets, verify `/health`, then add the HTTPS `/mcp` endpoint to ChatGPT as the BookTKIT testing MCP. Attach/use the instructions in `SKILL.md`.

Do not expose the MCP endpoint without authentication and do not put either token in the repository.
