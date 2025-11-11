<?php
// app/Http/Controllers/SeatPlanController.php
namespace App\Http\Controllers;

use App\Models\{Exam,Room,Faculty,Student,Invigilator,SeatAssignment,ExamRoom};
use App\Services\SeatPlanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeatPlanController extends Controller
{
  public function create()
  {
    return view('Backend.seatplan.create', [
      'faculties'    => Faculty::orderBy('code')->get(),
      'rooms'        => Room::orderBy('room_no')->get(),
      'invigilators' => Invigilator::orderBy('type')->orderBy('name')->get(),
    ]);
  }

  public function store(Request $req, SeatPlanner $planner)
  {
    $data = $req->validate([
      'exam_date'   => ['required','date'],
      'semester'    => ['required','integer','between:1,12'],
      'subject_code'=> ['required','string','max:50'],

      'rooms'                 => ['required','array','min:1'],
      'rooms.*.room_id'       => ['required','exists:rooms,id'],
      'rooms.*.rows_col1'     => ['required','integer','min:0'],
      'rooms.*.rows_col2'     => ['required','integer','min:0'],
      'rooms.*.rows_col3'     => ['required','integer','min:0'],
      'rooms.*.observers_required' => ['required','integer','in:1,2'],

      // allowed pairs: array of ["faculty_a_id:faculty_b_id", ...]
      'allowed_pairs'         => ['array'],
      'allowed_pairs.*'       => ['string'],
      // optional meta
      'start_time'            => ['nullable','string','max:20'],
      'duration_min'          => ['nullable','integer','min:0'],
      'notes'                 => ['nullable','string','max:500'],
    ]);

    $exam = DB::transaction(function() use ($data) {
      $exam = Exam::create([
        'exam_date'   => $data['exam_date'],
        'semester'    => $data['semester'],
        'subject_code'=> $data['subject_code'],
        'meta'        => [
          'start_time' => $data['start_time'] ?? null,
          'duration_min'=> $data['duration_min'] ?? null,
          'notes'      => $data['notes'] ?? null,
        ],
      ]);

      foreach ($data['rooms'] as $r) {
        ExamRoom::create([
          'exam_id' => $exam->id,
          'room_id' => $r['room_id'],
          'rows_col1' => $r['rows_col1'],
          'rows_col2' => $r['rows_col2'],
          'rows_col3' => $r['rows_col3'],
          'observers_required' => $r['observers_required'],
        ]);
      }

      // store allowed pairs (faculty groups with same subject)
      $pairs = $data['allowed_pairs'] ?? [];
      foreach ($pairs as $str) {
        [$a,$b] = array_map('intval', explode(':',$str));
        DB::table('exam_allowed_pairs')->insert([
          'exam_id'=>$exam->id,'faculty_a_id'=>$a,'faculty_b_id'=>$b,'created_at'=>now(),'updated_at'=>now()
        ]);
      }

      return $exam;
    });

    // build inputs for planner
    $roomLayouts = [];
    foreach ($data['rooms'] as $r) {
      $roomLayouts[$r['room_id']] = [
        'rows_col1' => (int)$r['rows_col1'],
        'rows_col2' => (int)$r['rows_col2'],
        'rows_col3' => (int)$r['rows_col3'],
        'observers_required' => (int)$r['observers_required'],
      ];
    }

    $allowedPairs = [];
    foreach (($data['allowed_pairs'] ?? []) as $str) {
      [$a,$b] = array_map('intval', explode(':',$str));
      $allowedPairs[] = [$a,$b];
    }

    $planner->generate($exam, $roomLayouts, $allowedPairs);

    return redirect()->route('seatplan.show', $exam);
  }

  public function show(Exam $exam)
  {
    $rooms = ExamRoom::with('room')->where('exam_id',$exam->id)->get()
      ->map(function($er){
        return [
          'room_id'=>$er->room_id,
          'room_no'=>optional($er->room)->room_no,
          'rows'=>[
            1=>$er->rows_col1,
            2=>$er->rows_col2,
            3=>$er->rows_col3,
          ],
          'observers_required'=>$er->observers_required
        ];
      });

    // seats grouped by room -> col -> row (each row has L/R)
    $seats = SeatAssignment::with(['student.faculty'])
      ->where('exam_id',$exam->id)
      ->get()
      ->groupBy('room_id')
      ->map(fn($g)=>$g->groupBy('col')->map(fn($c)=>$c->groupBy('row')));

    $invigs = \App\Models\InvigilatorAssignment::with('invigilator')
      ->where('exam_id',$exam->id)->get()
      ->groupBy('room_id');

    $facById = Faculty::pluck('code','id');

    return view('seatplan.show', compact('exam','rooms','seats','invigs','facById'));
  }
}
