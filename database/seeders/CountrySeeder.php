<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (DB::table('countries')->count() === 0) {
            DB::table('countries')->insert([
            [
                'id' => 1,
                'name' => 'India',
                'short_name' => 'IN',
                'iso2' => 'IN',
                'iso3' => 'IND',
                'phone_code' => '91',
                'currency' => 'INR',
                'currency_symbol' => '₹',
                'capital' => 'New Delhi',
                'nationality' => 'Indian',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'United States',
                'short_name' => 'US',
                'iso2' => 'US',
                'iso3' => 'USA',
                'phone_code' => '1',
                'currency' => 'USD',
                'currency_symbol' => '$',
                'capital' => 'Washington D.C.',
                'nationality' => 'American',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
        }
    }
}
