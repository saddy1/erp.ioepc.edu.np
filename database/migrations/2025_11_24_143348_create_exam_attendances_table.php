<?php

// database/migrations/2025_11_24_000001_create_exam_attendances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');
             $table->string('exam_date', 20);
            $table->unsignedTinyInteger('batch');      // 1=new, 2=old
            $table->unsignedBigInteger('faculty_id');
            $table->string('subject_code', 50);

            $table->string('symbol_no', 50);
            $table->enum('status', ['present', 'absent']);

            $table->timestamps();

            $table->unique(
                ['exam_id', 'exam_date', 'batch', 'faculty_id', 'subject_code', 'symbol_no'],
                'exam_attendance_unique_student'
            );

            $table->index(['exam_id', 'exam_date', 'batch']);
            $table->index(['faculty_id']);
            $table->index(['subject_code']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attendances');
    }
};
