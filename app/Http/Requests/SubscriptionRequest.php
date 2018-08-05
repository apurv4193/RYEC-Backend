<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class SubscriptionRequest extends Request {

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
            'months' => 'required|numeric',
            'price' => 'required|numeric',
        ];
    }

    public function messages() {
        return [
            'name.required' => trans('labels.namerequired'),
            'months.required' => trans('labels.noofmonthrequired'),
            'months.numeric' => trans('labels.noofmonthdigitsrequired'),
            'price.required' => trans('labels.pricerequired'),
            'price.numeric' => trans('labels.pricedigitsrequired'),
        ];
    }

}
