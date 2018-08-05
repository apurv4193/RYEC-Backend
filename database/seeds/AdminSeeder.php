<?php

use Illuminate\Database\Seeder;
use App\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::firstOrCreate(
            [
                'name' => 'Super Admin',
                'email' => 'admin@admin.com',
                //'password' => '$2y$10$tNxqWWEbEfHksxeJDFkRgeZN09naxQTvRAhRbdEdrfnuxFuc2rlwK',
                'password' => '$2y$10$TUCXgArDT0AL/m1DA77DX.3BO2HpxN9Rmq2vw7TuGwdL.1vWgcPxO',
                'phone' => '9879876767'
            ]);
        User::firstOrCreate(
            [
                'name' => 'Super Admin',
                'email' => 'dipeninx@admin.com',
                'password' => '$2y$10$RNKKvgGdybl6PuaheNTT4uxB2aHeNvV3AIFPdom.IjTNJ9PFA2C2i',
                'phone' => '11111111112'
            ]);
        User::firstOrCreate(
            [
                'name' => 'Super Admin',
                'email' => 'yuvrajinx@admin.com',
                'password' => '$2y$10$VSvxIHHpxNoTbPmkyVX8IOUlWK74BGplzPErpGoRlXr8tLktsi0NC',
                'phone' => '11111111113'
            ]);
        User::firstOrCreate(
            [
                'name' => 'Super Admin',
                'email' => 'jaydevsinh@admin.com',
                'password' => '$2y$10$lieO3r7nQgmgA9lJTtFHvu6j.xM4JTezY.TP1C2n7eGdUkGpNEnPW',
                'phone' => '11111111114'
            ]);
    }
}
