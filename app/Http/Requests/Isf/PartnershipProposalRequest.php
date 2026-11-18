<?php

namespace App\Http\Requests\Isf;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;

class PartnershipProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'institution_name' => trim((string) ($this->input('institution_name') ?? $this->input('nama_lembaga'))),
            'pic_name' => trim((string) ($this->input('pic_name') ?? $this->input('nama_pic') ?? $this->input('kontak'))),
            'email' => strtolower(trim((string) ($this->input('email') ?? $this->input('email_pic')))),
            'phone' => trim((string) ($this->input('phone') ?? $this->input('telepon') ?? $this->input('whatsapp'))),
            'partnership_type' => strtolower(trim((string) ($this->input('partnership_type') ?? $this->input('jenis_kemitraan') ?? 'banner'))),
            'message' => trim((string) ($this->input('message') ?? $this->input('pesan'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'institution_name' => ['required', 'string', 'min:2', 'max:255'],
            'pic_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'phone' => ['required', 'string', 'min:8', 'max:50'],
            'partnership_type' => ['required', 'string', 'in:banner,advertorial,event,riset,lainnya'],
            'message' => ['required', 'string', 'min:10', 'max:10000'],
            'captcha_key' => ['required', 'string'],
            'captcha_answer' => ['required', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Testing bypass for automated test suites
            if ((app()->environment('testing') || app()->environment('local')) && $this->input('captcha_answer') === 'TEST_CAPTCHA') {
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
                $validator->errors()->add('captcha_answer', 'Kode verifikasi captcha tidak cocok atau sudah kedaluwarsa.');
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
            'institution_name.required' => 'Nama lembaga atau perusahaan wajib diisi.',
            'institution_name.min' => 'Nama lembaga minimal 2 karakter.',
            'pic_name.required' => 'Nama penanggung jawab (PIC) wajib diisi.',
            'pic_name.min' => 'Nama PIC minimal 2 karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'phone.required' => 'Nomor WhatsApp atau telepon wajib diisi.',
            'phone.min' => 'Nomor telepon minimal 8 karakter.',
            'partnership_type.required' => 'Jenis kemitraan wajib dipilih.',
            'partnership_type.in' => 'Pilihan jenis kemitraan tidak valid.',
            'message.required' => 'Pesan atau kebutuhan kemitraan wajib diisi.',
            'message.min' => 'Pesan minimal 10 karakter.',
            'captcha_key.required' => 'Kunci captcha wajib disertakan.',
            'captcha_answer.required' => 'Kode verifikasi captcha wajib diisi.',
        ];
    }
}

