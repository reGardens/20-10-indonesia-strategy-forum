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

    public function rules(): array
    {
        return [
            'periode' => $this->textRules(true, 50),
            'lembaga' => ['required', 'string', Rule::in(['DPR', 'DPD', 'MPR'])],
            'partai' => $this->textRules(true, 100),
            'singkatan' => $this->textRules(false, 50),
            'jumlah_kursi' => ['required', 'integer', 'min:0', 'max:10000'],
            'warna_partai' => ['required', 'string', 'max:50'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:999'],
            'status' => $this->statusRules(),
        ];
    }
}

