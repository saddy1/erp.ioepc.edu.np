<?php
// database/migrations/2025_11_20_000000_create_subjects_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->string('code', 80)->unique();     // ENSH151, ENCT151, ...
            $t->string('name', 191);              // Engineering Mathematics II
            $t->boolean('has_practical')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
