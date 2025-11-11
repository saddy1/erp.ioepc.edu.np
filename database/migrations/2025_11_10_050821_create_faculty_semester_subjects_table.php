<?php
// database/migrations/2025_11_10_130000_create_faculty_semester_subjects_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('faculty_semester_subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('faculty_id')
                ->constrained('faculties')
                ->cascadeOnDelete();
            
            $t->unsignedTinyInteger('semester');       // 1..12
            $t->unsignedTinyInteger('batch')->default(1); // 1 = new, 2 = old
            $t->string('subject_code', 80);
            $t->string('subject_name', 191);
            $t->timestamps();

            // Short and clear constraint/index names
            $t->unique(['faculty_id', 'semester', 'batch', 'subject_code'], 'fss_fac_sem_bat_sub_uq');
            $t->index(['semester', 'batch'], 'fss_sem_bat_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('faculty_semester_subjects');
    }
};
