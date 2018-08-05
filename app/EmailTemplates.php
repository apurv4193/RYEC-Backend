<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class EmailTemplates extends Model
{
    use SoftDeletes;

    protected $table = 'email_templates';
    protected $fillable = ['id', 'name', 'pseudoname', 'subject', 'body', 'created_by', 'updated_by'];
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
            return EmailTemplates::where('id', $data['id'])->update($updateData);
        } else {
            return EmailTemplates::create($data);
        }
    }

    /**
     * get all Email Templates
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $emailTemplates = EmailTemplates::orderBy('id', 'DESC');

        if(isset($filters) && !empty($filters)) {
            if(isset($filters['pseudoname'])) {
                $emailTemplates->where('pseudoname', $filters['pseudoname']);
            }
        }

        if(isset($paginate) && $paginate == true) {
            return $emailTemplates->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $emailTemplates->get();
        }
    }

    public function getEmailContent($str , $arr) {
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
