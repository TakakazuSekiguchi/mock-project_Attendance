<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $params = [
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('admin000'),
        ];
        DB::table('admins')->insert($params);
    }
}
