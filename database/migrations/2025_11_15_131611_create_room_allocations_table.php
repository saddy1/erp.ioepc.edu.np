<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('room_allocations', function (Blueprint $table) {
            $table->id();
            
            // Foreign keys
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('faculty_id');
            
            // Exam date (same format as routine_slots.exam_date)
            $table->string('exam_date', 50);
            
            // Subject code
            $table->string('subject_code', 50);
            
            // Number of students allocated
            $table->integer('student_count')->default(0);
            $table->unsignedSmallInteger('invigilator_count');
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('exam_id')
                  ->references('id')
                  ->on('exams')
                  ->onDelete('cascade');
                  
            $table->foreign('room_id')
                  ->references('id')
                  ->on('rooms')
                  ->onDelete('cascade');
                  
            $table->foreign('faculty_id')
                  ->references('id')
                  ->on('faculties')
                  ->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['exam_id', 'exam_date']);
            $table->index(['room_id']);
            $table->index(['faculty_id', 'subject_code']);
            
            // Unique constraint: one allocation per exam+date+room+faculty+subject
            $table->unique(
                ['exam_id', 'exam_date', 'room_id', 'faculty_id', 'subject_code'],
                'unique_room_allocation'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_allocations');
    }
};