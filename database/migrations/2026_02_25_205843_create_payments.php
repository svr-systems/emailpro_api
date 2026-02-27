<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('payments', function (Blueprint $table) {
      $table->id();
      $table->boolean('is_active')->default(true);
      $table->timestamps();
      $table->foreignId('domain_id')->constrained('domains');
      $table->decimal('amount', 11, 2);
      $table->date('expired_on');
      $table->date('expire_at');
      $table->foreignId('transaction_id')->constrained('transactions');
      $table->foreignId('expiration_date_id')->constrained('expiration_dates');
      $table->string('invoice_id', 25)->nullable();
    });
  }

  public function down(): void {
    Schema::dropIfExists('payments');
  }
};
