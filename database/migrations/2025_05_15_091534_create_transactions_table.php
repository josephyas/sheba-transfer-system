<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->onDelete('cascade');
            $table->uuid('sheba_request_id')->nullable();
            $table->foreign('sheba_request_id')->references('id')->on('sheba_requests')->onDelete('set null');
            $table->enum('type', ['debit', 'credit', 'refund']);
            $table->bigInteger('amount');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('sheba_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
