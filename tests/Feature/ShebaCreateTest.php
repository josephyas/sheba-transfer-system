<?php

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShebaCreateTest extends TestCase
{

    public function test_can_create_sheba_request(): void
    {
        $sourceAccount = Account::create( [
            'sheba_number' => 'IR888456789012345678901234',
            'balance'      => 1000000000
        ] );

        $destAccount = Account::create( [
            'sheba_number' => 'IR777654321098765432109876',
            'balance'      => 0
        ] );


        $response = $this->postJson( '/api/sheba', [
            'price'           => 5000000,
            'fromShebaNumber' => $sourceAccount->sheba_number,
            'ToShebaNumber'   => $destAccount->sheba_number,
            'note'            => 'Test transfer'
        ] );

        $response->assertStatus( 201 )
            ->assertJsonStructure( [
                'message',
                'request' => [
                    'id',
                    'price',
                    'status',
                    'fromShebaNumber',
                    'ToShebaNumber',
                    'createdAt'
                ]
            ] );

        $this->assertDatabaseHas( 'accounts', [
            'id'      => $sourceAccount->id,
            'balance' => 995000000 // Original balance - transfer amount
        ] );

        $this->assertDatabaseHas( 'sheba_requests', [
            'from_sheba_number' => $sourceAccount->sheba_number,
            'to_sheba_number'   => $destAccount->sheba_number,
            'price'             => 5000000,
            'status'            => 'pending'
        ] );

        $this->assertDatabaseHas( 'transactions', [
            'account_id' => $sourceAccount->id,
            'type'       => 'debit',
            'amount'     => 5000000
        ] );
    }


    public function test_cannot_create_sheba_request_with_insufficient_balance(): void
    {
        $sourceAccount = Account::create( [
            'sheba_number' => 'IR666666789012345678901234',
            'balance'      => 1000000 // Only 1 million
        ] );

        $destAccount = Account::create( [
            'sheba_number' => 'IR567564321098765432109876',
            'balance'      => 0
        ] );

        // Send request with amount > balance
        $response = $this->postJson( '/api/sheba', [
            'price'           => 5000000, // 5 million > 1 million balance
            'fromShebaNumber' => $sourceAccount->sheba_number,
            'ToShebaNumber'   => $destAccount->sheba_number,
            'note'            => 'Test transfer'
        ] );

        $response->assertStatus( 422 )
            ->assertJson( [
                'message' => 'Insufficient balance for this transfer',
                'code'    => 'INSUFFICIENT_BALANCE'
            ] );

        $this->assertDatabaseHas( 'accounts', [
            'id'      => $sourceAccount->id,
            'balance' => 1000000 // Balance should remain unchanged
        ] );

        $this->assertDatabaseMissing( 'sheba_requests', [
            'from_sheba_number' => $sourceAccount->sheba_number,
            'to_sheba_number'   => $destAccount->sheba_number,
            'price'             => 5000000
        ] );
    }


    public function test_validates_sheba_number_format(): void
    {
        // Send request with invalid sheba number format
        $response = $this->postJson( '/api/sheba', [
            'price'           => 5000000,
            'fromShebaNumber' => 'INVALID-SHEBA-NUMBER', // Invalid format
            'ToShebaNumber'   => 'IR987654321098765432109876',
            'note'            => 'Test transfer'
        ] );


        $response->assertStatus( 422 )
            ->assertJsonValidationErrors( [ 'fromShebaNumber' ] );
    }
}
