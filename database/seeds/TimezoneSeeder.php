<?php

use Illuminate\Database\Seeder;
use App\Timezone;
//use Illuminate\Database\Eloquent\Model;

class TimezoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        Timezone::firstOrCreate(
        [
            'name' => 'Pacific/Midway',
            'value' => 'UTC -11:00'
        ]);
        Timezone::firstOrCreate(
        [
            'name' => 'Pacific/Rarotonga',
            'value' => 'UTC -10:00',
        ]);
        Timezone::firstOrCreate(
        [
            'name' => 'Pacific/Gambier',
            'value' => 'UTC -09:00',
        ]);
        Timezone::firstOrCreate([
            'name' => 'Pacific/Pitcairn',
            'value' => 'UTC -08:00',
        ]);
        Timezone::firstOrCreate([
            'name' => 'America/Los_Angeles',
            'value' => 'UTC -07:00',
        ]);
        Timezone::firstOrCreate([
            'name' => 'America/Cambridge_Bay',
            'value' => 'UTC -06:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'America/Jamaica',
            'value' => 'UTC -05:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'America/Toronto',
            'value' => 'UTC -04:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Atlantic/Bermuda',
            'value' => 'UTC -03:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Atlantic/South_Georgia',
            'value' => 'UTC -02:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Atlantic/Cape_Verde',
            'value' => 'UTC -01:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Africa/Accra',
            'value' => 'UTC 00:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Europe/London',
            'value' => 'UTC +01:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Europe/Amsterdam',
            'value' => 'UTC +02:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Europe/Moscow',
            'value' => 'UTC +03:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Asia/Muscat',
            'value' => 'UTC +04:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Asia/Kabul',
            'value' => 'UTC +04:30'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Asia/Karachi',
            'value' => 'UTC +05:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Asia/Kolkata',
            'value' => 'UTC +05:30'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Asia/Kathmandu',
            'value' => 'UTC +05:45'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Asia/Dhaka',
            'value' => 'UTC +06:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Asia/Bangkok',
            'value' => 'UTC +07:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Asia/Hong_Kong',
            'value' => 'UTC +08:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Asia/Tokyo',
            'value' => ' UTC +09:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Australia/Adelaide',
            'value' => 'UTC +09:30'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Australia/Brisbane',
            'value' => 'UTC +10:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Asia/Magadan',
            'value' => 'UTC +11:00'
        ]);
        Timezone::firstOrCreate([
            'name' => 'Pacific/Fiji',
            'value' => 'UTC +12:00'
        ]);
    }
}
