<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Account::create( [
            'sheba_number' => 'IR123456789012345678901234',
            'balance'      => 1000000000 // 1 billion
        ] );

        Account::create( [
            'sheba_number' => 'IR987654321098765432109876',
            'balance'      => 500000000 // 500 million
        ] );

        Account::create( [
            'sheba_number' => 'IR667654321098765432109876',
            'balance'      => 100000000 // 100 million
        ] );

        Account::create( [
            'sheba_number' => 'IR227654321098765432109876',
            'balance'      => 300000000 // 300 million
        ] );
    }
}
