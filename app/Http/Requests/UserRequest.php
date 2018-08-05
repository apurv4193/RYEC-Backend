<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class UserRequest extends Request {

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
                'name' => 'required',
                'email' => 'email',
                'phone' => 'required|numeric',
                'gender' => 'required',
                'password' => 'required|min:8'
            ];
        }
        else
        {
            return [
                'name' => 'required',
                'email'  =>  'email',
                'phone' => 'required|numeric',
                'gender' => 'required',
                'password' => 'min:8'
            ];
        }
       
    }

    public function messages() {
        return [
            'name.required' => trans('labels.namerequired'),
            'email.email' => trans('labels.invalidemail'),
            'email.unique' => trans('labels.emailexist'),
            'phone.required' => trans('labels.phonerequired'),
            'phone.numeric' => trans('labels.phonedigitsrequired'),
            'occupation.required' => trans('labels.occupationrequired'),
            'gender.required' => trans('labels.genderrequired'),
        ];
    }

}
