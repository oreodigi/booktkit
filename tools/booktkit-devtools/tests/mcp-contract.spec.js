import { test, expect } from '@playwright/test';
import { allowedSuites } from '../src/runner.js';

test('MCP suite registry exposes required BookTKIT capabilities', async () => {
  for (const suite of ['login','signup','organizer','event-creation','checkout','mobile','diagnostics','all']) {
    expect(allowedSuites.has(suite), `missing suite: ${suite}`).toBeTruthy();
  }
});
