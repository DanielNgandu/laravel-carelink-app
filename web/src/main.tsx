import React,{useEffect,useState} from 'react';
import {createRoot} from 'react-dom/client';
import {getFollowUps,markContacted,type Filters,type Page} from './service';
import './style.css';
export function App(){
 const [role,setRole]=useState<'clinic'|'district'>('clinic');
 const [filters,setFilters]=useState<Filters>({facility_id:'FAC-0101',status:'overdue',sort:'days_overdue_desc',page:1,page_size:50,overdue_days:7});
 const [result,setResult]=useState<Page|null>(null);
 const [loading,setLoading]=useState(true);const [error,setError]=useState('');const [notice,setNotice]=useState('');const [retry,setRetry]=useState(0);
 const token=role==='clinic'?'demo-clinician-0101':'demo-district';
 useEffect(()=>{const abort=new AbortController();setLoading(true);setError('');
 getFollowUps(filters,token,abort.signal).then(setResult).catch(e=>{if(e.name!=='AbortError')setError(e.message);}).finally(()=>{if(!abort.signal.aborted)setLoading(false);});
 return ()=>abort.abort();},[filters,token,retry]);
 const change=(patch:Partial<Filters>)=>setFilters(f=>({...f,...patch,page:1}));
 async function contact(id:string){const note=window.prompt('Contact note (no sensitive information in this demo):');if(!note?.trim())return;
 try{await markContacted(id,token,note);setNotice('Contact recorded successfully.');setRetry(v=>v+1);}catch(e){setNotice(e instanceof Error?e.message:'Contact failed');}}
 return <main><header><h1>CareLink <span>Follow-Up</span></h1><p>Clinic appointment follow-up list</p></header>
 <section className="filters" aria-label="Follow-up filters">
 <label>Demo role <select value={role} onChange={e=>{const next=e.target.value as 'clinic'|'district';setRole(next);change({facility_id:next==='clinic'?'FAC-0101':''});}}><option value="clinic">Clinician, Mwansa</option><option value="district">District officer, read only</option></select></label>
 <label>Facility <select value={filters.facility_id} disabled={role==='clinic'} onChange={e=>change({facility_id:e.target.value})}><option value="">All facilities</option><option value="FAC-0101">Mwansa Urban Clinic</option><option value="FAC-0207">Kalemba Rural Health Centre</option><option value="FAC-0312">Chembe District Hospital</option></select></label>
 <label>Status <select value={filters.status} onChange={e=>change({status:e.target.value as Filters['status']})}><option value="overdue">Overdue</option><option value="missed">Recently missed</option><option value="due_soon">Due soon</option></select></label>
 <label>Overdue after (days) <input type="number" min="1" max="365" value={filters.overdue_days} onChange={e=>{const value=Number(e.target.value);if(value>=1&&value<=365)change({overdue_days:value});}} /></label>
 <label>Sort <select value={filters.sort} onChange={e=>change({sort:e.target.value as Filters['sort']})}><option value="days_overdue_desc">Most overdue first</option><option value="days_overdue_asc">Least overdue first</option></select></label>
 <label>Per page <select value={filters.page_size} onChange={e=>change({page_size:Number(e.target.value)})}><option>25</option><option>50</option><option>100</option></select></label>
 </section>
 <div role="status" aria-live="polite">{loading?'Loading follow-ups…':error?`Error: ${error}`:result?`${result.total.toLocaleString()} patients found`:''} {notice}</div>
 {error&&<button onClick={()=>setRetry(v=>v+1)}>Retry</button>}
 {!loading&&!error&&result&&<><div className="table-wrap"><table><caption>Patients requiring follow-up</caption><thead><tr><th scope="col">Patient</th><th scope="col">Facility</th><th scope="col">Appointment</th><th scope="col">Days overdue</th><th scope="col">Status</th><th scope="col">Last contacted</th><th scope="col">Action</th></tr></thead>
 <tbody>{result.items.map(p=><tr key={p.id}><td><strong>{p.first_name} {p.last_name}</strong><small>{p.patient_number}</small></td><td>{p.facility_name}</td><td>{p.missed_appointment_date}</td><td>{p.days_overdue}</td><td><span className={'status '+p.follow_up_status}>{p.follow_up_status.replace('_',' ')}</span></td><td>{p.last_contact_attempt??'Not contacted'}</td><td>{role==='clinic'?<button onClick={()=>contact(p.id)}>Record contact</button>:'Read only'}</td></tr>)}</tbody></table>
 {result.items.length===0&&<p>No patients match the selected filters.</p>}</div>
 <nav aria-label="Pagination" className="pages"><button disabled={filters.page===1} onClick={()=>setFilters(f=>({...f,page:f.page-1}))}>Previous</button><span>Page {filters.page} of {Math.max(1,Math.ceil(result.total/filters.page_size))}</span><button disabled={filters.page*filters.page_size>=result.total} onClick={()=>setFilters(f=>({...f,page:f.page+1}))}>Next</button></nav></>}
 <footer>Demo uses invented patients and static test credentials. Facility servers remain the source of clinic data.</footer></main>;
}
createRoot(document.getElementById('root')!).render(<React.StrictMode><App/></React.StrictMode>);
