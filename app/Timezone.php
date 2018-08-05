<?php

namespace App;


use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class Timezone extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'timezone';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name','value'];
    
    
       
}
