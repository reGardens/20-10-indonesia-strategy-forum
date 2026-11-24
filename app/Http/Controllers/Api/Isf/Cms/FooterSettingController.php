<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Controllers\Controller;
use App\Models\Isf\Setting;
use App\Services\Isf\IsfCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CMS API — Pengaturan Footer Dinamis (Issue #99).
 * Mengelola informasi kontak, deskripsi About Us singkat, syarat & ketentuan,
 * kebijakan privasi, serta tautan navigasi legalitas footer ISF.
 */
class FooterSettingController extends Controller
{
    /**
     * Struktur data default footer jika belum pernah disimpan di isf_settings.
     */
    public static function defaultFooter(): array
    {
        return [
            'about_us' => 'Indonesia Strategy Forum (ISF) adalah kanal referensi independen untuk para pembuat kebijakan, eksekutif puncak korporasi, akademisi, dan profesional hukum yang membentuk arah pembangunan Indonesia.',
            'contact_email' => 'kontak@indonesiastrategyforum.com',
            'contact_phone' => '+62 21 555-1234',
            'contact_address' => 'Jakarta, Indonesia',
            'terms_conditions' => '<h2>1. Ketentuan Umum</h2><p>Selamat datang di <strong>Indonesia Strategy Forum (ISF)</strong>. Dengan mengakses dan menggunakan portal ini, Anda menyatakan setuju untuk terikat pada Syarat dan Ketentuan penggunaan yang berlaku.</p><h2>2. Hak Kekayaan Intelektual</h2><p>Seluruh materi publikasi, analisis kebijakan, infografik, basis data peraturan, dan dokumentasi di situs ini dilindungi hak cipta. Pengutipan materi untuk kepentingan akademis dan non-komersial wajib menyertakan atribusi sumber resmi ISF.</p><h2>3. Penafian Tanggung Jawab</h2><p>Informasi dan analisis yang disajikan bertujuan sebagai referensi strategis dan edukasi publik, serta bukan merupakan nasihat hukum atau investasi resmi.</p>',
            'privacy_policy' => '<h2>1. Kebijakan Privasi ISF</h2><p><strong>Indonesia Strategy Forum (ISF)</strong> berkomitmen penuh menjaga kerahasiaan dan keamanan data pribadi seluruh pembaca, anggota portal, dan mitra institusi.</p><h2>2. Data yang Dikumpulkan</h2><p>Kami hanya mengumpulkan data yang diberikan secara sadar oleh pengguna, seperti nama, kontak surat elektronik (email), instansi, dan sektor kerja saat melakukan registrasi akun anggota atau berlangganan buletin.</p><h2>3. Penggunaan & Keamanan Informasi</h2><p>Data yang dihimpun digunakan semata-mata untuk kebutuhan autentikasi layanan, pengiriman briefing kebijakan berkala, dan peningkatan kualitas konten analitik. Kami tidak memperjualbelikan data pribadi kepada pihak ketiga.</p>',
            'nav_links' => [
                ['label' => 'Tentang ISF', 'url' => '/tentang-isf', 'is_external' => false],
                ['label' => 'Dewan Pakar', 'url' => '/dewan-pakar', 'is_external' => false],
                ['label' => 'Event', 'url' => '/event', 'is_external' => false],
                ['label' => 'Iklan & Kemitraan', 'url' => '/media-kit', 'is_external' => false],
                ['label' => 'Kontak', 'url' => '/kontak', 'is_external' => false],
            ],
            'legal_links' => [
                ['label' => 'Syarat & Ketentuan', 'url' => '/syarat-ketentuan', 'is_external' => false],
                ['label' => 'Privacy Policy', 'url' => '/privacy-policy', 'is_external' => false],
                ['label' => 'Pedoman Media Siber', 'url' => '/pedoman-media-siber', 'is_external' => false],
                ['label' => 'Cookie Policy', 'url' => '/cookie-policy', 'is_external' => false],
            ],
            'social_links' => [
                ['platform' => 'twitter', 'label' => 'X (Twitter)', 'url' => 'https://twitter.com/isf_forum'],
                ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://linkedin.com/company/indonesia-strategy-forum'],
                ['platform' => 'youtube', 'label' => 'YouTube', 'url' => 'https://youtube.com/@indonesiastrategyforum'],
                ['platform' => 'instagram', 'label' => 'Instagram', 'url' => 'https://instagram.com/indonesiastrategyforum'],
            ],
            'copyright' => '© ' . date('Y') . ' Indonesia Strategy Forum. Hak Cipta Dilindungi Undang-Undang.',
            'disclaimer' => 'Seluruh analisis, opini, dan data yang disajikan di Indonesia Strategy Forum (ISF) bertujuan untuk referensi strategis dan edukasi publik, bukan merupakan nasihat investasi atau hukum resmi.',
        ];
    }

    /**
     * Ambil data pengaturan footer (CMS).
     */
    public function show(): JsonResponse
    {
        $footer = Setting::get('footer', self::defaultFooter());

        // Pastikan fallback default terisi jika ada key yang kosong
        $merged = array_merge(self::defaultFooter(), $footer);

        return response()->json([
            'data' => $merged,
        ]);
    }

    /**
     * Simpan / perbarui pengaturan footer (CMS).
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'about_us' => 'nullable|string|max:2000',
            'contact_email' => 'nullable|string|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'contact_address' => 'nullable|string|max:255',
            'terms_conditions' => 'nullable|string',
            'privacy_policy' => 'nullable|string',
            'nav_links' => 'nullable|array',
            'nav_links.*.label' => 'required|string|max:100',
            'nav_links.*.url' => 'required|string|max:255',
            'nav_links.*.is_external' => 'nullable|boolean',
            'legal_links' => 'nullable|array',
            'legal_links.*.label' => 'required|string|max:100',
            'legal_links.*.url' => 'required|string|max:255',
            'legal_links.*.is_external' => 'nullable|boolean',
            'social_links' => 'nullable|array',
            'social_links.*.platform' => 'required|string|max:50',
            'social_links.*.label' => 'required|string|max:100',
            'social_links.*.url' => 'required|string|max:255',
            'copyright' => 'nullable|string|max:255',
            'disclaimer' => 'nullable|string|max:1000',
        ]);

        $existing = Setting::get('footer', self::defaultFooter());
        $default = self::defaultFooter();

        $payload = [
            'about_us' => array_key_exists('about_us', $validated) ? ($validated['about_us'] ?? '') : ($existing['about_us'] ?? $default['about_us']),
            'contact_email' => array_key_exists('contact_email', $validated) ? ($validated['contact_email'] ?? '') : ($existing['contact_email'] ?? $default['contact_email']),
            'contact_phone' => array_key_exists('contact_phone', $validated) ? ($validated['contact_phone'] ?? '') : ($existing['contact_phone'] ?? $default['contact_phone']),
            'contact_address' => array_key_exists('contact_address', $validated) ? ($validated['contact_address'] ?? '') : ($existing['contact_address'] ?? $default['contact_address']),
            'terms_conditions' => array_key_exists('terms_conditions', $validated) ? ($validated['terms_conditions'] ?? '') : ($existing['terms_conditions'] ?? $default['terms_conditions']),
            'privacy_policy' => array_key_exists('privacy_policy', $validated) ? ($validated['privacy_policy'] ?? '') : ($existing['privacy_policy'] ?? $default['privacy_policy']),
            'nav_links' => $validated['nav_links'] ?? ($existing['nav_links'] ?? $default['nav_links']),
            'legal_links' => $validated['legal_links'] ?? ($existing['legal_links'] ?? $default['legal_links']),
            'social_links' => $validated['social_links'] ?? ($existing['social_links'] ?? $default['social_links']),
            'copyright' => $validated['copyright'] ?? ($existing['copyright'] ?? $default['copyright']),
            'disclaimer' => $validated['disclaimer'] ?? ($existing['disclaimer'] ?? $default['disclaimer']),
        ];

        Setting::put('footer', $payload);
        IsfCacheService::clearAll();

        return response()->json([
            'message' => 'Pengaturan footer berhasil disimpan.',
            'data' => $payload,
        ]);
    }
}
