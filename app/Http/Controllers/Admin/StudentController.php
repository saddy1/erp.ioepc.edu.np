<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    // ===== Master student list + simple create/edit =====
  
    public function showImportForm()
    {
    $faculties = Faculty::with(relations: 'sections')->codeOrder()->paginate(20);
        return view('Backend.admin.students.import', compact('faculties'));
    }

    public function import(Request $r)
    {
        $data = $r->validate([
            'batch'      => ['required', 'string', 'max:30'],
            'faculty_id' => ['required', 'exists:faculties,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'file'       => ['required', 'file', 'mimes:csv,txt'], // CSV parsing here
        ]);

        $facultyId = (int) $data['faculty_id'];
        $sectionId = (int) $data['section_id'];
        $batch     = $data['batch'];

        $file = $r->file('file');

        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('error', 'Unable to open file.');
        }

        $imported = 0;

        DB::transaction(function () use ($handle, &$imported, $facultyId, $sectionId, $batch) {
            $row = 0;
            while (($cols = fgetcsv($handle, 0, ',')) !== false) {
                $row++;

                // Expect: roll, name, contact, email, father, mother, gender, municipal/vdc, ward, district, year, part
                if ($row === 1) {
                    // Try to detect header row (starts with "roll" or similar)
                    $first = strtolower(trim($cols[0] ?? ''));
                    if (str_contains($first, 'roll') || str_contains($first, 'symbol')) {
                        continue;
                    }
                }

                if (count($cols) < 12) {
                    continue; // skip invalid rows
                }

                $symbolNo    = trim($cols[0]);
                $name        = trim($cols[1]);
                $contact     = trim($cols[2] ?? '');
                $email       = trim($cols[3] ?? '');
                $fatherName  = trim($cols[4] ?? '');
                $motherName  = trim($cols[5] ?? '');
                $gender      = trim($cols[6] ?? '');
                $municipal   = trim($cols[7] ?? '');
                $ward        = trim($cols[8] ?? '');
                $district    = trim($cols[9] ?? '');
                $year        = (int) ($cols[10] ?? 1);
                $part        = (int) ($cols[11] ?? 1);

                if ($symbolNo === '' || $name === '') {
                    continue;
                }

                $semester = (($year - 1) * 2) + ($part === 2 ? 2 : 1);

                Student::updateOrCreate(
                    ['symbol_no' => $symbolNo],
                    [
                        'name'         => $name,
                        'faculty_id'   => $facultyId,
                        'section_id'   => $sectionId,
                        'batch'        => $batch,
                        'year'         => $year,
                        'part'         => $part,
                        'semester'     => $semester,
                        'contact'      => $contact,
                        'email'        => $email,
                        'father_name'  => $fatherName,
                        'mother_name'  => $motherName,
                        'gender'       => $gender,
                        'municipality' => $municipal,
                        'ward'         => $ward,
                        'district'     => $district,
                    ]
                );

                $imported++;
            }

            fclose($handle);
        });

        return redirect()
            ->route('students.import.form')
            ->with('ok', "Imported / updated {$imported} students for batch {$batch}.");
    }

    // ===== Bulk semester upgrade: year/part -> next year/part for a batch =====

    public function showUpgradeForm()
    {
        $faculties = Faculty::orderBy('code')->get();
        return view('Backend.admin.students.upgrade', compact('faculties'));
    }

    public function upgradeSemester(Request $r)
    {
        $data = $r->validate([
            'batch'      => ['required', 'string', 'max:30'],
            'from_year'  => ['required', 'integer', 'min:1', 'max:8'],
            'from_part'  => ['required', 'integer', 'in:1,2'],
            'faculty_id' => ['nullable', 'exists:faculties,id'],
        ]);

        $batch     = $data['batch'];
        $fromYear  = (int) $data['from_year'];
        $fromPart  = (int) $data['from_part'];
        $facultyId = $data['faculty_id'] ?? null;

        // Promotion rule:
        // (1,1) -> (1,2)   (1st year 1st part -> 1st year 2nd part)
        // (1,2) -> (2,1)   (1st year 2nd part -> 2nd year 1st part), etc.
        $toYear = $fromYear;
        $toPart = 1;

        if ($fromPart === 1) {
            $toPart = 2;
        } else {
            $toYear = $fromYear + 1;
            $toPart = 1;
        }

        $toSemester = (($toYear - 1) * 2) + $toPart;

        $query = Student::where('batch', $batch)
            ->where('year', $fromYear)
            ->where('part', $fromPart);

        if ($facultyId) {
            $query->where('faculty_id', $facultyId);
        }

        $updated = $query->update([
            'year'     => $toYear,
            'part'     => $toPart,
            'semester' => $toSemester,
        ]);

        return redirect()
            ->route('students.upgrade.form')
            ->with('ok', "Upgraded {$updated} students of batch {$batch} from Y{$fromYear} P{$fromPart} to Y{$toYear} P{$toPart} (Sem {$toSemester}).");
    }
}
