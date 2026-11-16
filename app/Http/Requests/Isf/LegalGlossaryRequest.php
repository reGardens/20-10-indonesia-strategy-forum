<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LegalGlossaryRequest extends FormRequest
{
    use IsfRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->slug) && !empty($this->istilah)) {
            $this->merge(['slug' => Str::slug($this->istilah)]);
        }
        if (empty($this->abjad) && !empty($this->istilah)) {
            $this->merge(['abjad' => strtoupper(mb_substr(trim($this->istilah), 0, 1))]);
        }
    }

    public function rules(): array
    {
        $param = $this->route('legalGlossary') ?? $this->route('legal_glossary') ?? $this->route('glosarium_hukum');
        $id = is_object($param) ? $param->id : (is_numeric($param) ? (int) $param : null);

        return [
            'istilah' => $this->textRules(),
            'slug' => $this->slugRules('isf_legal_glossaries', $id),
            'abjad' => ['nullable', 'string', 'size:1'],
            'definisi' => $this->textRules(true, 5000),
            'dasar_hukum' => ['nullable', 'string', 'max:1000'],
            'istilah_terkait' => ['nullable', 'array', 'max:15'],
            'istilah_terkait.*' => ['string', 'max:150'],
            'urutan' => $this->orderRules(),
            'status' => $this->statusRules(),
        ];
    }
}
