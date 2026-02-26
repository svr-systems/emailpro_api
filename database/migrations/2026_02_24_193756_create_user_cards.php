<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('user_cards', function (Blueprint $table) {
      $table->id();
      $table->timestamps();
      $table->boolean('is_active')->default(true);
      $table->foreignId('user_id')->constrained('users');
      $table->string('card_id', 25);
      $table->boolean('is_favorite')->default(false);
    });
  }

  public function down(): void {
    Schema::dropIfExists('user_cards');
  }
};
