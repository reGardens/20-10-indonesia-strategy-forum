<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;

class IsuRequest extends FormRequest
{
    use IsfRules;

    /** Access is gated by the `feature:isu` route middleware. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => $this->slugRules('isf_isus', $this->route('isu')?->id),
            'title' => $this->textRules(),
            'category' => $this->categoryRules(),
            'published_at' => $this->dateRules(),
            'summary' => $this->textRules(true, 2000),
            'author' => $this->textRules(),
            'teaser' => $this->textRules(false, 1000),
            'is_weekly' => ['boolean'],

            // 01 Ringkasan Isu
            'ringkasan' => $this->textRules(true, 5000),

            // 02 Visual Kunci
            'visual_chart_id' => ['nullable', 'integer', 'exists:isf_charts,id'],
            'visual_image_alt' => [...$this->textRules(false), 'required_with:visual_image_url'],
            'visual_image_url' => $this->urlRules(),

            // 03 Fakta Kunci & Linimasa
            'fakta_kunci' => $this->listRules(10),
            ...$this->factBlockRules('fakta_kunci'),
            'linimasa' => $this->listRules(),
            'linimasa.*.date' => $this->dateRules(),
            'linimasa.*.label' => $this->textRules(),

            // 04 Aktor & Posisi
            'aktor' => $this->listRules(),
            'aktor.*.name' => $this->textRules(),
            'aktor.*.role' => $this->textRules(),
            'aktor.*.stance' => $this->textRules(true, 100),
            'aktor.*.to' => $this->linkRules(),

            // 05 Lensa GRC
            'grc_governance' => $this->textRules(true, 5000),
            'grc_risk_level' => $this->textRules(true, 50),
            'grc_risk_description' => $this->textRules(true, 5000),
            'grc_compliance' => $this->listRules(),
            'grc_compliance.*.kewajiban' => $this->textRules(),
            'grc_compliance.*.tenggat' => $this->dateRules(),
            'grc_compliance.*.regulasi' => $this->textRules(),

            // 06 Dampak & Reaksi
            'dampak_sektoral' => $this->listRules(),
            'dampak_sektoral.*.sektor' => $this->textRules(true, 100),
            'dampak_sektoral.*.dampak' => $this->textRules(true, 1000),
            'dampak_reaksi' => $this->listRules(),
            'dampak_reaksi.*.pihak' => $this->textRules(),
            'dampak_reaksi.*.reaksi' => $this->textRules(true, 1000),

            // 07 Apa Selanjutnya
            'selanjutnya_tahapan' => $this->listRules(),
            'selanjutnya_tahapan.*.date' => $this->dateRules(),
            'selanjutnya_tahapan.*.label' => $this->textRules(),
            'selanjutnya_pantau' => $this->listRules(),
            'selanjutnya_pantau.*.label' => $this->textRules(),
            'selanjutnya_pantau.*.to' => $this->linkRules(),

            // Cara isu ini dipilih, FAQ, Artikel Terkait
            'metodologi_text' => $this->textRules(true, 2000),
            'metodologi_signals' => $this->listRules(20),
            'metodologi_signals.*' => $this->textRules(),
            'faq' => $this->listRules(),
            'faq.*.question' => $this->textRules(true, 500),
            'faq.*.answer' => $this->textRules(true, 5000),
            'related' => $this->listRules(),
            'related.*.title' => $this->textRules(),
            'related.*.to' => $this->linkRules(),
            'related.*.publishedAt' => $this->dateRules(),

            'status' => $this->statusRules(),
        ];
    }
}
