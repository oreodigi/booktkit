export function attachDiagnostics(page) {
  const consoleErrors = [];
  const pageErrors = [];
  const failedRequests = [];
  page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
  page.on('pageerror', err => pageErrors.push(err.message));
  page.on('requestfailed', req => failedRequests.push({
    method: req.method(), url: req.url(), error: req.failure()?.errorText || 'request failed'
  }));
  return {
    snapshot: () => ({ consoleErrors, pageErrors, failedRequests }),
    assertClean: ({ allowConsole = [] } = {}) => {
      const unexpected = consoleErrors.filter(x => !allowConsole.some(p => x.includes(p)));
      if (pageErrors.length || unexpected.length || failedRequests.length) {
        throw new Error(JSON.stringify({ consoleErrors: unexpected, pageErrors, failedRequests }, null, 2));
      }
    }
  };
}
