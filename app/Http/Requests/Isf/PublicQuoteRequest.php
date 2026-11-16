<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;

class PublicQuoteRequest extends FormRequest
{
    use IsfRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quote' => $this->textRules(true, 2000),
            'author_name' => $this->textRules(true, 255),
            'author_title' => ['nullable', 'string', 'max:255'],
            'author_type' => ['required', 'string', 'in:tokoh,netizen'],
            'tokoh_id' => ['nullable', 'exists:isf_tokohs,id'],
            'topic' => ['nullable', 'string', 'max:255'],
            'source_title' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'string', 'max:500'],
            'quote_date' => $this->dateRules(false),
            'order' => $this->orderRules(),
            'status' => $this->statusRules(),
        ];
    }
}

