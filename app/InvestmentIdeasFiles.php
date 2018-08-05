<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class InvestmentIdeasFiles extends Model
{
    use SoftDeletes, CascadeSoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'investment_ideas_files';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    
    protected $fillable = ['investment_id', 'file_name', 'file_type'];
    
    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
    protected $primaryKey = 'id';

    /**
     * Insert and Update Investment files
     */

    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0)
        {
            $updateData = [];
            foreach ($this->fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            return InvestmentIdeasFiles::where('id', $data['id'])->update($updateData);
        } else {
            return InvestmentIdeasFiles::create($data);
        }
    }
    
   
}
