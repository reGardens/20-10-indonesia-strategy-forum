<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    use IsfRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('category')?->id ?? $this->route('category') ?? $this->route('kategori')?->id ?? $this->route('kategori');

        return [
            'name' => $this->textRules(true, 100),
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('isf_categories', 'slug')->ignore($id),
            ],
            'description' => $this->textRules(false, 500),
            'color' => ['nullable', 'string', 'max:50'],
            'order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'status' => $this->statusRules(),
        ];
    }
}
