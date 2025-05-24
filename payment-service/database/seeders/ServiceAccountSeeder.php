<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('service_accounts')->insert([
            'name' => 'API Gateway',
            'service_id' => env('API_GATEWAY_SERVICE_ID'),
            'service_secret' => env('API_GATEWAY_SERVICE_SECRET'),
        ]);

        DB::table('service_accounts')->insert([
            'name' => 'Corporate Service',
            'service_id' => env('CORPORATE_SERVICE_ID'),
            'service_secret' => env('CORPORATE_SERVICE_SECRET'),
        ]);

        DB::table('service_accounts')->insert([
            'name' => 'Payment Service',
            'service_id' => env('PAYMENT_SERVICE_ID'),
            'service_secret' => env('PAYMENT_SERVICE_SECRET'),
        ]);
    }
}
