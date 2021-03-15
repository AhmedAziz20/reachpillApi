<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        App\User::create([
            'email' => 'admin@admin.com',
            'name' => 'admin',
            'password' => bcrypt('123456'),
            'role' =>'admin',
            'phone' =>'123456',
            'address' =>'test'
        ]);

        App\User::create([
            'email' => 'user@user.com',
            'name' => 'user',
            'password' => bcrypt('123456'),
            'role' =>'user',
            'phone' =>'123456',
            'address' =>'test'
        ]);

        App\User::create([
            'email' => 'vendor@vendor.com',
            'name' => 'vendor',
            'password' => bcrypt('123456'),
            'role' =>'vendor',
            'phone' =>'123456',
            'address' =>'test'
        ]);
    }
}
