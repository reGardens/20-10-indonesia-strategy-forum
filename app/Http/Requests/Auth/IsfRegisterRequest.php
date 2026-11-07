<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Cache;

class IsfRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // General Information (Strict Politico match)
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'zip_code' => ['required', 'string', 'max:10'],
            'age_requirement' => ['required', 'accepted'],

            // Login Information (Strict Politico match)
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d)/',
            ],
            'password_confirmation' => ['required', 'string', 'same:password'],

            // Captcha verification
            'captcha_key' => ['required', 'string'],
            'captcha_answer' => ['required', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     */
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

            if (! $expected || $expected !== $answer) {
                $validator->errors()->add('captcha_answer', 'Kode captcha tidak cocok atau sudah kedaluwarsa.');
            } else {
                // Invalidate after single use to prevent replay
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

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Nama depan wajib diisi.',
            'last_name.required' => 'Nama belakang wajib diisi.',
            'zip_code.required' => 'Kode pos wajib diisi.',
            'age_requirement.required' => 'Konfirmasi usia wajib dicentang.',
            'age_requirement.accepted' => 'Anda harus menyatakan berusia 18 tahun atau lebih.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.regex' => 'Kata sandi harus mengandung kombinasi huruf dan angka.',
            'password_confirmation.required' => 'Verifikasi kata sandi wajib diisi.',
            'password_confirmation.same' => 'Verifikasi kata sandi tidak cocok dengan kata sandi.',
            'captcha_key.required' => 'Sesi captcha tidak valid, silakan muat ulang kode.',
            'captcha_answer.required' => 'Kode captcha wajib diisi.',
        ];
    }
}
