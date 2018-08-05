<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class CategoryRequest extends Request {

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
                'cat_logo' => 'required|mimes:png|max:5120',
                'banner_img' => 'required|mimes:jpeg,jpg,bmp,png|max:5120',
            ];
        }
        else
        {
            return [
                'name' => 'required|max:100',
                'cat_logo' => 'mimes:png|max:2048',
                'banner_img' => 'mimes:jpeg,jpg,bmp,png|max:5120',
            ];
        }
    }

}
