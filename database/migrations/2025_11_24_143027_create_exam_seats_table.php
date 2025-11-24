<?php

// database/migrations/2025_11_24_000000_create_exam_seats_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_seats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');
            $table->string('exam_date',20);
            $table->unsignedTinyInteger('batch');      // 1=new, 2=old
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('faculty_id')->nullable();
            $table->string('subject_code', 50);

            $table->string('symbol_no', 50);

            $table->unsignedTinyInteger('column_no');  // 1,2,3
            $table->unsignedTinyInteger('row_no');     // R1, R2…
            $table->enum('side', ['L', 'R']);          // Left / Right seat
            $table->unsignedSmallInteger('bench_index'); // linear index 0..N-1

            $table->timestamps();

            // Avoid duplicate same student/subject in same exam
            $table->unique(
                ['exam_id', 'exam_date', 'batch', 'subject_code', 'symbol_no'],
                'exam_seats_unique_student'
            );

            $table->index(['exam_id', 'exam_date', 'batch']);
            $table->index(['room_id']);
            $table->index(['faculty_id']);
            $table->index(['subject_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_seats');
    }
};
