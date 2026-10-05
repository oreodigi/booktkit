import { test, expect } from '@playwright/test';
import { assertMutationAllowed } from '../src/safety.js';
test('production and unapproved origins reject mutation permission',()=>{
 const old={...process.env};
 try {
  process.env.BOOKTKIT_ALLOW_MUTATIONS='true';
  for(const url of ['https://booktkit.com','https://www.booktkit.com','https://evil.example','https://test.booktkit.com.evil.example']) {
   process.env.BOOKTKIT_BASE_URL=url;expect(()=>assertMutationAllowed()).toThrow();
  }
  process.env.BOOKTKIT_BASE_URL='https://test.booktkit.com';expect(()=>assertMutationAllowed()).not.toThrow();
  process.env.BOOKTKIT_ALLOW_MUTATIONS='false';expect(()=>assertMutationAllowed()).toThrow();
 } finally {for(const k of ['BOOKTKIT_BASE_URL','BOOKTKIT_ALLOW_MUTATIONS']){if(old[k]===undefined)delete process.env[k];else process.env[k]=old[k];}}
});
