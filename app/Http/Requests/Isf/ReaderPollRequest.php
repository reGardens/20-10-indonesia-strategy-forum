<?php

namespace App\Http\Requests\Isf;

use App\Http\Requests\Isf\Concerns\IsfRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ReaderPollRequest extends FormRequest
{
    use IsfRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (empty($this->slug) && !empty($this->question)) {
            $this->merge(['slug' => Str::slug($this->question)]);
        }
    }

    public function rules(): array
    {
        $param = $this->route('readerPoll') ?? $this->route('reader_poll');
        $id = is_object($param) ? $param->id : (is_numeric($param) ? (int) $param : null);

        return [
            'question' => $this->textRules(true, 500),
            'slug' => $this->slugRules('isf_reader_polls', $id),
            'description' => ['nullable', 'string', 'max:2000'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*.id' => ['required', 'string', 'max:50'],
            'options.*.label' => ['required', 'string', 'max:255'],
            'options.*.votes' => ['nullable', 'integer', 'min:0'],
            'start_date' => $this->dateRules(false),
            'end_date' => $this->dateRules(false),
            'status' => ['required', 'string', 'in:aktif,selesai,draft'],
            'is_featured' => ['nullable', 'boolean'],
            'order' => $this->orderRules(),
        ];
    }
}

