<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('faculty_id')
                ->constrained('faculties')
                ->cascadeOnDelete();
            $t->string('name', 50);          // e.g. A, B, C
            $t->string('code', 20)->nullable(); // optional short code

            $t->timestamps();

            $t->unique(['faculty_id', 'name'], 'sec_fac_name_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
