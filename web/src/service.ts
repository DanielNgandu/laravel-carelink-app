export type Status = 'due_soon' | 'missed' | 'overdue';
export type Patient = {id:string;patient_number:string;first_name:string;last_name:string;facility_id:string;facility_name:string;missed_appointment_date:string;days_overdue:number;follow_up_status:Status;phone_number:string|null;last_contact_attempt:string|null};
export type Page = {page:number;page_size:number;total:number;items:Patient[]};
export type Filters = {facility_id:string;status:Status;sort:'days_overdue_asc'|'days_overdue_desc';page:number;page_size:number;overdue_days:number};
export async function getFollowUps(filters:Filters, token:string, signal?:AbortSignal):Promise<Page>{
 const query = new URLSearchParams(Object.entries(filters).map(([k,v])=>[k,String(v)]));
 if (!filters.facility_id) query.delete('facility_id');
 const response=await fetch(`/api/follow-up?${query}`,{headers:{Authorization:`Bearer ${token}`,Accept:'application/json'},signal});
 const json=await response.json();
 if(!response.ok) throw new Error(json?.error?.message || `Request failed (${response.status})`);
 return json as Page;
}
export async function markContacted(patientId:string,token:string,note:string):Promise<void>{
 const response=await fetch(`/api/follow-up/${encodeURIComponent(patientId)}/contacted`,{method:'POST',headers:{Authorization:`Bearer ${token}`,'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify({contacted_at:new Date().toISOString(),note,interaction_id:crypto.randomUUID()})});
 if(!response.ok){const json=await response.json();throw new Error(json?.error?.message || 'Could not record contact');}
}
