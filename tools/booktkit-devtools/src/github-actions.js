import {randomUUID} from 'node:crypto';
const API='https://api.github.com/repos/oreodigi/booktkit';
const suites=new Set(['smoke','contracts','mobile','auth','signup','login','organizer','event-creation','checkout','api','security','razorpay','scanner','diagnostics','all']);
async function api(path, options={},signal=AbortSignal.timeout(20_000)) {
 const token=process.env.BOOKTKIT_GITHUB_TOKEN;if(!token)throw new Error('GitHub token is not configured');
 const response=await fetch(API+path,{...options,headers:{Accept:'application/vnd.github+json',Authorization:`Bearer ${token}`,'X-GitHub-Api-Version':'2022-11-28','User-Agent':'booktkit-devtools'},signal});
 if(!response.ok)throw new Error(`GitHub API returned HTTP ${response.status}`);
 return response;
}
export async function dispatchSuite({suite='smoke',baseUrl='https://test.booktkit.com',ref='main'}={}){
 if(!suites.has(suite))throw new Error('Unsupported suite');
 if(baseUrl!=='https://test.booktkit.com')throw new Error('Remote suites require isolated staging');
 const run_id=randomUUID();
 await api('/actions/workflows/booktkit-e2e.yml/dispatches',{method:'POST',body:JSON.stringify({ref,inputs:{suite,base_url:baseUrl,request_id:run_id}})});
 return {ok:true,run_id,status:'queued',suite,baseUrl,ref};
}
export async function recentRuns({limit=10}={}){
 const response=await api(`/actions/workflows/booktkit-e2e.yml/runs?per_page=${Math.min(limit,100)}`);
 const data=await response.json();return data.workflow_runs.map(x=>({id:x.id,title:x.display_title,status:x.status,conclusion:x.conclusion,html_url:x.html_url,head_sha:x.head_sha,created_at:x.created_at}));
}
async function findRun(id,signal){
 if(!/^(?:[0-9]+|[a-f0-9-]{36})$/.test(id))throw new Error('Invalid run_id');
 if(/^\d+$/.test(id))return (await api('/actions/runs/'+id,{},signal)).json();
 const data=await (await api('/actions/workflows/booktkit-e2e.yml/runs?per_page=100',{},signal)).json();
 return data.workflow_runs.find(x=>x.display_title?.includes(id));
}
export async function getRemoteRunStatus(run_id){
 const run=await findRun(run_id,AbortSignal.timeout(20_000));
 return run?{ok:true,run_id,github_run_id:run.id,status:run.status,conclusion:run.conclusion,url:run.html_url,commit:run.head_sha}:{ok:true,run_id,status:'pending_or_not_found',message:'GitHub may not have created the run yet; retry shortly.'};
}
export async function getRemoteRunLog(run_id,tail=100){
 const signal=AbortSignal.timeout(25_000),run=await findRun(run_id,signal);
 if(!run)return {ok:true,run_id,log:'Run has not appeared yet.'};
 const data=await (await api(`/actions/runs/${run.id}/jobs`,{},signal)).json();
 const job=data.jobs.find(x=>x.status==='completed');
 if(!job)return {ok:true,run_id,log:'GitHub publishes downloadable logs after the job completes.',jobs:data.jobs.map(x=>({name:x.name,status:x.status,conclusion:x.conclusion}))};
 const response=await api(`/actions/jobs/${job.id}/logs`,{},signal);
 // Bounded memory even if a job produces a large log; secrets are masked by Actions.
 const reader=response.body.getReader(),decoder=new TextDecoder();let text='';
 while(true){const {done,value}=await reader.read();if(done)break;text=(text+decoder.decode(value,{stream:true})).slice(-65536);}
 return {ok:true,run_id,log:text.split('\n').slice(-Math.max(1,Math.min(500,tail))).join('\n')};
}
