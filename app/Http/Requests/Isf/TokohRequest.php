<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;

class TokohRequest extends FormRequest
{
    use IsfRules;

    /** Access is gated by the `feature:tokoh` route middleware. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Every matrix row has one involvement flag per column.
        $columnCount = count((array) $this->input('matrix_columns', []));

        return [
            'slug' => $this->slugRules('isf_tokohs', $this->route('tokoh')?->id),
            'name' => $this->textRules(),
            'position' => $this->textRules(),
            'photo_alt' => [...$this->textRules(false), 'required_with:photo_url'],
            'photo_url' => $this->urlRules(),

            'stats' => ['required', 'array', 'min:1', 'max:8'],
            'stats.*.label' => $this->textRules(true, 100),
            'stats.*.value' => ['required', 'integer', 'min:0'],

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
            'status' => $this->statusRules(),
        ];
    }
}
