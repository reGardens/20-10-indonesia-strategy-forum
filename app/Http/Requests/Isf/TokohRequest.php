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
        $merge = [];
        if (trim((string) $this->input('photo_url')) !== '' && trim((string) $this->input('photo_alt')) === '') {
            $merge['photo_alt'] = mb_substr((string) $this->input('name'), 0, 255);
        }
        if (!$this->has('calendar_year') || empty($this->input('calendar_year'))) {
            $merge['calendar_year'] = (int) date('Y');
        }
        if (!$this->has('calendar_month') || empty($this->input('calendar_month'))) {
            $merge['calendar_month'] = (int) date('n');
        }
        if (!$this->has('stats') || !is_array($this->input('stats'))) {
            $merge['stats'] = [];
        }
        if (!$this->has('calendar_events') || !is_array($this->input('calendar_events'))) {
            $merge['calendar_events'] = [];
        }
        if (!$this->has('skor_value') || $this->input('skor_value') === null || $this->input('skor_value') === '') {
            $merge['skor_value'] = 75;
        }
        if (!$this->has('skor_period') || $this->input('skor_period') === null || $this->input('skor_period') === '') {
            $merge['skor_period'] = 'Pekan Ini';
        }
        if (!$this->has('skor_explanation') || $this->input('skor_explanation') === null || $this->input('skor_explanation') === '') {
            $merge['skor_explanation'] = '-';
        }
        if (!empty($merge)) {
            $this->merge($merge);
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

            'stats' => ['nullable', 'array'],
            'stats.*.label' => ['nullable', 'string', 'max:100'],
            'stats.*.value' => ['nullable'],

            'skor_value' => ['nullable', 'integer', 'min:0', 'max:100'],
            'skor_period' => ['nullable', 'string', 'max:100'],
            'skor_explanation' => ['nullable', 'string', 'max:1000'],

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

            'calendar_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'calendar_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'calendar_events' => ['nullable', 'array'],
            'calendar_events.*.date' => $this->dateRules(false),
            'calendar_events.*.title' => $this->textRules(false),

            'teaser' => $this->textRules(false, 1000),
            'is_featured' => ['boolean'],
            'is_influential_weekly' => ['boolean'],
            'show_in_politik' => ['boolean'],
            'status' => $this->statusRules(),
        ];
    }
}
