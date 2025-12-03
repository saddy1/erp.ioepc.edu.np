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
        'file'       => ['required', 'file', 'mimes:csv,txt'],
    ]);

    $facultyId = (int) $data['faculty_id'];
    $sectionId = (int) $data['section_id'];
    $batch     = $data['batch'];

    $file   = $r->file('file');
    $handle = fopen($file->getRealPath(), 'r');

    if (!$handle) {
        return back()->with('error', 'Unable to open the uploaded CSV file.');
    }

    $imported = 0;
    $row      = 0;

    DB::beginTransaction();

    try {
        while (($cols = fgetcsv($handle, 0, ',')) !== false) {
            $row++;

            // Trim all columns
            $cols = array_map('trim', $cols);

            // Skip header row (first row) if looks like header
            if ($row === 1) {
                $col0 = strtolower($cols[0] ?? '');
                $col1 = strtolower($cols[1] ?? '');
                if (str_contains($col0, 'roll') || str_contains($col1, 'name')) {
                    continue;
                }
            }

            // Validate column count (13 in BAG.csv)
            if (count($cols) !== 13) {
                throw new \Exception("Invalid column count at row {$row}. Expected 13 columns, found " . count($cols));
            }

            // Map columns
            $symbolNo   = $cols[0];  // roll_no
            $name       = $cols[1];  // name
            $contact    = $cols[2];  // phone_no
            $email      = $cols[3];  // email
            $fatherName = $cols[4];  // FatherName
            $motherName = $cols[5];  // MotherName
            $gender     = $cols[6];  // gender
            $municipal  = $cols[7];  // vdc_municipal
            $ward       = $cols[8];  // ward_no
            $district   = $cols[9];  // district
            $year       = (int)($cols[10] ?? 1); // year
            $part       = (int)($cols[11] ?? 1); // part
            $dob        = $cols[12] ?? null;     // dob (string, BS)

            // Basic sanity check
            if ($symbolNo === '' || $name === '') {
                throw new \Exception("Empty roll no or name at row {$row}.");
            }

            // Compute semester from year & part (1→1-2, 2→3-4, etc.)
            $semester = (($year - 1) * 2) + ($part === 2 ? 2 : 1);

            // Avoid duplicate roll numbers (by symbol_no)
            if (Student::where('symbol_no', $symbolNo)->exists()) {
                // skip duplicates silently, or:
                // throw new \Exception("Duplicate roll/ symbol_no '{$symbolNo}' at row {$row}.");
                continue;
            }

            Student::create([
                'symbol_no'    => $symbolNo,
                'name'         => $name,
                'contact'      => $contact,
                'email'        => $email,
                'father_name'  => $fatherName,
                'mother_name'  => $motherName,
                'gender'       => $gender,
                'municipality' => $municipal,
                'ward'         => $ward,
                'district'     => $district,
                'year'         => $year,
                'part'         => $part,
                'dob'          => $dob,
                'faculty_id'   => $facultyId,
                'section_id'   => $sectionId,
                'batch'        => $batch,
                'semester'     => $semester,
            ]);

            $imported++;
        }

        DB::commit();
        fclose($handle);

        return back()->with('ok', "Imported {$imported} students successfully.");
    } catch (\Throwable $e) {
        DB::rollBack();
        fclose($handle);

        // Show row + message in frontend
        return back()->with('error', "Import failed at row {$row}: " . $e->getMessage());
    }
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
