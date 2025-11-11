<?php
// app/Services/SeatPlanner.php
namespace App\Services;


use App\Models\{Exam,ExamRoom,SeatAssignment,Student,Faculty,Room,Invigilator,InvigilatorAssignment};
use Illuminate\Support\Collection;    


class SeatPlanner
{
  /**
   * @param Exam $exam
   * @param array $roomLayouts [room_id => ['rows_col1'=>int,'rows_col2'=>int,'rows_col3'=>int,'observers_required'=>1|2]]
   * @param array $allowedPairs array of [ [faculty_a_id, faculty_b_id], ... ] (same-subject faculties allowed on the same bench)
   * @return array summary
   */
  public function generate(Exam $exam, array $roomLayouts, array $allowedPairs = []): array
  {
    // Normalize allowed pairs as a bidirectional set
    $allowed = [];
    foreach ($allowedPairs as $pair) {
      [$a,$b] = $pair;
      $allowed["$a:$b"] = true;
      $allowed["$b:$a"] = true;
    }

    // Students of the selected semester, across all faculties
    $students = Student::with('faculty')
      ->where('semester', $exam->semester)
      ->orderBy('faculty_id')
      ->orderBy('symbol_no')
      ->get();

    // Prepare queues per faculty
    $byFaculty = $students->groupBy('faculty_id')->map(fn($c)=>$c->values());

    // Build list of seats (capacity) across chosen rooms & layout
    $seatSlots = []; // [ [room_id,col,row,side], ... ]
    foreach ($roomLayouts as $roomId => $layout) {
      for ($col=1; $col<=3; $col++) {
        $rows = (int)$layout["rows_col{$col}"];
        for ($row=1; $row<=$rows; $row++) {
          // two sides per bench
          $seatSlots[] = ['room_id'=>$roomId,'col'=>$col,'row'=>$row,'side'=>'L'];
          $seatSlots[] = ['room_id'=>$roomId,'col'=>$col,'row'=>$row,'side'=>'R'];
        }
      }
    }

    // Greedy bench pairing: fill seatSlots in bench pairs (L then R)
    // Strategy: pick first student from the faculty with most remaining.
    // For partner: pick a student from a different faculty (or allowed pair) with most remaining.
    $remaining = $byFaculty->map->count()->toArray();

    // Helper to pop student from a faculty queue
    $popFrom = function(int $fid) use (&$byFaculty, &$remaining) {
      $st = $byFaculty[$fid]->shift();
      $remaining[$fid]--;
      return $st;
    };

    // Ordered list of faculty ids by remaining count (max-heap-like via resort each time)
    $nextFacultyOrder = function() use (&$remaining) {
      arsort($remaining);
      return array_keys(array_filter($remaining, fn($n)=>$n>0));
    };

    $assignments = [];

    for ($i=0; $i<count($seatSlots); $i+=2) {
      $left = $seatSlots[$i] ?? null;
      $right = $seatSlots[$i+1] ?? null;
      if (!$left) break;

      // choose left student
      $order = $nextFacultyOrder();
      if (empty($order)) break;

      $leftFid = $order[0];
      $leftStudent = $popFrom($leftFid);
      $assignments[] = array_merge($left, ['student_id'=>$leftStudent?->id]);

      // choose right student
      if ($right && array_sum($remaining) > 0) {
        // candidates: faculty with most remaining, not same as left unless allowed
        $order = $nextFacultyOrder();
        $rightStudent = null;
        foreach ($order as $fid) {
          if ($fid == $leftFid) continue; // prefer different faculty
          $rightStudent = $popFrom($fid);
          break;
        }
        // If only same faculty remains, check allowed pair with itself? (never allowed)
        // Or try to find allowed *pair* with left faculty among remaining
        if (!$rightStudent && $right) {
          foreach ($order as $fid) {
            if (isset($allowed["$leftFid:$fid"])) {
              $rightStudent = $popFrom($fid);
              break;
            }
          }
          // As a last resort: if strictly no alternative, put same-faculty (but we try very hard to avoid)
          if (!$rightStudent && $remaining[$leftFid] > 0) {
            $rightStudent = $popFrom($leftFid);
          }
        }
        $assignments[] = array_merge($right, ['student_id'=>$rightStudent?->id]);
      }
    }

    // Persist
    // Clear old seats for this exam (idempotent re-gen)
    SeatAssignment::where('exam_id', $exam->id)->delete();

    foreach ($assignments as $a) {
      SeatAssignment::create([
        'exam_id'   => $exam->id,
        'room_id'   => $a['room_id'],
        'col'       => $a['col'],
        'row'       => $a['row'],
        'side'      => $a['side'],
        'student_id'=> $a['student_id'] ?? null,
      ]);
    }

    // Observer assignment
    InvigilatorAssignment::where('exam_id',$exam->id)->delete();

    $teachers = Invigilator::where('type','teacher')->get()->shuffle();
    $staffs   = Invigilator::where('type','staff')->get()->shuffle();

    foreach ($roomLayouts as $roomId => $layout) {
      $need = max(1, min(2, (int)($layout['observers_required'] ?? 1)));
      if ($need == 1) {
        // pick teacher if available else staff
        $inv = $teachers->shift() ?? $staffs->shift();
        if ($inv) InvigilatorAssignment::create([
          'exam_id'=>$exam->id,'room_id'=>$roomId,'invigilator_id'=>$inv->id
        ]);
      } else {
        // try teacher + staff
        $inv1 = $teachers->shift() ?? $staffs->shift();
        $inv2 = $staffs->shift() ?? $teachers->shift();
        foreach (array_filter([$inv1,$inv2]) as $x) {
          InvigilatorAssignment::create([
            'exam_id'=>$exam->id,'room_id'=>$roomId,'invigilator_id'=>$x->id
          ]);
        }
      }
    }

    return [
      'seats_created' => count($assignments),
      'students_total'=> $students->count(),
    ];
  }
}
