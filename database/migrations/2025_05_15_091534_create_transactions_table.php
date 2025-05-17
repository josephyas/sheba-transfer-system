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
            $table->index(['account_id', 'type'], 'idx_account_type');        // For account statements filtering by type
            $table->index(['created_at'], 'idx_transaction_date');            // For time-based transaction history
            $table->index(['sheba_request_id', 'type'], 'idx_transfer_type'); // For transfer auditing by type
            $table->index(['type', 'created_at'], 'idx_type_date');           // For financial reporting by period
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
