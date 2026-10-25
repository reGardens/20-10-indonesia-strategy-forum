<?php

namespace App\Http\Requests\Isf\Concerns;

use App\Support\IsfOptions;
use Illuminate\Validation\Rule;

/**
 * Shared validation rules for the ISF CMS content requests.
 */
trait IsfRules
{
    /** Publish status: draf | terbit. */
    protected function statusRules(): array
    {
        return ['required', 'string', Rule::in(IsfOptions::STATUSES)];
    }

    /** Content category (politik | ekonomi | kebijakan | hukum | ruu). */
    protected function categoryRules(): array
    {
        return ['required', 'string', Rule::in(IsfOptions::CATEGORIES)];
    }

    /** Date in YYYY-MM-DD. */
    protected function dateRules(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'date_format:Y-m-d'];
    }

    /** URL-safe slug, unique in the given table (ignoring the edited row). */
    protected function slugRules(string $table, ?int $ignoreId): array
    {
        return [
            'required',
            'string',
            'max:255',
            'alpha_dash:ascii',
            Rule::unique($table, 'slug')->ignore($ignoreId),
        ];
    }

    /**
     * In-site path ("/isu/...") or absolute http(s) URL used as a link target.
     * Protocol-relative URLs ("//host") are rejected.
     */
    protected function linkRules(bool $required = false): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            'max:255',
            'starts_with:/,https://,http://',
            'not_regex:/^\/\//',
        ];
    }

    /** Absolute http(s) URL (sources, images). */
    protected function urlRules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'max:255', 'url:http,https'];
    }

    /** Plain text value. */
    protected function textRules(bool $required = true, int $max = 255): array
    {
        return [$required ? 'required' : 'nullable', 'string', "max:{$max}"];
    }

    /** List of rows stored in a JSON column (may be empty). */
    protected function listRules(int $max = 50): array
    {
        return ['present', 'array', "max:{$max}"];
    }

    /**
     * Rules for a list of "Fakta Kunci" blocks {claim, sourceUrl, sourceTitle, sourceDate}.
     */
    protected function factBlockRules(string $field): array
    {
        return [
            "{$field}.*.claim" => $this->textRules(true, 1000),
            "{$field}.*.sourceUrl" => $this->urlRules(true),
            "{$field}.*.sourceTitle" => $this->textRules(),
            "{$field}.*.sourceDate" => $this->dateRules(),
        ];
    }
}
