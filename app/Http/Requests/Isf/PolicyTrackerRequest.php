<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class PolicyTrackerRequest extends FormRequest
{
    use IsfRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->slug) && !empty($this->program_name)) {
            $this->merge(['slug' => Str::slug($this->program_name)]);
        }
    }

    public function rules(): array
    {
        $param = $this->route('monitoringKebijakan') ?? $this->route('monitoring_kebijakan') ?? $this->route('policyTracker') ?? $this->route('policy_tracker');
        $id = is_object($param) ? $param->id : (is_numeric($param) ? (int) $param : null);

        return [
            'program_name' => $this->textRules(),
            'slug' => $this->slugRules('isf_policy_trackers', $id),
            'sector' => ['required', 'string', 'in:ekonomi,kesehatan,pendidikan,pertahanan,lingkungan'],
            'promise' => $this->textRules(true, 5000),
            'realization' => $this->textRules(true, 5000),
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'status_label' => ['required', 'string', 'in:tercapai,dalam_proses,tertunda,perlu_evaluasi'],
            'source_title' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'string', 'max:500'],
            'last_updated_date' => $this->dateRules(false),
            'order' => $this->orderRules(),
            'status' => $this->statusRules(),
        ];
    }
}
