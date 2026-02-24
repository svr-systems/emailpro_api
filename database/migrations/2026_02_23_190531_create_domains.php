<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('domains', function (Blueprint $table) {
      $table->id();
      $table->boolean('is_active')->default(true);
      $table->timestamps();
      $table->foreignId('created_by_id')->constrained('users');
      $table->foreignId('updated_by_id')->constrained('users');
      $table->string('company', 80);
      $table->foreignId('extention_id')->constrained('extensions');
      $table->date('expire_at');
      $table->tinyInteger('email_accounts');
    });
  }

  public function down(): void {
    Schema::dropIfExists('domains');
  }
};
