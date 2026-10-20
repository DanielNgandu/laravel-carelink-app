<?php
namespace Tests\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class FollowUpTest extends TestCase {
 use RefreshDatabase;
 private function auth(string $token='demo-clinician-0101'): array { return ['Authorization'=>'Bearer '.$token]; }
 protected function setUp(): void { parent::setUp(); Carbon::setTestNow(Carbon::parse('2026-10-06 12:00:00','Africa/Lusaka')); DB::table('facilities')->insert([
  ['id'=>'FAC-0101','name'=>'Mwansa Urban Clinic','district'=>'Mwansa'],['id'=>'FAC-0207','name'=>'Kalemba Rural Health Centre','district'=>'Kalemba']]); }
 protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }
 private function patient(int $id,string $appointment, string $facility='FAC-0101', ?string $newerVisit=null): void {
  DB::table('patients')->insert(['id'=>$id,'facility_id'=>$facility,'patient_number'=>"P-$id",'first_name'=>'Test','last_name'=>'Patient',
   'date_of_birth'=>'1990-01-01','sex'=>'female','created_at'=>'2026-01-01 00:00:00']);
  DB::table('visits')->insert(['patient_id'=>$id,'visit_date'=>'2026-08-01','next_appointment_date'=>$appointment,'visit_type'=>'routine','created_at'=>'2026-08-01 00:00:00']);
  if($newerVisit) DB::table('visits')->insert(['patient_id'=>$id,'visit_date'=>$newerVisit,'next_appointment_date'=>null,'visit_type'=>'follow_up','created_at'=>$newerVisit.' 00:00:00']);
 }
 public function test_strict_overdue_boundary_and_statuses(): void {
  $this->patient(1,'2026-09-28'); // 8 days, overdue
  $this->patient(2,'2026-09-29'); // exactly 7, missed
  $this->patient(3,'2026-10-05'); // yesterday, missed
  $this->patient(4,'2026-10-06'); // today, due soon
  $this->patient(5,'2026-10-13'); // seven days away, due soon
  $this->patient(6,'2026-10-14'); // outside due soon window
  $this->getJson('/api/follow-up',$this->auth())->assertOk()->assertJsonPath('total',1)->assertJsonPath('items.0.id','p_1')->assertJsonPath('items.0.days_overdue',8);
  $this->getJson('/api/follow-up?status=missed',$this->auth())->assertOk()->assertJsonPath('total',2);
  $this->getJson('/api/follow-up?status=due_soon',$this->auth())->assertOk()->assertJsonPath('total',2);
 }
 public function test_latest_visit_controls_eligibility(): void {
  $this->patient(1,'2026-09-01','FAC-0101','2026-09-12');
  $this->getJson('/api/follow-up',$this->auth())->assertJsonPath('total',0);
 }
 public function test_facility_access_and_district_read_only(): void {
  $this->patient(1,'2026-09-01');$this->patient(2,'2026-09-01','FAC-0207');
  $this->getJson('/api/follow-up',$this->auth())->assertJsonPath('total',1);
  $this->getJson('/api/follow-up?facility_id=FAC-0207',$this->auth())->assertForbidden();
  $this->getJson('/api/follow-up',$this->auth('demo-district'))->assertJsonPath('total',2);
  $this->postJson('/api/follow-up/p_1/contacted',['interaction_id'=>(string)Str::uuid(),'note'=>'called','contacted_at'=>'2026-10-06T09:00:00Z'],$this->auth('demo-district'))->assertForbidden();
  $this->getJson('/api/follow-up')->assertUnauthorized();
 }
 public function test_validation_pagination_sort_and_correlation(): void {
  $this->patient(1,'2026-09-01');$this->patient(2,'2026-09-20');
  $this->getJson('/api/follow-up?status=invalid',$this->auth())->assertStatus(400)->assertJsonStructure(['error'=>['code','message','correlation_id']]);
  $this->getJson('/api/follow-up?overdue_days=0',$this->auth())->assertStatus(400);
  $response=$this->getJson('/api/follow-up?page_size=1&page=2&sort=days_overdue_asc',$this->auth());
  $response->assertOk()->assertJsonPath('total',2)->assertJsonPath('items.0.id','p_1');
  $this->assertNotEmpty($response->headers->get('X-Correlation-Id'));
 }
 public function test_contact_is_idempotent_and_cross_facility_writes_are_denied(): void {
  $this->patient(1,'2026-09-01');$this->patient(2,'2026-09-01','FAC-0207');
  $body=['contacted_at'=>'2026-10-06T09:00:00Z','note'=>'Called without answer','interaction_id'=>(string)Str::uuid()];
  $this->postJson('/api/follow-up/p_1/contacted',$body,$this->auth())->assertNoContent();
  $this->postJson('/api/follow-up/p_1/contacted',$body,$this->auth())->assertNoContent();
  $this->assertDatabaseCount('contact_attempts',1);
  $this->postJson('/api/follow-up/p_1/contacted',[...$body,'note'=>'different'],$this->auth())->assertStatus(409);
  $this->postJson('/api/follow-up/p_2/contacted',$body,$this->auth())->assertForbidden();
 }
}
