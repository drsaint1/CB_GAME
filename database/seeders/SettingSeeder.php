<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('settings')->insert([
            'type' => 'minimum_withdrawal',
            'value' => '1000'
        ]);

        DB::table('settings')->insert([
            'type' => 'maximum_withdrawal',
            'value' => '50000000',
        ]);

        DB::table('settings')->insert([
            'type' => 'minimum_transfer',
            'value' => '200',
        ]);

        DB::table('settings')->insert([
            'type' => 'max_transfer',
            'value' => '30000000',
        ]);
    }
}
