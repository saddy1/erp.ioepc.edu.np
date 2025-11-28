<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->enum('shift', ['morning', 'day']);
            $table->unsignedTinyInteger('order');              // 1..N within shift
            $table->string('label', 20);                       // e.g. P1, MP1, etc.
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->unique(['shift', 'order']);                // no duplicate slots
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
