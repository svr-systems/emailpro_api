<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('expiration_dates', function (Blueprint $table) {
      $table->id();
      $table->boolean('is_active')->default(true);
      $table->string('name', 15);
      $table->tinyInteger('months');
    });
  }

  public function down(): void {
    Schema::dropIfExists('expiration_dates');
  }
};
