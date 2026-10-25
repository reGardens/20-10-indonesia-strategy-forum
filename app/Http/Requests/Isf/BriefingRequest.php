<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;

class BriefingRequest extends FormRequest
{
    use IsfRules;

    /** Access is gated by the `feature:email_briefing` route middleware. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal' => $this->dateRules(),
            'penerima' => $this->textRules(),
            'organisasi' => $this->textRules(),

            // Ringkasan Data Anggota
            'ringkasan_judul' => $this->textRules(),
            'ringkasan_catatan' => $this->textRules(true, 2000),
            'ringkasan_data' => $this->listRules(10),
            'ringkasan_data.*.label' => $this->textRules(true, 100),
            'ringkasan_data.*.nilai' => ['required', 'integer', 'min:0'],

            // Poin-Poin Utama (exactly 3)
            'pembuka' => $this->textRules(true, 2000),
            'poin' => ['required', 'array', 'size:3'],
            'poin.*.judul' => $this->textRules(),
            'poin.*.deskripsi' => $this->textRules(true, 2000),
            'poin.*.url' => $this->linkRules(true),
            'penutup' => $this->textRules(true, 2000),
            'cta_url' => $this->linkRules(true),

            // Tautan Langsung
            'tautan' => $this->listRules(10),
            'tautan.*.label' => $this->textRules(),
            'tautan.*.url' => $this->linkRules(true),

            'status' => $this->statusRules(),
        ];
    }
}
