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

    /**
     * Gambar diisi tetapi Alt Text kosong → pakai title sebagai Alt Text
     * (sebelumnya ditolak validasi sehingga gambar tidak tersimpan).
     */
    protected function prepareForValidation(): void
    {
        if (trim((string) $this->input('image_url')) !== '' && trim((string) $this->input('image_alt')) === '') {
            $this->merge(['image_alt' => mb_substr((string) $this->input('title'), 0, 255)]);
        }

        $user = $this->user();
        $isOnlyMember = $user && $user->hasRole('member') && ! $user->hasAnyRole(['admin', 'editor', 'super-admin']);
        if ($isOnlyMember) {
            $this->merge([
                'is_hero' => false,
                'show_in_terkini' => false,
                'is_popular' => false,
                'status' => 'draf',
                'published_at' => $this->input('published_at') ?: now()->format('Y-m-d'),
                'author_name' => $user->name,
                'author_role' => $user->author_role ?? $this->input('author_role') ?? 'Member ISF',
                'author_avatar' => $user->author_avatar ?? $user->avatar ?? null,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'slug' => $this->slugRules('isf_articles', $this->route('article')?->id),
            'title' => $this->textRules(),
            'category' => $this->categoryRules(),
            'subcategory' => ['nullable', 'string', 'max:50'],
            'published_at' => $this->dateRules(),
            'summary' => $this->textRules(false, 2000),
            'body' => ['nullable', 'string', 'max:100000'],
            'author_name' => $this->textRules(false),
            'author_role' => $this->textRules(false),
            'author_avatar' => ['nullable', 'string', 'max:1000'],
            'reading_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'image_alt' => [...$this->textRules(false), 'required_with:image_url'],
            'image_url' => $this->urlRules(),
            'link_url' => $this->linkRules(),
            'fact_blocks' => ['nullable', 'array', 'max:10'],
            ...$this->factBlockRules('fact_blocks'),
            'is_hero' => ['boolean'],
            'is_expert_opinion' => ['nullable', 'boolean'],
            'expert_name' => ['nullable', 'string', 'max:255'],
            'expert_title' => ['nullable', 'string', 'max:255'],
            'expert_avatar' => ['nullable', 'string', 'max:1000'],
            'show_in_terkini' => ['boolean'],
            'show_in_running_text' => ['nullable', 'boolean'],
            'running_text_order' => ['nullable', 'integer', 'min:1', 'max:5'],
            'show_in_top_rank' => ['nullable', 'boolean'],
            'top_rank_order' => ['nullable', 'integer', 'min:1', 'max:3'],
            'is_popular' => ['boolean'],
            'popular_rank' => ['nullable', 'integer', 'min:1', 'max:99'],
            'status' => $this->statusRules(),
        ];
    }
}
