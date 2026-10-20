import {describe,it,expect,vi,afterEach} from 'vitest';
import {getFollowUps,type Filters} from './service';
const filters:Filters={facility_id:'FAC-0101',status:'overdue',overdue_days:7,sort:'days_overdue_desc',page:1,page_size:50};
afterEach(()=>vi.unstubAllGlobals());
describe('follow-up service',()=>{
 it('sends filters and a token, then parses paginated response',async()=>{
  const request=vi.fn().mockResolvedValue({ok:true,json:async()=>({page:1,page_size:50,total:0,items:[]})});vi.stubGlobal('fetch',request);
  const result=await getFollowUps(filters,'token');expect(result.total).toBe(0);
  expect(request.mock.calls[0][0]).toContain('facility_id=FAC-0101');expect(request.mock.calls[0][1].headers.Authorization).toBe('Bearer token');
 });
 it('propagates safe API errors for retry UI',async()=>{
  vi.stubGlobal('fetch',vi.fn().mockResolvedValue({ok:false,json:async()=>({error:{message:'Try again'}})}));
  await expect(getFollowUps(filters,'token')).rejects.toThrow('Try again');
 });
});
