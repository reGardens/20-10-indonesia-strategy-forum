<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class CaseTimelineRequest extends FormRequest
{
    use IsfRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->slug) && !empty($this->judul_kasus)) {
            $this->merge(['slug' => Str::slug($this->judul_kasus)]);
        }
    }

    public function rules(): array
    {
        $param = $this->route('caseTimeline') ?? $this->route('case_timeline') ?? $this->route('timeline_kasus');
        $id = is_object($param) ? $param->id : (is_numeric($param) ? (int) $param : null);

        return [
            'judul_kasus' => $this->textRules(),
            'slug' => $this->slugRules('isf_case_timelines', $id),
            'kategori_kasus' => ['nullable', 'string', 'max:100'],
            'deskripsi' => $this->textRules(false, 3000),
            'status_kasus' => ['required', 'string', 'max:100'],
            'gambar' => $this->urlRules(),
            'urutan' => $this->orderRules(),
            'status' => $this->statusRules(),
            'events' => ['nullable', 'array', 'max:50'],
            'events.*.id' => ['nullable', 'integer'],
            'events.*.tanggal_peristiwa' => ['required', 'date'],
            'events.*.tahap' => ['required', 'string', 'max:100'],
            'events.*.judul' => ['required', 'string', 'max:255'],
            'events.*.deskripsi' => ['nullable', 'string', 'max:3000'],
            'events.*.sumber_tautan' => ['nullable', 'string', 'max:500'],
            'events.*.urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'events.*.status' => ['nullable', 'string', 'in:terbit,draft'],
        ];
    }
}
