<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FeedbackFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'trigger_type' => ['required', 'in:positive,negative,always'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.type' => ['required', 'in:text,textarea,radio,checkbox,rating,email'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.required' => ['boolean'],
            'fields.*.options' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The form name is required.',
            'trigger_type.required' => 'Please select when this form should appear.',
            'trigger_type.in' => 'Invalid trigger type selected.',
            'fields.required' => 'At least one field is required.',
            'fields.min' => 'At least one field is required.',
            'fields.*.type.required' => 'Each field must have a type.',
            'fields.*.type.in' => 'Invalid field type.',
            'fields.*.label.required' => 'Each field must have a label.',
        ];
    }
}
