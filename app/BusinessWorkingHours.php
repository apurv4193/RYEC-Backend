<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class BusinessWorkingHours extends Model
{
    use SoftDeletes;

    protected $table = 'business_working_hours';
    protected $fillable = ['id','business_id','timezone', 'mon_start_time','mon_end_time','mon_open_close',
                                          'tue_start_time','tue_end_time','tue_open_close',
                                          'wed_start_time','wed_end_time','wed_open_close',
                                          'thu_start_time','thu_end_time','thu_open_close',
                                          'fri_start_time','fri_end_time','fri_open_close',
                                          'sat_start_time','sat_end_time','sat_open_close',
                                          'sun_start_time','sun_end_time','sun_open_close'
                        ];
    protected $dates = ['deleted_at'];

	public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) {
            $updateData = [];

            foreach ($this->fillable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }
            return BusinessWorkingHours::where('id', $data['id'])->update($updateData);
        } else {
            return BusinessWorkingHours::create($data);
        }
    }

    /**
     * get all Business for admin
     */
    public function getAll($filters = array(), $paginate = false)
    {
        $businessWorkingHours = BusinessWorkingHours::orderBy('id', 'DESC');
        
        if(isset($paginate) && $paginate == true) {
            return $businessWorkingHours->paginate(Config::get('constant.ADMIN_RECORD_PER_PAGE'));
        } else {
            return $businessWorkingHours->get();
        }
    }
   
}
