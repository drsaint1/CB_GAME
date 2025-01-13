<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessPromoionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('business_promotion_plans')->insert([
            'plan_name' => 'Pay monthly',
            'price' => 7000,
            'duration' => 'month',
            'discount' => 10,
        ]);

        DB::table('business_promotion_plans')->insert([
            'plan_name' => 'Pay weekly',
            'price' => 1800,
            'duration' => 'week',
            'discount' => 15,
        ]);

        DB::table('business_promotion_plans')->insert([
            'plan_name' => 'Pay daily',
            'price' => 500,
            'duration' => 'day',
            'discount' => 0,
        ]);
    }
}
