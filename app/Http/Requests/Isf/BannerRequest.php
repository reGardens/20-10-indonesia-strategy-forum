<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;

class BannerRequest extends FormRequest
{
    use IsfRules;

    /** Access is gated by the `feature:banner` route middleware. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kicker' => $this->textRules(true, 100),
            'title' => $this->textRules(true, 255),
            'deck' => $this->textRules(false, 1000),
            'to' => $this->linkRules(false),
            'image_url' => $this->urlRules(false),
            'image_label' => $this->textRules(false, 255),
            'order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'status' => $this->statusRules(),
        ];
    }
}
