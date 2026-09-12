export class ApiError extends Error {constructor(message:string,public status:number){super(message);}}
let csrf='';
export async function token(){const r=await fetch('/api/v1/csrf',{credentials:'same-origin',headers:{Accept:'application/json'}});if(!r.ok)throw new Error('Cannot initialise session. Check the Laravel server.');csrf=(await r.json()).token;}
export async function api<T>(path:string,method='GET',body?:unknown):Promise<T>{
 if(method!=='GET')await token();
 let r:Response;
 try {r=await fetch('/api/v1'+path,{method,credentials:'same-origin',headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},body:body===undefined?undefined:JSON.stringify(body)});}
 catch{throw new Error('Cannot reach the server. Check Laravel on port 8080.');}
 if(r.status===204)return undefined as T;
 const d=await r.json().catch(()=>null);
 if(!r.ok)throw new ApiError(d?.errors?Object.values(d.errors).flat().join(' '):d?.message||'Request failed ('+r.status+').',r.status);
 if(d===null)throw new Error('Unexpected server response.');
 return d;
}

