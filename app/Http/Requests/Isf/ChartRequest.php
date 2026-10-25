<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;

class ChartRequest extends FormRequest
{
    use IsfRules;

    /** Access is gated by the `feature:data_grafik` route middleware. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => $this->textRules(),
            'description' => $this->textRules(false, 1000),
            'unit' => $this->textRules(false, 20),
            'data' => ['required', 'array', 'min:1', 'max:30'],
            'data.*.label' => $this->textRules(true, 100),
            'data.*.value' => ['required', 'numeric', 'min:0'],
            'source_title' => $this->textRules(false),
            'source_url' => $this->urlRules(),
            'is_beranda' => ['boolean'],
            'status' => $this->statusRules(),
        ];
    }
}
