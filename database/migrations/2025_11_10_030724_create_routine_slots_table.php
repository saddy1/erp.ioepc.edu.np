<?php

// database/migrations/2025_11_10_000001_create_routine_slots_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(){
    Schema::create('routine_slots', function(Blueprint $t){
      $t->id();
      $t->string('exam_title');
      $t->string('exam_date');
      $t->unsignedBigInteger('batch')->default(1); // 1 = new, 2 = old
      $t->time('start_time');
      $t->time('end_time');
      $t->unsignedTinyInteger('semester'); // 1..12
      $t->timestamps();
      $t->index(['exam_date','semester']);
      $t->unique(['exam_date','start_time','semester']); // one sitting per sem+date+start
    });
  }
  public function down(){ Schema::dropIfExists('routine_slots'); }
};
