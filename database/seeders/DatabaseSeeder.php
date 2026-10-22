<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
class DatabaseSeeder extends Seeder {
  
 public function run(): void {

  $this->call([
            UserSeeder::class,
        ]);

  $facilities = [['id'=>'FAC-0101','name'=>'Mwansa Urban Clinic','district'=>'Mwansa'],
    ['id'=>'FAC-0207','name'=>'Kalemba Rural Health Centre','district'=>'Kalemba'],
    ['id'=>'FAC-0312','name'=>'Chembe District Hospital','district'=>'Chembe']];
  DB::table('facilities')->insert($facilities);
  $large = (bool) env('LARGE_SEED', false);
  $count = $large ? 100000 : 150;
  $today = Carbon::now('Africa/Lusaka')->startOfDay();
  for ($start=1; $start<=$count; $start+=500) {
   $patients=[]; $visits=[];
   for ($i=$start; $i<min($start+500,$count+1); $i++) {
    $facility = $facilities[($i-1)%3]['id'];
    $patients[]=['id'=>$i,'facility_id'=>$facility,'patient_number'=>substr($facility,4).'-'.str_pad((string)$i,6,'0',STR_PAD_LEFT),
     'first_name'=>['Thandiwe','Mulenga','Chipo','Chanda','Mwamba'][$i%5], 'last_name'=>['Banda','Phiri','Zulu','Mwale'][$i%4],
     'date_of_birth'=>'1990-03-14','sex'=>$i%2?'female':'male','phone_number'=>null,'created_at'=>$today->toDateTimeString()];
    $visitCount=$large?4:2;
    for ($j=0;$j<$visitCount;$j++) {
     $when=$today->copy()->subDays(120-$j*25);
     $appointment=$j===$visitCount-1?($i%9===0?null:$today->copy()->addDays(($i%65)-45)->toDateString()):$when->copy()->addDays(20)->toDateString();
     $visits[]=['patient_id'=>$i,'visit_date'=>$when->toDateString(),'next_appointment_date'=>$appointment,
       'visit_type'=>'routine','created_at'=>$today->toDateTimeString()];
    }
   }
   DB::table('patients')->insert($patients); foreach(array_chunk($visits,500) as $chunk) DB::table('visits')->insert($chunk);
  }
 }
}
