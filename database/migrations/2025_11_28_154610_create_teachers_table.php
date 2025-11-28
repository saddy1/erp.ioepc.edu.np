<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();      // e.g. T001
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone', 20)->nullable();

            // Main department/faculty they belong to
            $table->foreignId('faculty_id')
                  ->constrained()
                  ->cascadeOnDelete();

            $table->boolean('is_active')->default(true);

            // For login later (we’ll hook into this in attendance step)
            $table->string('password')->nullable();    // hashed later

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
