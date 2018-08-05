<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class BusinessContactRequest extends Request {

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

        return [
            'phone' => 'regex:/^[0-9_\-\+]*$/',
            'mobile' => 'required|numeric',
            'address' => 'required',
        ];
       
    }

    public function messages() {
        return [
            'mobile.required' => trans('labels.mobilerequired'),
            'mobile.numeric' => trans('labels.mobiledigitsrequired'),
            'address.required' => trans('labels.addressrequired'),
        ];
    }

}
