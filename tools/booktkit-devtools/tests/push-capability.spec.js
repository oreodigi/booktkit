import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import vm from 'node:vm';

test('push setup does not register or throw when browser capabilities are absent', () => {
  const script = fs.readFileSync('../../source/website/public/assets/front/js/pwa.js', 'utf8');
  for (const missing of ['serviceWorker', 'PushManager', 'Notification']) {
    let registrations = 0;
    const window = { PushManager: {}, Notification: {}, addEventListener() {} };
    const navigator = { serviceWorker: { register() { registrations++; throw new Error('unexpected registration'); } } };
    if (missing === 'serviceWorker') delete navigator.serviceWorker;
    else delete window[missing];
    const context = vm.createContext({ window, navigator });
    expect(() => vm.runInContext(script, context), missing).not.toThrow();
    expect(registrations, missing).toBe(0);
    if (missing === 'Notification' || missing === 'serviceWorker') {
      expect(() => vm.runInContext('initPush()', context), missing).not.toThrow();
    }
  }
});
