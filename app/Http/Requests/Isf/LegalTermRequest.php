<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LegalTermRequest extends FormRequest
{
    use IsfRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        $termName = $this->nama ?? $this->istilah ?? '';
        if ($this->has('nama') && !$this->has('istilah')) {
            $merge['istilah'] = $this->nama;
        } elseif ($this->has('istilah') && !$this->has('nama')) {
            $merge['nama'] = $this->istilah;
        }

        if (!$this->has('slug') || empty($this->slug)) {
            $merge['slug'] = Str::slug($termName);
        }
        if (!$this->has('abjad') || empty($this->abjad)) {
            $merge['abjad'] = strtoupper(substr(trim($termName ?: 'A'), 0, 1));
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
        return [
            'nama' => ['nullable', 'string', 'max:150'],
            'istilah' => ['required_without:nama', 'nullable', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150'],
            'abjad' => ['nullable', 'string', 'max:2'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'status' => $this->statusRules(),
        ];
    }
}

