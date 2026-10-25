<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;

class InfographicRequest extends FormRequest
{
    use IsfRules;

    /** Access is gated by the `feature:infografik` route middleware. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => $this->slugRules('isf_infographics', $this->route('infographic')?->id),
            'title' => $this->textRules(),
            'category' => $this->categoryRules(),
            'published_at' => $this->dateRules(),
            'image_alt' => $this->textRules(),
            'image_url' => $this->urlRules(),
            'status' => $this->statusRules(),
        ];
    }
}
