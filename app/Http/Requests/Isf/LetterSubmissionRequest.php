<?php

namespace App\Http\Requests\Isf;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;

class LetterSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sender_name' => $this->input('sender_name') ?? $this->input('nama_pengirim'),
            'sender_email' => $this->input('sender_email') ?? $this->input('email_pengirim'),
            'sender_city' => $this->input('sender_city') ?? $this->input('kota_domisili'),
            'title' => $this->input('title') ?? $this->input('judul_surat'),
            'body' => $this->input('body') ?? $this->input('isi_surat'),
        ]);
    }

    public function rules(): array
    {
        return [
            'sender_name' => ['required', 'string', 'max:150'],
            'sender_email' => ['required', 'string', 'email', 'max:150'],
            'sender_city' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'min:5', 'max:300'],
            'body' => ['required', 'string', 'min:20', 'max:10000'],
            'captcha_key' => ['required', 'string'],
            'captcha_answer' => ['required', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Allow testing bypass with 'TEST_CAPTCHA'
            if (app()->environment('testing') && $this->input('captcha_answer') === 'TEST_CAPTCHA') {
                return;
            }

            $key = $this->input('captcha_key');
            $answer = strtolower(trim((string) $this->input('captcha_answer')));

            if (! $key) {
                return;
            }

            $expected = null;
            try {
                $expected = Cache::get('captcha:' . $key);
            } catch (\Throwable $e) {}

            if ($expected === null) {
                try {
                    $expected = Cache::store('file')->get('captcha:' . $key);
                } catch (\Throwable $e) {}
            }

            if ($expected === null) {
                $expected = session('captcha:' . $key);
            }

            if (! $expected || strtolower((string) $expected) !== $answer) {
                $validator->errors()->add('captcha_answer', 'Kode captcha tidak cocok atau sudah kedaluwarsa.');
            } else {
                try {
                    Cache::forget('captcha:' . $key);
                } catch (\Throwable $e) {}
                try {
                    Cache::store('file')->forget('captcha:' . $key);
                } catch (\Throwable $e) {}
                session()->forget('captcha:' . $key);
            }
        });
    }

    public function messages(): array
    {
        return [
            'sender_name.required' => 'Nama lengkap wajib diisi.',
            'sender_email.required' => 'Alamat email wajib diisi.',
            'sender_email.email' => 'Alamat email tidak valid.',
            'title.required' => 'Judul surat pembaca wajib diisi.',
            'title.min' => 'Judul surat pembaca minimal 5 karakter.',
            'body.required' => 'Isi surat pembaca wajib diisi.',
            'body.min' => 'Isi surat pembaca minimal 20 karakter.',
            'captcha_key.required' => 'Kunci captcha wajib disertakan.',
            'captcha_answer.required' => 'Kode verifikasi captcha wajib diisi.',
        ];
    }
}
