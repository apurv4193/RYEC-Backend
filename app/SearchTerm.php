<?php

namespace App;


use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class SearchTerm extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'search_term';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['search_term', 'city', 'type', 'count'];
    
    
    
}
