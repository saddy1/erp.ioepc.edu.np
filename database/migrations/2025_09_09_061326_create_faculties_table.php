<?php
// database/migrations/2025_11_09_000001_create_faculties_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up() {
    Schema::create('faculties', function (Blueprint $t) {
      $t->id();
      $t->string('name');
      $t->string('code')->unique(); // short code like CE, CSIT, ELE, MEC...
      $t->timestamps();
    });
  }
  public function down() { Schema::dropIfExists('faculties'); }
};
