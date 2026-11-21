<?php

namespace App\Http\Requests\Isf;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KategoriLembagaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('kategoriLembaga')?->id;

        return [
            'nama' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('isf_kategori_lembagas', 'slug')->ignore($id)],
            'deskripsi' => ['nullable', 'string'],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:terbit,draf,arsip'],
        ];
    }
}

