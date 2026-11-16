<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class PolicyBriefRequest extends FormRequest
{
    use IsfRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->slug) && !empty($this->title)) {
            $this->merge(['slug' => Str::slug($this->title)]);
        }
    }

    public function rules(): array
    {
        $param = $this->route('policyBrief') ?? $this->route('policy_brief');
        $id = is_object($param) ? $param->id : (is_numeric($param) ? (int) $param : null);

        return [
            'title' => $this->textRules(),
            'slug' => $this->slugRules('isf_policy_briefs', $id),
            'sector' => ['required', 'string', 'in:ekonomi,kesehatan,pendidikan,pertahanan,lingkungan'],
            'author' => $this->textRules(true, 150),
            'institution' => ['nullable', 'string', 'max:255'],
            'executive_summary' => $this->textRules(true, 10000),
            'key_recommendations' => ['nullable', 'array', 'max:20'],
            'key_recommendations.*' => ['string', 'max:500'],
            'pdf_path' => ['required', 'string', 'max:500'],
            'pdf_size' => ['nullable', 'string', 'max:50'],
            'published_at' => $this->dateRules(false),
            'order' => $this->orderRules(),
            'status' => $this->statusRules(),
        ];
    }
}
