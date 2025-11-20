<?php
// database/seeders/DemoSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Faculty, Student, Room, Invigilator};
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
  public function run()
  {
    $f = collect([
      ['name' => 'Civil Engineering', 'code' => 'BCE'],
      ['name' => 'Electrical Engineering', 'code' => 'BEL'],
      ['name' => 'Electronics, Communication & Information Engineering', 'code' => 'BEI'],
      ['name' => 'Electronics & Communiaction Engineering', 'code' => 'BEX'],
      ['name' => 'Computer Engineering', 'code' => 'BCT'],
      ['name' => 'Mechanical Engineering', 'code' => 'BME'],
      ['name' => 'Agriculture Engineering', 'code' => 'BAG'],
      ['name' => 'Bachelor in Architecture', 'code' => 'BAR'],

    ])->map(fn($x) => Faculty::create($x));

    // 20 students per faculty in semester 3
    foreach ($f as $fac) {
      for ($i = 1; $i <= 20; $i++) {
        Student::create([
          'name' => $fac->code . ' Student ' . $i,
          'symbol_no' => $fac->code . sprintf('%03d', $i),
          'faculty_id' => $fac->id,
          'semester' => 3,
        ]);
      }
    }

    foreach (['A101', 'A102', 'B201'] as $r) {
      Room::create(['room_no' => $r, 'total_benches' => 0]);
    }

    // 6 teachers, 6 staff
    foreach (range(1, 6) as $i) {
      Invigilator::create(['name' => "Teacher $i", 'type' => 'teacher']);
      Invigilator::create(['name' => "Staff $i", 'type' => 'staff']);
    }
  }
}
