import { spawn } from 'node:child_process';
import { createRequire } from 'node:module';
import fs from 'node:fs';
import path from 'node:path';
import { randomUUID } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { targetConfig } from './environment.js';
const require = createRequire(import.meta.url);
const cwd = fileURLToPath(new URL('..', import.meta.url));
const root = path.join(cwd, 'artifacts', 'runs');
const active = new Map();
export const allowedSuites = new Set(['all','smoke','mobile','auth','signup','login','organizer','event-creation','checkout','api','security','razorpay','scanner','contracts','diagnostics']);
const suiteArgs = {
  smoke: ['tests/readonly-smoke.spec.js'],
  auth: ['tests/authenticated-role.spec.js'], login: ['tests/authenticated-role.spec.js'],
  signup: ['tests/customer-auth.spec.js','tests/organizer-auth.spec.js','--grep','signup|register'],
  organizer: ['tests/organizer-authenticated.spec.js'],
  'event-creation': ['tests/staging-event.spec.js'],
  checkout: ['tests/razorpay.spec.js','tests/razorpay-contract.spec.js'],
  api: ['tests/api-surface.spec.js'], security: ['tests/security-boundaries.spec.js','tests/mutation-guard.spec.js'],
  razorpay: ['tests/razorpay.spec.js','tests/razorpay-contract.spec.js'], scanner: ['tests/scanner-contract.spec.js'],
  contracts: ['tests/mobile-api-contract.spec.js','tests/razorpay-contract.spec.js','tests/scanner-contract.spec.js'],
  diagnostics: ['tests/runtime-diagnostics.spec.js'],
};
function directory(id) {
  if (!/^[a-f0-9-]{36}$/.test(id)) throw new Error('Invalid run_id');
  return path.join(root,id);
}
export function getRunStatus(id) {
  const file=path.join(directory(id),'status.json');
  if (!fs.existsSync(file)) throw new Error('Unknown run_id');
  return JSON.parse(fs.readFileSync(file,'utf8'));
}
export function getRunLog(id, tail=100) {
  const file=path.join(directory(id),'run.log');
  const fd=fs.openSync(file,'r');
  try {
    const size=fs.fstatSync(fd).size, buffer=Buffer.alloc(Math.min(size,64*1024));
    fs.readSync(fd,buffer,0,buffer.length,Math.max(0,size-buffer.length));
    return {run_id:id,log:buffer.toString('utf8').split('\n').slice(-Math.max(1,Math.min(tail,500))).join('\n')};
  } finally {fs.closeSync(fd);}
}
export function startSuite(suite='smoke') {
  if (!allowedSuites.has(suite)) throw new Error('Unsupported suite');
  const target=targetConfig();
  if (target.production && suite!=='smoke') throw new Error('Production permits only the read-only smoke suite');
  if (active.size) throw new Error('A test run is already active; query its status before starting another');
  const minutes=Number(process.env.BOOKTKIT_RUN_TIMEOUT_MINUTES || 15);
  if (!Number.isFinite(minutes) || minutes<=0 || minutes>20) throw new Error('Run timeout must be between 0 and 20 minutes');
  const id=randomUUID(), dir=directory(id);
  fs.mkdirSync(dir,{recursive:true,mode:0o700});
  const logFd=fs.openSync(path.join(dir,'run.log'),'w',0o600);
  const log={write:value=>fs.writeSync(logFd,value),end:()=>fs.closeSync(logFd)};
  const status={ok:true,run_id:id,suite,baseURL:target.baseURL,status:'running',startedAt:new Date().toISOString()};
  const save=()=>{const file=path.join(dir,'status.json');fs.writeFileSync(file+'.tmp',JSON.stringify(status,null,2),{mode:0o600});fs.renameSync(file+'.tmp',file);};
  save();
  const child=spawn(process.execPath,[require.resolve('@playwright/test/cli'),'test',...(suiteArgs[suite]||[])],{
    cwd,env:{...process.env,BOOKTKIT_SUITE:suite,BOOKTKIT_RUN_DIR:dir},shell:false,detached:process.platform!=='win32',stdio:['ignore','pipe','pipe']});
  const secrets=Object.entries(process.env).filter(([k,v])=>/PASSWORD|TOKEN|SECRET|API_KEY/.test(k)&&v.length>=6).map(([,v])=>v);
  // Keep a bounded suffix so credentials split across chunks are still redacted.
  function stream(source){let pending='';const keep=Math.max(1,...secrets.map(x=>x.length));
    source.on('data',chunk=>{pending+=chunk.toString();for(const s of secrets) pending=pending.split(s).join('[REDACTED]');if(pending.length>keep){log.write(pending.slice(0,-keep));pending=pending.slice(-keep);}});
    source.on('end',()=>{for(const s of secrets)pending=pending.split(s).join('[REDACTED]');log.write(pending);});}
  stream(child.stdout);stream(child.stderr);
  let timedOut=false,finished=false;
  const kill=()=>{try {if(process.platform==='win32')spawn('taskkill',['/pid',String(child.pid),'/T','/F']);else process.kill(-child.pid,'SIGKILL');}catch{}};
  const timer=setTimeout(()=>{timedOut=true;kill();},minutes*60_000);
  const done=new Promise(resolve=>{
    const finish=(code,error)=>{if(finished)return;finished=true;clearTimeout(timer);kill();log.end();
      Object.assign(status,{ok:code===0&&!timedOut&&!error,status:timedOut?'timed_out':error?'error':code===0?'passed':'failed',code,finishedAt:new Date().toISOString(),error:error?.message});
      try{const r=JSON.parse(fs.readFileSync(path.join(dir,'results.json'),'utf8'));status.counts={passed:r.stats.expected,failed:r.stats.unexpected,flaky:r.stats.flaky,skipped:r.stats.skipped};}catch{}
      save();active.delete(id);resolve({...status,...getRunLog(id,80)});};
    child.on('error',e=>finish(null,e));child.on('close',code=>finish(code));
  });
  active.set(id,done);return {...status};
}
export async function runSuite(suite='smoke'){const run=startSuite(suite);return await active.get(run.run_id)||getRunStatus(run.run_id);}
