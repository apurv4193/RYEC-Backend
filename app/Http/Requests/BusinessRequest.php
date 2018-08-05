<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class BusinessRequest extends Request {

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
                'email_id'  =>  'email',
                'establishment_year'  =>  'numeric|digits:4',
                'business_images.*' => 'required|mimes:jpeg,jpg,bmp,png|max:5120'
                
            ];
        }
        else
        {
            return [
                'name' => 'required',
                'email_id'  =>  'email',
                'establishment_year'  =>  'numeric|digits:4',
                'business_images.*' => 'mimes:jpeg,jpg,bmp,png|max:5120'
               
            ];
        }
        
       
    }

    

}
