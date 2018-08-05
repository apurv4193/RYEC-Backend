<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class EmailTemplatesRequest extends Request {

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
            'name' => 'required|regex:/^[\pL\s\-]+$/u',
            'pseudoname' => 'required',
            'subject' => 'required|regex:/^[\pL\s\-]+$/u',
            'body' => 'required',
        ];
    }

    public function messages() {
        return [
            'name.required' => trans('labels.templatenamerequired'),
            'name.regex' => trans('validation.templatenamelettersonly'),
            'pseudoname.required' => trans('labels.templatepseudonamerequired'),
            'subject.required' => trans('labels.subjectrequired'),
            'subject.regex' => trans('validation.templatesubjectlettersonly'),
            'body.required' => trans('labels.bodyrequired'),
        ];
    }

}
