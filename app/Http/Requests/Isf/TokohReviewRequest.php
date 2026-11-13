<?php

namespace App\Http\Requests\Isf;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form "Beri Ulasan" Indeks Tokoh. Login dicek oleh middleware auth di rute.
 */
class TokohReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Pilih rating bintang 1–5.',
            'rating.between' => 'Rating harus antara 1 sampai 5 bintang.',
            'body.required' => 'Tulis ulasan Anda.',
            'body.min' => 'Ulasan minimal 3 karakter.',
            'body.max' => 'Ulasan maksimal 2000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('body'))) {
            $this->merge(['body' => trim($this->input('body'))]);
        }
    }
}
