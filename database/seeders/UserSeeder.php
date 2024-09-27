<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true
        ]);
        DB::table('users')->insert([
            'name' => 'John koye',
            'email' => 'john.koye@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false
        ]);
    }
}