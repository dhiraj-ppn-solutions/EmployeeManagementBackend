<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            [
                'country_id' => 1,
                'name' => 'Haryana',
                'code' => 'HR',
                'gst_code' => '06',
                'capital' => 'Chandigarh',
                'status' => 1,
            ],
            [
                'country_id' => 1,
                'name' => 'Punjab',
                'code' => 'PB',
                'gst_code' => '03',
                'capital' => 'Chandigarh',
                'status' => 1,
            ],
            [
                'country_id' => 1,
                'name' => 'Bihar',
                'code' => 'BR',
                'gst_code' => '10',
                'capital' => 'Patna',
                'status' => 1,
            ],
            [
                'country_id' => 2,
                'name' => 'California',
                'code' => 'CA',
                'gst_code' => null,
                'capital' => 'Sacramento',
                'status' => 1,
            ]
        ];

        foreach ($states as $state) {
            DB::table('states')->updateOrInsert(
                ['country_id' => $state['country_id'], 'name' => $state['name']],
                $state
            );
        }
    }
}