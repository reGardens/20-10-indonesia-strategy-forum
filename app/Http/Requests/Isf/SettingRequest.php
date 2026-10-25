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
            // Beranda: top bar + "Pekan Ini" period
            'beranda' => ['required', 'array'],
            'beranda.topBar' => ['required', 'array'],
            'beranda.topBar.badge' => $this->textRules(true, 50),
            'beranda.topBar.announcement' => $this->textRules(true, 500),
            'beranda.topBar.info' => $this->textRules(true, 100),
            'beranda.pekanIni' => ['required', 'array'],
            'beranda.pekanIni.dateStart' => $this->dateRules(),
            'beranda.pekanIni.dateEnd' => [...$this->dateRules(), 'after_or_equal:beranda.pekanIni.dateStart'],

            // Anggota: sample member profile on /anggota
            'anggota' => ['required', 'array'],
            'anggota.name' => $this->textRules(),
            'anggota.role' => $this->textRules(),
            'anggota.organization' => $this->textRules(),
            'anggota.plan' => $this->textRules(),
            'anggota.sektor' => $this->listRules(20),
            'anggota.sektor.*' => $this->textRules(true, 100),
        ];
    }
}
