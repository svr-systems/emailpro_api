<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('clients', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained('users')->unique();
      $table->string('customer_id', 25);
    });
  }

  public function down(): void {
    Schema::dropIfExists('clients');
  }
};
