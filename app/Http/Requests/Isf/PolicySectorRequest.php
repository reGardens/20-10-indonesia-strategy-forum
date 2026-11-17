<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class PolicySectorRequest extends FormRequest
{
    use IsfRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if (!$this->has('slug') || empty($this->slug)) {
            $merge['slug'] = Str::slug($this->nama ?? '');
        }
        if (!$this->has('status') || empty($this->status)) {
            $merge['status'] = 'terbit';
        }
        if ($merge) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        $sectorId = $this->route('kategori_sektor')?->id ?? $this->route('kategoriSector')?->id;

        return [
            'nama' => $this->textRules(true, 150),
            'slug' => ['required', 'string', 'max:150', 'unique:isf_policy_sectors,slug,' . ($sectorId ?? 'NULL') . ',id'],
            'deskripsi' => ['nullable', 'string'],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'status' => $this->statusRules(),
        ];
    }
}

