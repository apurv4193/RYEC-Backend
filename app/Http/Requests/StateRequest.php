<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class StateRequest extends Request {

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
                'name' => 'required|unique:state,name',
                'country_id' => 'required'
            ];
        }
        else
        {
            return [
                'name' => 'required|unique:state,name,'.$this->get('id'),
                'country_id' => 'required'
            ];
        }
       
    }

    

}
