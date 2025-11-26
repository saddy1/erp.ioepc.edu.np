<?php
// database/seeders/DemoSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Faculty};
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

   
  }
}
