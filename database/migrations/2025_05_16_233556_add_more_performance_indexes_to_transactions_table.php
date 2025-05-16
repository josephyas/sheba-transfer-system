<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table( 'transactions', function ( Blueprint $table ) {
            Schema::table( 'transactions', function ( Blueprint $table ) {
                // Drop existing indexes to recreate optimal compound indexes
                $table->dropIndex( [ 'account_id' ] );
                $table->dropIndex( [ 'sheba_request_id' ] );

                // Add optimized indexes for high-throughput queries

                // Compound index for account statements (useful for filtering by account and type)
                $table->index( [ 'account_id', 'type' ], 'idx_account_type' );

                // Index for time-based queries (transaction timeline/history)
                $table->index( [ 'created_at' ], 'idx_transaction_date' );

                // Compound index for transfer auditing (all transactions for a specific transfer)
                $table->index( [ 'sheba_request_id', 'type' ], 'idx_transfer_type' );

                // Compound index for financial reporting (grouping by type and time period)
                $table->index( [ 'type', 'created_at' ], 'idx_type_date' );

                // Recreate the single column indexes for general queries
                $table->index( 'account_id', 'idx_account' );
                $table->index( 'sheba_request_id', 'idx_sheba_request' );
            } );
        } );

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'transactions', function ( Blueprint $table ) {
            // Drop all new indexes
            $table->dropIndex( 'idx_account_type' );
            $table->dropIndex( 'idx_transaction_date' );
            $table->dropIndex( 'idx_transfer_type' );
            $table->dropIndex( 'idx_type_date' );
            $table->dropIndex( 'idx_account' );
            $table->dropIndex( 'idx_sheba_request' );

            // Recreate old indexes
            $table->index( [ 'account_id' ] );
            $table->index( [ 'sheba_request_id' ] );
        } );
    }
};
