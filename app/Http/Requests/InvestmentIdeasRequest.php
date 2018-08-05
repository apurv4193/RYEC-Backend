<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class InvestmentIdeasRequest extends Request {

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
            'title' => 'required',
            'description'  =>  'required',
            //'ins_amount'  =>  'required',
            'project_duration'  =>  'required',
            'member_name'  =>  'required',
            'member_email'  =>  'required|email',
            'member_phone'  =>  'required|numeric',
            'offering_percent' => 'required'
        ];       
    }

}
