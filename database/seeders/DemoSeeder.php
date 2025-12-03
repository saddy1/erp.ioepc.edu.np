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
      ['name' => 'Computer Engineering', 'code' => 'BCT'],
      ['name' => 'Electronics, Communication & Information Engineering', 'code' => 'BEI'],
       ['name' => 'Electrical Engineering', 'code' => 'BEL'],
       ['name' => 'Electronics & Communiaction Engineering', 'code' => 'BEX'],
         ['name' => 'Mechanical Engineering', 'code' => 'BME'],
      ['name' => 'Civil Engineering', 'code' => 'BCE'],
      
     ['name' => 'Bachelor in Architecture', 'code' => 'BAR'],

      ['name' => 'Agriculture Engineering', 'code' => 'BAG'],
     
    ])->map(fn($x) => Faculty::create($x));

   
  }
}
