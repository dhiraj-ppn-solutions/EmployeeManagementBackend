<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            // Haryana
            ['country_id' => 1, 'state_name' => 'Haryana', 'name' => 'Hisar', 'zipcode' => '125001', 'latitude' => 29.1492, 'longitude' => 75.7217, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Haryana', 'name' => 'Rohtak', 'zipcode' => '124001', 'latitude' => 28.8955, 'longitude' => 76.6066, 'status' => 1],
            // Punjab
            ['country_id' => 1, 'state_name' => 'Punjab', 'name' => 'Amritsar', 'zipcode' => '143001', 'latitude' => 31.6340, 'longitude' => 74.8723, 'status' => 1],
            // California
            ['country_id' => 2, 'state_name' => 'California', 'name' => 'Los Angeles', 'zipcode' => '90001', 'latitude' => 34.0522, 'longitude' => -118.2437, 'status' => 1],
            // Bihar
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Patna', 'zipcode' => '800001', 'latitude' => 25.5941, 'longitude' => 85.1376, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Gaya', 'zipcode' => '823001', 'latitude' => 24.7964, 'longitude' => 84.9996, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Bhagalpur', 'zipcode' => '812001', 'latitude' => 25.2425, 'longitude' => 87.0145, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Muzaffarpur', 'zipcode' => '842001', 'latitude' => 26.1209, 'longitude' => 85.3647, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Purnia', 'zipcode' => '854301', 'latitude' => 25.7771, 'longitude' => 87.4753, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Darbhanga', 'zipcode' => '846004', 'latitude' => 26.1542, 'longitude' => 85.8918, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Bihar Sharif', 'zipcode' => '803101', 'latitude' => 25.1982, 'longitude' => 85.5149, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Arrah', 'zipcode' => '802301', 'latitude' => 25.5560, 'longitude' => 84.6677, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Begusarai', 'zipcode' => '851101', 'latitude' => 25.4182, 'longitude' => 86.1272, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Katihar', 'zipcode' => '854105', 'latitude' => 25.5524, 'longitude' => 87.5724, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Munger', 'zipcode' => '811201', 'latitude' => 25.3748, 'longitude' => 86.4735, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Chhapra', 'zipcode' => '841301', 'latitude' => 25.7811, 'longitude' => 84.7271, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Saharsa', 'zipcode' => '852201', 'latitude' => 25.8835, 'longitude' => 86.6006, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Hajipur', 'zipcode' => '844101', 'latitude' => 25.6858, 'longitude' => 85.2237, 'status' => 1],
            ['country_id' => 1, 'state_name' => 'Bihar', 'name' => 'Bettiah', 'zipcode' => '845438', 'latitude' => 26.8014, 'longitude' => 84.5029, 'status' => 1],
        ];

        // Cache state names to IDs mapping to prevent repeated database queries
        $stateIds = DB::table('states')->pluck('id', 'name')->toArray();

        foreach ($cities as $city) {
            $stateId = $stateIds[$city['state_name']] ?? null;
            if ($stateId) {
                DB::table('cities')->updateOrInsert(
                    ['country_id' => $city['country_id'], 'state_id' => $stateId, 'name' => $city['name']],
                    [
                        'country_id' => $city['country_id'],
                        'state_id' => $stateId,
                        'name' => $city['name'],
                        'zipcode' => $city['zipcode'],
                        'latitude' => $city['latitude'],
                        'longitude' => $city['longitude'],
                        'status' => $city['status'],
                    ]
                );
            }
        }
    }
}