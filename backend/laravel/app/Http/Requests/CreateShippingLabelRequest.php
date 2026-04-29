<?php

namespace App\Http\Requests;

use App\Http\Controllers\ShippingLabelController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateShippingLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Protected by middleware in routes
    }

    public function rules(): array
    {
        return [
            // From address
            'from_name'    => ['required', 'string', 'max:255'],
            'from_company' => ['nullable', 'string', 'max:255'],
            'from_street1' => ['required', 'string', 'max:255'],
            'from_street2' => ['nullable', 'string', 'max:255'],
            'from_city'    => ['required', 'string', 'max:255'],
            'from_state'   => ['required', 'string', 'size:2', Rule::in(ShippingLabelController::US_STATES)],
            'from_zip'     => ['required', 'string', 'regex:/^\d{5}(-\d{4})?$/'],

            // To address
            'to_name'      => ['required', 'string', 'max:255'],
            'to_company'   => ['nullable', 'string', 'max:255'],
            'to_street1'   => ['required', 'string', 'max:255'],
            'to_street2'   => ['nullable', 'string', 'max:255'],
            'to_city'      => ['required', 'string', 'max:255'],
            'to_state'     => ['required', 'string', 'size:2', Rule::in(ShippingLabelController::US_STATES)],
            'to_zip'       => ['required', 'string', 'regex:/^\d{5}(-\d{4})?$/'],

            // Package (weight in oz, dimensions in inches)
            'weight' => ['required', 'numeric', 'min:0.1'],
            'length' => ['required', 'numeric', 'min:0.1'],
            'width'  => ['required', 'numeric', 'min:0.1'],
            'height' => ['required', 'numeric', 'min:0.1'],
        ];
    }
}
