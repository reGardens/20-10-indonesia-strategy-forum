<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SurveyRequest extends FormRequest
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
        $param = $this->route('survey') ?? $this->route('survei');
        $id = is_object($param) ? $param->id : (is_numeric($param) ? (int) $param : null);

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('isf_surveys', 'slug')->ignore($id)],
            'institution' => ['required', 'string', 'max:150'],
            'is_isf' => ['boolean'],
            'topic' => ['required', 'string', Rule::in(['politik', 'ekonomi', 'sosial', 'hukum'])],
            'region' => ['required', 'string', 'max:50'],
            'survey_period' => ['nullable', 'string', 'max:150'],
            'release_date' => ['nullable', 'date'],
            'sample_size' => ['required', 'integer', 'min:1'],
            'margin_of_error' => ['nullable'],
            'confidence_level' => ['nullable'],
            'sampling_method' => ['nullable', 'string', 'max:255'],
            'interview_method' => ['nullable', 'string', 'max:150'],
            'headline_stat_value' => ['nullable', 'string', 'max:50'],
            'headline_stat_label' => ['nullable', 'string', 'max:255'],
            'headline_stat_comparison' => ['nullable', 'string', 'max:150'],
            'editorial_intro' => ['nullable', 'string'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'questions' => ['nullable', 'array'],
            'is_member_active' => ['nullable', 'boolean'],
            'summary' => ['nullable', 'string'],
            'expert_analysis' => ['nullable', 'string'],
            'chart_data' => ['nullable', 'array'],
            'pdf_path' => ['nullable', 'string', 'max:500'],
            'pdf_size' => ['nullable', 'string', 'max:50'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['boolean'],
            'status' => ['required', Rule::in(['terbit', 'draft', 'draf'])],
        ];
    }
}
