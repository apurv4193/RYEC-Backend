<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class Cms extends Model
{
    use SoftDeletes;

    protected $table = 'cms';
    protected $fillable = ['id', 'title', 'slug', 'body', 'type' ,'created_by', 'updated_by'];
    protected $dates = ['deleted_at'];

    /**
     * Insert and Update Email Template
     */
    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) {
            $updateData = [];
            foreach ($this->fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            return Cms::where('id', $data['id'])->update($updateData);
        } else {
            return Cms::create($data);
        }
    }

    /**
     * get all Email Templates
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $cms = Cms::orderBy('id', 'DESC');

        if(isset($filters) && !empty($filters)) {
            if(isset($filters['slug'])) {
                $cms->where('slug', $filters['slug']);
            }
        }

        if(isset($paginate) && $paginate == true) {
            return $cms->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $cms->get();
        }
    }

    public function getCmsContent($str , $arr) {
        if (is_array($arr))
        {
            reset($arr);
            $keys = array_keys($arr);
            array_walk($keys, create_function('&$val', '$val = "[$val]";'));
            $vals = array_values($arr);
            return preg_replace('/^[0-9a-zA-Z\/_\/s\/-]+/', '', str_replace($keys, $vals, $str));
        }
        else
        {
            return $str;
        }
    }
}
