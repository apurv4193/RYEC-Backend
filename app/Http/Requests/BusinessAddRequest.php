<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class BusinessAddRequest extends Request {

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
                'name' => 'required',
                'description' => 'required',
                'email_id'  =>  'email',
                'establishment_year'  =>  'numeric|digits:4',
                'business_images.*' => 'required|mimes:jpeg,jpg,bmp,png|max:5120',
                'phone' => 'regex:/^[0-9_\-\+]*$/',
                'mobile' => 'required|numeric',
                'address' => 'required',
                'facebook_url' => 'url',
                'twitter_url' => 'url',
                'linkedin_url' => 'url',
                'instagram_url' => 'url',
                
            ];
    }

    

}
