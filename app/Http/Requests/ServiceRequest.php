<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class ServiceRequest extends Request {

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

        if ($this->get('id') == 0) 
        {
            return [
                'name' => 'required|max:100',
                'logo' => 'required|mimes:jpeg,jpg,bmp,png|max:5120',
                'description' => 'required',
            ];
        }
        else
        {
            return [
                'name' => 'required|max:100',
                'logo' => 'mimes:jpeg,jpg,bmp,png|max:5120',
                'description' => 'required',
            ];
        }
       
    }

    public function messages() {
        return [
            'name.required' => trans('labels.titlerequired'),
            'name.max' => trans('labels.titlemaxrequired'),
            'logo.max' => trans('labels.maxfilesizevalidate')
        ];
    }

}
