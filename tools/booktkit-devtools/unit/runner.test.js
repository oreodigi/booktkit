import {test} from 'node:test';
import assert from 'node:assert/strict';
import {startSuite,getRunStatus,getRunLog} from '../src/runner.js';
test('async start returns immediately and a hard timeout ends the process',async()=>{
 process.env.BOOKTKIT_BASE_URL='https://test.booktkit.com';
 process.env.BOOKTKIT_RUN_TIMEOUT_MINUTES='0.001';
 const started=Date.now(),run=startSuite('smoke');
 assert.ok(Date.now()-started<1000);
 assert.equal(getRunStatus(run.run_id).status,'running');
 assert.equal(typeof getRunLog(run.run_id).log,'string');
 const until=Date.now()+3000;
 while(getRunStatus(run.run_id).status==='running'&&Date.now()<until)await new Promise(resolve=>setTimeout(resolve,20));
 assert.equal(getRunStatus(run.run_id).status,'timed_out');
 process.env.BOOKTKIT_BASE_URL='https://booktkit.com';
 assert.throws(()=>startSuite('all'),/read-only smoke/);
 delete process.env.BOOKTKIT_RUN_TIMEOUT_MINUTES;
});
