<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;

class ArticleRequest extends FormRequest
{
    use IsfRules;

    /** Access is gated by the `feature:artikel` route middleware. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => $this->slugRules('isf_articles', $this->route('article')?->id),
            'title' => $this->textRules(),
            'category' => $this->categoryRules(),
            'published_at' => $this->dateRules(),
            'summary' => $this->textRules(false, 2000),
            'body' => ['nullable', 'string', 'max:100000'],
            'author_name' => $this->textRules(false),
            'author_role' => $this->textRules(false),
            'reading_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'image_alt' => [...$this->textRules(false), 'required_with:image_url'],
            'image_url' => $this->urlRules(),
            'link_url' => $this->linkRules(),
            'fact_blocks' => ['nullable', 'array', 'max:10'],
            ...$this->factBlockRules('fact_blocks'),
            'is_hero' => ['boolean'],
            'show_in_terkini' => ['boolean'],
            'is_popular' => ['boolean'],
            'popular_rank' => ['nullable', 'integer', 'min:1', 'max:99'],
            'status' => $this->statusRules(),
        ];
    }
}
