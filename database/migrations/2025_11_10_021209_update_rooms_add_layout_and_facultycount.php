<?php

// database/migrations/2025_11_09_100001_update_rooms_add_layout_and_facultycount.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(){
    Schema::table('rooms', function(Blueprint $t){
      if (!Schema::hasColumn('rooms','rows_col1')) {
        $t->unsignedInteger('rows_col1')->default(0);
        $t->unsignedInteger('rows_col2')->default(0);
        $t->unsignedInteger('rows_col3')->default(0);
      }
      if (!Schema::hasColumn('rooms','faculties_per_room')) {
        $t->unsignedTinyInteger('faculties_per_room')->default(2); // constant per room
      }
    });
  }
  public function down(){
    Schema::table('rooms', function(Blueprint $t){
      $t->dropColumn(['rows_col1','rows_col2','rows_col3','faculties_per_room']);
    });
  }
};
