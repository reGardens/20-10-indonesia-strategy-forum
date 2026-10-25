<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use App\Support\IsfOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlertRequest extends FormRequest
{
    use IsfRules;

    /** Access is gated by the `feature:alert` route middleware. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => $this->textRules(),
            'summary' => $this->textRules(true, 2000),
            'priority' => ['required', 'string', Rule::in(IsfOptions::PRIORITIES)],
            'date' => $this->dateRules(),
            'sektor' => $this->textRules(true, 100),
            'link_url' => $this->linkRules(),
            'status' => $this->statusRules(),
        ];
    }
}
