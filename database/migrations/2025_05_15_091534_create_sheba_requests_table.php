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
        Schema::create( 'sheba_requests', function ( Blueprint $table ) {
            $table->uuid( 'id' )->primary();
            $table->bigInteger( 'price' );
            $table->string( 'from_sheba_number', 26 );
            $table->string( 'to_sheba_number', 26 );
            $table->string( 'note' )->nullable();
            $table->enum( 'status', [ 'pending', 'confirmed', 'canceled' ] )->default( 'pending' );
            $table->timestamp( 'confirmed_at' )->nullable();
            $table->timestamp( 'canceled_at' )->nullable();
            $table->string( 'cancellation_note' )->nullable();
            $table->timestamps();

            $table->index( [ 'status', 'created_at' ] );
            $table->index( 'from_sheba_number' );
            $table->index( 'to_sheba_number' );
            $table->index( [ 'from_sheba_number', 'status' ], 'idx_from_status' );  // For account history filtering by status
            $table->index( [ 'to_sheba_number', 'status' ], 'idx_to_status' );      // For account history filtering by status
            $table->index( [ 'confirmed_at' ], 'idx_confirmed_date' );              // For date-based reporting on completed transfers
            $table->index( [ 'canceled_at' ], 'idx_canceled_date' );                // For date-based reporting on canceled transfers

        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'sheba_requests' );
    }
};
