<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class OwnerRequest extends Request {

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {

        if($this->get('id') == 0)
        {
            return [
                'full_name' => 'required'
                // 'mobile'  =>  'required|numeric|digits:10',
                // 'email_id' => 'required|email',
                // 'photo' => 'required|mimes:jpeg,jpg,bmp,png|max:5120',
            ];
        }
        else
        {

            return [
                'full_name' => 'required'
                // 'mobile'  =>  'required|numeric|digits:10',
                // 'email_id' => 'required|email',
                // 'photo' => 'mimes:jpeg,jpg,bmp,png|max:5120',
            ];
        }
    }


}
