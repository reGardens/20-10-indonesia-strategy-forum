<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParliamentSeatRequest extends FormRequest
{
    use IsfRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if (!$this->has('warna_partai') || empty($this->warna_partai)) {
            $merge['warna_partai'] = '#E10600';
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
            'periode' => $this->textRules(true, 50),
            'lembaga' => ['required', 'string', Rule::in(['DPR', 'DPD', 'MPR'])],
            'partai' => $this->textRules(true, 100),
            'singkatan' => $this->textRules(false, 50),
            'jumlah_kursi' => ['required', 'integer', 'min:0', 'max:10000'],
            'warna_partai' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'string', 'max:500'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['nullable', 'string', Rule::in(['terbit', 'draf'])],
        ];
    }
}

