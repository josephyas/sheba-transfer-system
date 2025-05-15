<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ShebaRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShebaUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test confirming a sheba transfer request.
     */
    public function test_can_confirm_sheba_request(): void
    {
        // Create test accounts
        $sourceAccount = Account::create( [
            'sheba_number' => 'IR123456789012345678901234',
            'balance'      => 995000000
        ] );

        $destAccount = Account::create( [
            'sheba_number' => 'IR987654321098765432109876',
            'balance'      => 0
        ] );

        // Create a pending sheba request
        $requestId = (string)Str::uuid();
        $shebaRequest = ShebaRequest::create( [
            'id'                => $requestId,
            'price'             => 5000000,
            'from_sheba_number' => $sourceAccount->sheba_number,
            'to_sheba_number'   => $destAccount->sheba_number,
            'status'            => 'pending',
            'note'              => 'Test transfer'
        ] );

        // Send confirm request
        $response = $this->putJson( "/api/sheba/{$requestId}", [
            'status' => 'confirmed'
        ] );

        // Assert response
        $response->assertStatus( 200 )
            ->assertJson( [
                'message' => 'Request is Confirmed!',
                'request' => [
                    'id'     => $requestId,
                    'status' => 'confirmed'
                ]
            ] );

        // Assert database state
        $this->assertDatabaseHas( 'sheba_requests', [
            'id'     => $requestId,
            'status' => 'confirmed'
        ] );

        $this->assertDatabaseHas( 'accounts', [
            'id'      => $destAccount->id,
            'balance' => 5000000 // Should be credited
        ] );

        $this->assertDatabaseHas( 'transactions', [
            'account_id'       => $destAccount->id,
            'sheba_request_id' => $requestId,
            'type'             => 'credit',
            'amount'           => 5000000
        ] );
    }


    public function test_can_cancel_sheba_request(): void
    {
        // Create test accounts
        $sourceAccount = Account::create( [
            'sheba_number' => 'IR123456789012345678901234',
            'balance'      => 995000000
        ] );

        $destAccount = Account::create( [
            'sheba_number' => 'IR987654321098765432109876',
            'balance'      => 0
        ] );

        // Create a pending sheba request
        $requestId = (string)Str::uuid();
        $shebaRequest = ShebaRequest::create( [
            'id'                => $requestId,
            'price'             => 5000000,
            'from_sheba_number' => $sourceAccount->sheba_number,
            'to_sheba_number'   => $destAccount->sheba_number,
            'status'            => 'pending',
            'note'              => 'Test transfer'
        ] );

        // Send cancel request
        $response = $this->putJson( "/api/sheba/{$requestId}", [
            'status' => 'canceled',
            'note'   => 'Cancellation reason'
        ] );

        // Assert response
        $response->assertStatus( 200 )
            ->assertJson( [
                'message' => 'Request is Canceled!',
                'request' => [
                    'id'     => $requestId,
                    'status' => 'canceled'
                ]
            ] );

        // Assert database state
        $this->assertDatabaseHas( 'sheba_requests', [
            'id'                => $requestId,
            'status'            => 'canceled',
            'cancellation_note' => 'Cancellation reason'
        ] );

        $this->assertDatabaseHas( 'accounts', [
            'id'      => $sourceAccount->id,
            'balance' => 1000000000 // Should be refunded (back to original balance)
        ] );

        $this->assertDatabaseHas( 'transactions', [
            'account_id'       => $sourceAccount->id,
            'sheba_request_id' => $requestId,
            'type'             => 'refund',
            'amount'           => 5000000
        ] );
    }


    public function test_cannot_update_non_pending_request(): void
    {
        // Create test accounts
        $sourceAccount = Account::create( [
            'sheba_number' => 'IR123456789012345678901234',
            'balance'      => 995000000
        ] );

        $destAccount = Account::create( [
            'sheba_number' => 'IR987654321098765432109876',
            'balance'      => 5000000 // Already credited
        ] );

        // Create a completed sheba request
        $requestId = (string)Str::uuid();
        $shebaRequest = ShebaRequest::create( [
            'id'                => $requestId,
            'price'             => 5000000,
            'from_sheba_number' => $sourceAccount->sheba_number,
            'to_sheba_number'   => $destAccount->sheba_number,
            'status'            => 'confirmed', // Already confirmed
            'confirmed_at'      => now(),
            'note'              => 'Test transfer'
        ] );

        // Try to cancel an already confirmed request
        $response = $this->putJson( "/api/sheba/{$requestId}", [
            'status' => 'canceled'
        ] );

        // Assert response
        $response->assertStatus( 400 )
            ->assertJson( [
                'message' => 'Sheba request is not in pending status',
                'code'    => 'INVALID_REQUEST'
            ] );

        // Assert database state unchanged
        $this->assertDatabaseHas( 'sheba_requests', [
            'id'     => $requestId,
            'status' => 'confirmed' // Status should remain confirmed
        ] );

        $this->assertDatabaseHas( 'accounts', [
            'id'      => $sourceAccount->id,
            'balance' => 995000000 // Balance should remain unchanged
        ] );

        $this->assertDatabaseHas( 'accounts', [
            'id'      => $destAccount->id,
            'balance' => 5000000 // Balance should remain unchanged
        ] );
    }
}
