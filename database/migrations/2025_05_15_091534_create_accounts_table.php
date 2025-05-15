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
        Schema::create( 'accounts', function ( Blueprint $table ) {
            $table->id();
            $table->string( 'sheba_number', 26 )->unique();
            $table->bigInteger( 'balance' )->default( 0 );
            $table->timestamps();

            $table->index( 'sheba_number' );
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'accounts' );
    }
};
