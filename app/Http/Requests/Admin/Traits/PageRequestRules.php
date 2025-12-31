<?php

namespace App\Http\Requests\Admin\Traits;

use Illuminate\Validation\Rule;

trait PageRequestRules
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['navigation', 'group', 'document'])],
            'icon' => ['nullable', 'string', 'max:50'],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'parent_id' => ['nullable', 'exists:pages,id'],
            'is_default' => ['boolean'],
            'is_expanded' => ['boolean'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The page title is required.',
            'title.max' => 'The title must not exceed 255 characters.',
            'type.required' => 'Please select a page type.',
            'type.in' => 'Invalid page type selected.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Invalid status selected.',
            'parent_id.exists' => 'The selected parent page does not exist.',
            'seo_description.max' => 'The SEO description must not exceed 500 characters.',
        ];
    }
}
