<?php

use Illuminate\Database\Seeder;
use App\Role;
//use Illuminate\Database\Eloquent\Model;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       $roles = [
            'superadmin' => 'Super Admin',
            'user' => 'User',
            'agent' => 'Agent'
        ];

        foreach($roles as $slug => $role) {
            Role::firstOrCreate(['slug' => $slug, 'name' => $role]);
        }
    }
}
