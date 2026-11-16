<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;

class LetterModerationRequest extends FormRequest
{
    use IsfRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'moderation_status' => ['required', 'string', 'in:pending,disetujui,ditolak'],
            'moderation_notes' => ['nullable', 'string', 'max:2000'],
            'title' => ['sometimes', 'string', 'max:300'],
            'body' => ['sometimes', 'string', 'max:10000'],
            'order' => $this->orderRules(),
            'status' => $this->statusRules(),
        ];
    }
}

