<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Config;
use Auth;

class UsersDevice extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_device';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['user_id','device_id','device_type','device_token'];
    
    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];


    public function insertUpdate($data)
    {
        $response = UsersDevice::firstOrCreate(['user_id' => $data['user_id'],'device_type'=> $data['device_type'],'device_id' => $data['device_id']]);
        $response->device_token = $data['device_token'];
        $response->save();
        return $response;
    }

    /**
     * get all user device detail for admin
     */
    public function getByUserId($userId)
    {
        return UsersDevice::where('user_id',$userId)->get();
    }
}
