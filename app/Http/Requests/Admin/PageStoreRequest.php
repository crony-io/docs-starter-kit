<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Admin\Traits\PageRequestRules;
use Illuminate\Foundation\Http\FormRequest;

class PageStoreRequest extends FormRequest
{
    use PageRequestRules;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }
}
