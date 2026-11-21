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
        $user = $this->user();
        $this->merge([
            'sender_name' => $this->input('sender_name') ?? $this->input('author_name') ?? $this->input('nama_pengirim') ?? $user?->name,
            'sender_email' => $this->input('sender_email') ?? $this->input('email_pengirim') ?? $user?->email,
            'sender_city' => $this->input('sender_city') ?? $this->input('kota_domisili'),
            'author_role' => $this->input('author_role') ?? $this->input('jabatan'),
            'title' => $this->input('title') ?? $this->input('judul_surat') ?? $this->input('judul'),
            'body' => $this->input('body') ?? $this->input('isi_surat') ?? $this->input('konten'),
            'summary' => $this->input('summary') ?? $this->input('ringkasan'),
            'category' => $this->input('category') ?? $this->input('kategori') ?? 'kebijakan',
            'subcategory' => $this->input('subcategory') ?? $this->input('subkategori'),
            'author_avatar' => $this->input('author_avatar') ?? $this->input('foto_penulis'),
            'image_url' => $this->input('image_url') ?? $this->input('gambar_sampul'),
            'image_alt' => $this->input('image_alt'),
            'reading_minutes' => $this->input('reading_minutes') ? (int) $this->input('reading_minutes') : 5,
            'link_url' => $this->input('link_url'),
            'fact_blocks' => $this->input('fact_blocks'),
        ]);
    }

    public function rules(): array
    {
        return [
            'sender_name' => ['required', 'string', 'max:150'],
            'sender_email' => ['required', 'string', 'email', 'max:150'],
            'sender_city' => ['nullable', 'string', 'max:100'],
            'author_role' => ['nullable', 'string', 'max:150'],
            'title' => ['required', 'string', 'min:5', 'max:300'],
            'category' => ['nullable', 'string', 'max:50'],
            'subcategory' => ['nullable', 'string', 'max:100'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'body' => ['required', 'string', 'min:20', 'max:20000'],
            'author_avatar' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'reading_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'link_url' => ['nullable', 'string', 'max:500'],
            'fact_blocks' => ['nullable'],
            'captcha_key' => ['nullable', 'string'],
            'captcha_answer' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Member terdaftar / terautentikasi tidak memerlukan captcha
            if ($this->user()) {
                return;
            }

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
