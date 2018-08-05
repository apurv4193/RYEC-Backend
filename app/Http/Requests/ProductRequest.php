<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class ProductRequest extends Request {

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
                'name' => 'required',
                'description'  =>  'required',
                'logo' => 'required|mimes:jpeg,jpg,bmp,png|max:5120',
            ];
        }
        else
        {
            return [
                'name' => 'required',
                'description'  =>  'required',
                'logo' => 'mimes:jpeg,jpg,bmp,png|max:5120',
            ];
        }
        
       
    }

    public function messages() {
        return [
            'name.required' => trans('labels.namerequired'),
            'description.required' => trans('labels.descriptionrequired'),
            'logo.max' => trans('labels.maxfilesizevalidate'),
            'logo.required' => trans('labels.logorequired')
        ];
    }

}
