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
        Schema::table( 'sheba_requests', function ( Blueprint $table ) {
            $table->string( 'idempotency_key' )->nullable()->unique();
            $table->index( 'idempotency_key' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'sheba_requests', function ( Blueprint $table ) {
            $table->dropIndex( [ 'idempotency_key' ] );
            $table->dropColumn( 'idempotency_key' );
        } );
    }
};
