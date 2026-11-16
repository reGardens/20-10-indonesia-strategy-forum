<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use App\Models\Isf\Tokoh;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TokohRequest extends FormRequest
{
    use IsfRules;

    /** Access is gated by the `feature:tokoh` route middleware. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Gambar diisi tetapi Alt Text kosong → pakai name sebagai Alt Text
     * (sebelumnya ditolak validasi sehingga gambar tidak tersimpan).
     */
    protected function prepareForValidation(): void
    {
        if (trim((string) $this->input('photo_url')) !== '' && trim((string) $this->input('photo_alt')) === '') {
            $this->merge(['photo_alt' => mb_substr((string) $this->input('name'), 0, 255)]);
        }
    }

    public function rules(): array
    {
        // Every matrix row has one involvement flag per column.
        $columnCount = count((array) $this->input('matrix_columns', []));
        $tokohId = $this->route('tokoh')?->id ?? $this->route('id');

        return [
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('isf_tokohs', 'slug')->ignore($tokohId),
                Rule::notIn(Tokoh::RESERVED_CATEGORIES),
            ],
            'name' => $this->textRules(),
            'position' => $this->textRules(),
            'role_category' => ['nullable', 'string', 'in:eksekutif,legislatif,yudikatif,pakar,militer,pengusaha'],
            'issues' => ['nullable', 'array'],
            'summary_analysis' => ['nullable', 'string', 'max:500'],
            'bio' => ['nullable', 'string'],
            'career_timeline' => ['nullable', 'array'],
            'education' => ['nullable', 'array'],
            'positions_history' => ['nullable', 'array'],
            'influence_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'affiliations' => ['nullable', 'array'],
            'allies' => ['nullable', 'array'],
            'critics' => ['nullable', 'array'],
            'quotes' => ['nullable', 'array'],
            'controversies' => ['nullable', 'array'],
            'aliases' => ['nullable', 'array'],
            'photo_alt' => [...$this->textRules(false), 'required_with:photo_url'],
            'photo_url' => $this->urlRules(),

            'stats' => ['required', 'array', 'min:1', 'max:8'],
            'stats.*.label' => $this->textRules(true, 100),
            'stats.*.value' => ['required'],

            'skor_value' => ['required', 'integer', 'min:0', 'max:100'],
            'skor_period' => $this->textRules(true, 100),
            'skor_explanation' => $this->textRules(true, 1000),

            'matrix_columns' => ['required', 'array', 'min:1', 'max:6'],
            'matrix_columns.*' => $this->textRules(true, 100),
            'matrix_rows' => $this->listRules(),
            'matrix_rows.*.kebijakan' => $this->textRules(),
            'matrix_rows.*.tahap' => $this->textRules(),
            'matrix_rows.*.involvement' => ['required', 'array', "size:{$columnCount}"],
            'matrix_rows.*.involvement.*' => ['boolean'],

            'related_articles' => $this->listRules(),
            'related_articles.*.title' => $this->textRules(),
            'related_articles.*.to' => $this->linkRules(),
            'related_articles.*.publishedAt' => $this->dateRules(),
            'related_articles.*.relativeLabel' => $this->textRules(false, 50),

            'calendar_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'calendar_month' => ['required', 'integer', 'min:1', 'max:12'],
            'calendar_events' => $this->listRules(),
            'calendar_events.*.date' => $this->dateRules(),
            'calendar_events.*.title' => $this->textRules(),

            'teaser' => $this->textRules(false, 1000),
            'is_featured' => ['boolean'],
            'is_influential_weekly' => ['boolean'],
            'show_in_politik' => ['boolean'],
            'status' => $this->statusRules(),
        ];
    }
}
