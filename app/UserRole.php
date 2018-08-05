<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;


class UserRole extends Authenticatable
{
   use SoftDeletes;

    protected $table = 'user_role';
    protected $fillable = ['user_id', 'role_id'];
    protected $dates = ['deleted_at'];


    public function role()
    {
        return $this->belongsTo('App\Role','role_id');
    }

     public function user()
    {
        return $this->belongsTo('App\User','user_id');
    }
}
