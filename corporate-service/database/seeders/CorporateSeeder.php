<?php

namespace Database\Seeders;

use App\Models\Corporate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CorporateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Corporate::factory(10)->create();
    }
}
