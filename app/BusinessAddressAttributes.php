<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Config;

class BusinessAddressAttributes extends Model
{
    use SoftDeletes;

    protected $table = 'business_address_attributes';
    protected $fillable = [
        'business_id',
        'village',
        'taluka',
        'district',
        'premise',
        'route',
        'neighborhood',
        'street_number',
        'sublocality_level_3',
        'sublocality_level_2',
        'sublocality_level_1',
        'locality',
        'administrative_area_level_2',
        'administrative_area_level_1',
        'country',
        'postal_code',
        'address'
    ];
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
            return BusinessAddressAttributes::where('id', $data['id'])->update($updateData);
        } else {
            return BusinessAddressAttributes::create($data);
        }
    }

    public function business()
    {
        return $this->belongsTo('App\Business', 'business_id');
    }
}
