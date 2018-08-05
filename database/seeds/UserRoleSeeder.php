<?php

use Illuminate\Database\Seeder;
use App\UserRole;
//use Illuminate\Database\Eloquent\Model;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        UserRole::firstOrCreate([
            'user_id' => '1',
            'role_id' => '1'
        ]);
    }
}
