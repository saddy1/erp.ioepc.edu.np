<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routines', function (Blueprint $table) {
            $table->id();

            // Academic context
            $table->foreignId('faculty_id')->constrained()->cascadeOnDelete();
            $table->string('batch', 10); 
            $table->unsignedTinyInteger('year');                    // 2080, 2081...
            $table->unsignedTinyInteger('semester');         // 1..8
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();

            // Time slot
            $table->foreignId('period_id')->constrained('periods')->cascadeOnDelete();
            // We'll still store day separately: Sun-Fri
            $table->enum('day_of_week', ['sun','mon','tue','wed','thu','fri']);

            // Group & type
            // ALL = whole section together (theory), A/B = split for practicals
            $table->enum('group', ['ALL','A','B'])->default('ALL');
            $table->enum('type', ['TH','PR'])->default('TH');

            // Teaching info
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();

            // Optional room allocation
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();

            // Optional academic year (if you want separate routines per AY)
            $table->string('academic_year', 15)->nullable();

            $table->timestamps();

            // Prevent duplicate slot for same section+group
            $table->unique(
                [
                    'faculty_id','batch','semester','section_id',
                    'day_of_week','period_id','group'
                ],
                'routine_unique_section_slot'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routines');
    }
};
