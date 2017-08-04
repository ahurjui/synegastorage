<?php

use Illuminate\Database\Seeder;
use App\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create(array(
            'name' => 'Admin',
            'password' => Hash::make('secretpass'),
            'email' => env('ADMIN_EMAIL', '')
        ));
    }
}
