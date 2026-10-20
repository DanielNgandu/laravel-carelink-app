<?php
namespace App\Http\Controllers;

use App\Models\ContactAttempt;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FollowUpController extends Controller {
 public function index(Request $request): JsonResponse {
    $input = $request->validate([
        'facility_id' => ['sometimes','string','max:30'],
        'status' => ['sometimes', Rule::in(['due_soon','missed','overdue'])],
        'overdue_days' => ['sometimes','integer','min:1','max:365'],
        'sort' => ['sometimes', Rule::in(['days_overdue_asc','days_overdue_desc'])],
        'page' => ['sometimes','integer','min:1'], 'page_size' => ['sometimes','integer','min:1','max:100'],
    ]);
    $actor = $request->attributes->get('actor');
    $facility = $input['facility_id'] ?? null;
    if ($actor['role'] === 'CLINICIAN') {
        abort_if($facility !== null && $facility !== $actor['facility_id'], 403, 'Cannot read another facility.');
        $facility = $actor['facility_id'];
    }
    $threshold = (int) ($input['overdue_days'] ?? 7);
    $status = $input['status'] ?? 'overdue';
    $today = Carbon::now('Africa/Lusaka')->startOfDay();
    $todayString = $today->toDateString();
    $base = DB::table('patients as p')
        ->join('facilities as f','f.id','=','p.facility_id')
        ->join('visits as v','v.patient_id','=','p.id')
        ->whereNotNull('v.next_appointment_date')
        ->where('v.visit_date','<=',$todayString)
        ->whereNotExists(function ($q) {
            // This visit must be the latest, including deterministic tie-breaking on its ID.
            $q->selectRaw('1')->from('visits as later')->whereColumn('later.patient_id','v.patient_id')
              ->where(function ($q) { $q->whereColumn('later.visit_date','>','v.visit_date')
                ->orWhere(function ($q) { $q->whereColumn('later.visit_date','v.visit_date')->whereColumn('later.id','>','v.id'); }); });
        });
    if ($facility !== null) $base->where('p.facility_id',$facility);
    if ($status === 'overdue') $base->where('v.next_appointment_date','<',$today->copy()->subDays($threshold)->toDateString());
    elseif ($status === 'missed') $base->whereBetween('v.next_appointment_date',[
        $today->copy()->subDays($threshold)->toDateString(), $today->copy()->subDay()->toDateString()]);
    else $base->whereBetween('v.next_appointment_date',[$todayString,$today->copy()->addDays(7)->toDateString()]);
    // Sorting by appointment date is equivalent to sorting by days overdue within one timezone.
    $direction = ($input['sort'] ?? 'days_overdue_desc') === 'days_overdue_desc' ? 'asc' : 'desc';
    $page = (int) ($input['page'] ?? 1); $size = (int) ($input['page_size'] ?? 50);
    $total = (clone $base)->count();
    $rows = $base->select(['p.id','p.patient_number','p.first_name','p.last_name','p.facility_id',
         'p.phone_number','f.name as facility_name','v.next_appointment_date as missed_appointment_date'])
         ->orderBy('v.next_appointment_date',$direction)->orderBy('p.id')->forPage($page,$size)->get();
    $ids = $rows->pluck('id')->all();
    $contacts = empty($ids) ? collect() : DB::table('contact_attempts')->whereIn('patient_id',$ids)
        ->selectRaw('patient_id, MAX(contacted_at) as latest')->groupBy('patient_id')->pluck('latest','patient_id');
    $items = $rows->map(function ($row) use ($today, $status, $contacts) {
        $days = Carbon::parse($row->missed_appointment_date, 'Africa/Lusaka')->startOfDay()->diffInDays($today, false);
        return ['id' => 'p_'.$row->id, 'patient_number' => $row->patient_number,
            'first_name' => $row->first_name, 'last_name' => $row->last_name,
            'facility_id' => $row->facility_id, 'facility_name' => $row->facility_name,
            'missed_appointment_date' => $row->missed_appointment_date, 'days_overdue' => (int) $days,
            'follow_up_status' => $status, 'phone_number' => $row->phone_number,
            'last_contact_attempt' => $contacts->get($row->id)];
    });
    return response()->json(['page'=>$page,'page_size'=>$size,'total'=>$total,'items'=>$items]);
 }

 public function contacted(Request $request, string $patient): JsonResponse|\Illuminate\Http\Response {
    $actor = $request->attributes->get('actor');
    abort_unless($actor['role'] === 'CLINICIAN',403,'Only clinicians may record contacts.');
    $body = $request->validate(['contacted_at'=>['required','date'], 'note'=>['required','string','max:1000'],
        'interaction_id'=>['required','uuid']]);
    $id = Str::startsWith($patient,'p_') ? substr($patient,2) : '';
    abort_unless(ctype_digit($id),404,'Patient not found.');
    $record = Patient::find($id);
    abort_unless($record !== null,404,'Patient not found.');
    abort_unless($record->facility_id === $actor['facility_id'],403,'Cannot modify another facility.');
    $existing = ContactAttempt::where('interaction_id',$body['interaction_id'])->first();
    if ($existing) {
        abort_if($existing->patient_id !== $record->id || $existing->contacted_at !== Carbon::parse($body['contacted_at'])->format('Y-m-d H:i:s') || $existing->note !== $body['note'],409,'Interaction ID used for another operation.');
        return response()->noContent();
    }
    ContactAttempt::create(['patient_id'=>$record->id,'interaction_id'=>$body['interaction_id'],
        'contacted_at'=>Carbon::parse($body['contacted_at'])->format('Y-m-d H:i:s'),'note'=>$body['note']]);
    return response()->noContent();
 }
}
