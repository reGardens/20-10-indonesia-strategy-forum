<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;

class DatabasePeraturanRequest extends FormRequest
{
    use IsfRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('status') || empty($this->status)) {
            $this->merge(['status' => 'terbit']);
        }
    }

    public function rules(): array
    {
        return [
            'judul' => $this->textRules(true, 255),
            'nomor' => ['nullable', 'string', 'max:100'],
            'tahun' => ['nullable', 'string', 'max:10'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'tentang' => ['nullable', 'string'],
            'url_dokumen' => ['required', 'string', 'max:500'],
            'lembaga' => ['nullable', 'string', 'max:150'],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'status' => $this->statusRules(),
        ];
    }
}

