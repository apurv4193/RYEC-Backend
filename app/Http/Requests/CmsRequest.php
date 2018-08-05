<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class CmsRequest extends Request {

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
            'title' => 'required|regex:/^[\pL\s\-]+$/u',
            'slug' => 'required',
            'body' => 'required',
        ];
    }

    public function messages() {
        return [
            'title.required' => trans('labels.templatenamerequired'),
            'title.regex' => trans('validation.templatenamelettersonly'),
            'slug.required' => trans('labels.templatepseudonamerequired'),
            'body.required' => trans('labels.bodyrequired'),
        ];
    }

}
