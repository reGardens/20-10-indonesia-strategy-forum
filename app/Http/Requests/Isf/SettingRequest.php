<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    use IsfRules;

    /** Access is gated by the `feature:pengaturan_halaman` route middleware. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Beranda: top bar + media sorotan + period
            'beranda' => ['required', 'array'],
            'beranda.topBar' => ['nullable', 'array'],
            'beranda.topBar.badge' => $this->textRules(false, 50),
            'beranda.topBar.announcement' => $this->textRules(false, 500),
            'beranda.topBar.info' => $this->textRules(false, 100),
            'beranda.topBars' => ['nullable', 'array'],
            'beranda.topBars.*.id' => ['nullable'],
            'beranda.topBars.*.badge' => $this->textRules(false, 50),
            'beranda.topBars.*.announcement' => $this->textRules(false, 500),
            'beranda.topBars.*.info' => $this->textRules(false, 100),
            'beranda.topBars.*.status' => ['nullable', 'string'],

            'beranda.mediaSorotan' => ['nullable', 'array'],
            'beranda.mediaSorotan.*.id' => ['nullable'],
            'beranda.mediaSorotan.*.title' => $this->textRules(false, 255),
            'beranda.mediaSorotan.*.imageUrl' => $this->urlRules(false, 500),
            'beranda.mediaSorotan.*.mediaUrl' => $this->urlRules(false, 500),
            'beranda.mediaSorotan.*.category' => $this->textRules(false, 100),
            'beranda.mediaSorotan.*.publishedAt' => ['nullable', 'string'],
            'beranda.mediaSorotan.*.order' => ['nullable', 'integer'],
            'beranda.mediaSorotan.*.status' => ['nullable', 'string'],

            'beranda.pekanIni' => ['nullable', 'array'],
            'beranda.pekanIni.dateStart' => $this->dateRules(false),
            'beranda.pekanIni.dateEnd' => $this->dateRules(false),

            // Anggota: sample member profile on /anggota (optional)
            'anggota' => ['nullable', 'array'],
            'anggota.name' => $this->textRules(false),
            'anggota.role' => $this->textRules(false),
            'anggota.organization' => $this->textRules(false),
            'anggota.plan' => $this->textRules(false),
            'anggota.sektor' => $this->listRules(20),
            'anggota.sektor.*' => $this->textRules(false, 100),
        ];
    }
}
