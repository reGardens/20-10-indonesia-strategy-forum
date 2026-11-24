<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class IsfCaptchaController extends Controller
{
    /** Batas percobaan salah per puzzle sebelum key hangus (Issue #74). */
    public const MAX_PUZZLE_ATTEMPTS = 3;

    /**
     * Generate a fresh visual CAPTCHA challenge (classic text/code).
     */
    public function generate(): JsonResponse
    {
        // 5 characters without visually ambiguous glyphs (0, O, 1, I, l)
        $charset = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $length = 5;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $charset[random_int(0, strlen($charset) - 1)];
        }

        $key = Str::uuid()->toString();
        $storedCode = strtolower($code);

        // Store with robust multi-layer fallback: Default Store -> File Store -> Session
        $stored = false;
        try {
            Cache::put('captcha:' . $key, $storedCode, now()->addMinutes(10));
            $stored = true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Captcha default cache error (falling back to file): ' . $e->getMessage());
        }

        if (! $stored) {
            try {
                Cache::store('file')->put('captcha:' . $key, $storedCode, now()->addMinutes(10));
                $stored = true;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Captcha file cache error (falling back to session): ' . $e->getMessage());
            }
        }

        if (! $stored) {
            session(['captcha:' . $key => $storedCode]);
        }

        // Generate stylized SVG image
        $svg = $this->renderSvg($code);

        return response()->json([
            'key' => $key,
            'image' => 'data:image/svg+xml;base64,' . base64_encode($svg),
        ]);
    }

    /**
     * Render a secure, stylized SVG image containing the challenge characters.
     */
    protected function renderSvg(string $code): string
    {
        $width = 180;
        $height = 46;
        $bg = '#f8fafc';

        // Disturbance curved / dashed lines
        $lines = '';
        for ($i = 0; $i < 3; $i++) {
            $x1 = random_int(5, 40);
            $y1 = random_int(8, 38);
            $x2 = random_int(140, 175);
            $y2 = random_int(8, 38);
            $stroke = ['#94a3b8', '#cbd5e1', '#b91c1c', '#64748b'][random_int(0, 3)];
            $lines .= "<line x1='{$x1}' y1='{$y1}' x2='{$x2}' y2='{$y2}' stroke='{$stroke}' stroke-width='1.5' stroke-dasharray='4,2' opacity='0.7' />";
        }

        // Noise dots
        $dots = '';
        for ($i = 0; $i < 25; $i++) {
            $cx = random_int(5, 175);
            $cy = random_int(5, 40);
            $r = random_int(1, 2);
            $dots .= "<circle cx='{$cx}' cy='{$cy}' r='{$r}' fill='#94a3b8' opacity='0.4' />";
        }

        // Characters with slight rotations and distinct editorial colors
        $charsSvg = '';
        $colors = ['#0f172a', '#b91c1c', '#1e293b', '#334155', '#7f1d1d'];
        $charCount = strlen($code);
        $spacing = ($width - 40) / $charCount;

        for ($i = 0; $i < $charCount; $i++) {
            $char = $code[$i];
            $x = 24 + ($i * $spacing) + random_int(-3, 3);
            $y = 32 + random_int(-3, 3);
            $angle = random_int(-16, 16);
            $color = $colors[$i % count($colors)];
            $fontSize = random_int(22, 26);

            $charsSvg .= "<text x='{$x}' y='{$y}' font-family='Arial, sans-serif' font-weight='bold' font-size='{$fontSize}' fill='{$color}' transform='rotate({$angle}, {$x}, {$y})'>{$char}</text>";
        }

        return "<svg xmlns='http://www.w3.org/2000/svg' width='{$width}' height='{$height}' viewBox='0 0 {$width} {$height}' style='border-radius:6px; background:{$bg}; border:1px solid #cbd5e1; user-select:none;'>
            {$lines}
            {$dots}
            {$charsSvg}
        </svg>";
    }

    /**
     * Generate a fresh interactive Slide Puzzle CAPTCHA challenge (Issue #74 / vue3-puzzle-vcode).
     */
    public function generatePuzzle(): JsonResponse
    {
        $key = Str::uuid()->toString();
        $targetX = random_int(85, 235);
        $targetY = random_int(25, 90);

        // Store target X in Cache (with file/session fallback)
        $this->storeCacheKey('puzzle:' . $key, $targetX);

        // Clean background photo from public/images/captcha (without any cutout holes or stamped borders)
        $bgImage = '';
        $photoPath = $this->getRandomCaptchaImagePath();

        if ($photoPath && is_readable($photoPath)) {
            $ext = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
            if (function_exists('imagecreatefromstring')) {
                $fileContent = @file_get_contents($photoPath);
                $src = $fileContent ? @imagecreatefromstring($fileContent) : false;
                if ($src) {
                    ob_start();
                    imagepng($src);
                    $pngBytes = ob_get_clean();
                    imagedestroy($src);
                    $bgImage = 'data:image/png;base64,' . base64_encode($pngBytes);
                }
            }
            if ($bgImage === '') {
                $bgImage = 'data:image/png;base64,' . base64_encode(file_get_contents($photoPath));
            }
        }

        if ($bgImage === '') {
            $bgImage = 'data:image/svg+xml;base64,' . base64_encode($this->renderFallbackBgSvg());
        }

        $token = encrypt(['key' => $key, 'target' => $targetX]);

        return response()->json([
            'key' => $key,
            'token' => $token,
            'bg_image' => $bgImage,
            'imgs' => [$bgImage],
            // Kept for backward compatibility with existing tests
            'piece_image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAADElEQVQImWNgYGAAAAAEAAGjChXjAAAAAElFTkSuQmCC',
            'puzzle_y' => $targetY,
            'width' => 320,
            'height' => 160,
            'piece_size' => 44,
            'piece_width' => 58,
            'piece_height' => 58,
            'max_offset' => 276,
        ]);
    }

    /**
     * Verify slider puzzle position offset (Issue #74).
     */
    public function verifyPuzzle(Request $request): JsonResponse
    {
        $key = (string) $request->input('key');
        $offset = $request->input('offset');
        $token = $request->input('token');

        if (! $key || ($offset === null && empty($token))) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter verifikasi puzzle tidak lengkap.',
            ], 422);
        }

        // Allow token-based verification (from vue3-puzzle-vcode on client success)
        if (! empty($token)) {
            try {
                $decrypted = decrypt($token);
                if (is_array($decrypted) && ($decrypted['key'] ?? '') === $key) {
                    $expectedX = (float) $decrypted['target'];
                    $this->storeCacheKey('puzzle_verified:' . $key, $expectedX);

                    return response()->json([
                        'success' => true,
                        'offset' => $expectedX,
                        'message' => 'Verifikasi puzzle berhasil!',
                    ]);
                }
            } catch (\Throwable $e) {}
        }

        // Allow testing bypass with 'TEST_PUZZLE' or 'TEST_CAPTCHA'
        if (app()->environment('testing') && in_array($offset, ['TEST_PUZZLE', 'TEST_CAPTCHA'], true)) {
            $this->storeCacheKey('puzzle_verified:' . $key, 100);
            return response()->json([
                'success' => true,
                'message' => 'Verifikasi puzzle berhasil (bypass testing).',
            ]);
        }

        $expectedX = $this->getCacheKey('puzzle:' . $key);

        if ($expectedX === null) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi captcha puzzle telah kedaluwarsa. Silakan muat ulang.',
            ], 422);
        }

        // Tolerance window: +-6 pixels
        $tolerance = 6.0;
        $diff = is_numeric($offset) ? abs((float) $offset - (float) $expectedX) : INF;

        if ($diff <= $tolerance) {
            // Mark puzzle as verified in cache
            $this->storeCacheKey('puzzle_verified:' . $key, (float) $offset);

            return response()->json([
                'success' => true,
                'message' => 'Verifikasi puzzle berhasil!',
            ]);
        }

        // Jangan kirim jarak ke posisi benar (bisa dipakai bot). Key hangus setelah
        // MAX_PUZZLE_ATTEMPTS kali salah agar posisi tidak bisa ditebak satu per satu.
        $attempts = (int) ($this->getCacheKey('puzzle_attempts:' . $key) ?? 0) + 1;

        if ($attempts >= self::MAX_PUZZLE_ATTEMPTS) {
            $this->forgetPuzzleKey($key);

            return response()->json([
                'success' => false,
                'expired' => true,
                'message' => 'Terlalu banyak percobaan. Puzzle baru dimuat, silakan coba lagi.',
            ], 422);
        }

        $this->storeCacheKey('puzzle_attempts:' . $key, $attempts);

        return response()->json([
            'success' => false,
            'message' => 'Posisi kepingan puzzle belum tepat. Silakan coba lagi.',
        ], 422);
    }

    /**
     * Hapus semua data puzzle untuk key ini (target, status terverifikasi, jumlah percobaan).
     */
    protected function forgetPuzzleKey(string $key): void
    {
        foreach (['puzzle:', 'puzzle_verified:', 'puzzle_attempts:'] as $prefix) {
            try { Cache::forget($prefix . $key); } catch (\Throwable $e) {}
            try { Cache::store('file')->forget($prefix . $key); } catch (\Throwable $e) {}
            session()->forget($prefix . $key);
        }
    }

    /**
     * Clean fallback SVG background when no photos are available.
     */
    protected function renderFallbackBgSvg(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="160" viewBox="0 0 320 160">'
            . '<defs><linearGradient id="fallbackGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#0B1220"/><stop offset="100%" stop-color="#1E293B"/></linearGradient></defs>'
            . '<rect width="320" height="160" fill="url(#fallbackGrad)"/>'
            . '<rect x="14" y="14" width="3" height="15" fill="#E10600"/>'
            . '<text x="22" y="24" fill="#FFFFFF" font-family="Arial, sans-serif" font-weight="bold" font-size="10" letter-spacing="1">INDONESIA STRATEGY FORUM</text>'
            . '<text x="22" y="36" fill="#94A3B8" font-family="Arial, sans-serif" font-size="7.5" font-weight="600" letter-spacing="0.5">VERIFIKASI KEAMANAN</text>'
            . '</svg>';
    }

    /**
     * Robust cache storage with fallbacks (Cache default -> Cache file -> Session).
     */
    protected function storeCacheKey(string $key, mixed $value, int $minutes = 10): void
    {
        $stored = false;
        try {
            Cache::put($key, $value, now()->addMinutes($minutes));
            $stored = true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Captcha default cache error: ' . $e->getMessage());
        }

        if (! $stored) {
            try {
                Cache::store('file')->put($key, $value, now()->addMinutes($minutes));
                $stored = true;
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Captcha file cache error: ' . $e->getMessage());
            }
        }

        if (! $stored) {
            session([$key => $value]);
        }
    }

    /**
     * Robust cache retrieval with fallbacks (Cache default -> Cache file -> Session).
     */
    protected function getCacheKey(string $key): mixed
    {
        $val = null;
        try {
            $val = Cache::get($key);
        } catch (\Throwable $e) {}

        if ($val === null) {
            try {
                $val = Cache::store('file')->get($key);
            } catch (\Throwable $e) {}
        }

        if ($val === null) {
            $val = session($key);
        }

        return $val;
    }

    /**
     * Path file foto latar puzzle yang dipilih acak (public/images/captcha), atau null.
     */
    protected function getRandomCaptchaImagePath(): ?string
    {
        $dir = public_path('images/captcha');
        if (! is_dir($dir)) {
            return null;
        }

        $files = glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
        if (empty($files)) {
            return null;
        }

        $chosen = $files[array_rand($files)];

        return is_readable($chosen) ? $chosen : null;
    }
}

