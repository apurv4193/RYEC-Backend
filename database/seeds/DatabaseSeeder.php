<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RolesSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(UserRoleSeeder::class);
        $this->call(TimezoneSeeder::class);
    }
}
