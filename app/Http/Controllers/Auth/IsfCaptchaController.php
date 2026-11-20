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
     * Generate a fresh interactive Slide Puzzle CAPTCHA challenge (Issue #74).
     */
    public function generatePuzzle(): JsonResponse
    {
        $key = Str::uuid()->toString();
        $width = 320;
        $height = 160;
        $pieceSize = 44;

        // Target slot location: safe boundaries so hole is not cut off or too close to edge
        $targetX = random_int(85, 235);
        $targetY = random_int(25, 90);

        // Store target X in Cache (with file/session fallback)
        $this->storeCacheKey('puzzle:' . $key, $targetX);

        // Latar foto (PNG via GD) bila ada foto & ekstensi GD; selain itu tema vektor SVG.
        $bgImage = '';
        $pieceImage = '';
        $photoPath = $this->getRandomCaptchaImagePath();

        if ($photoPath && function_exists('imagecreatetruecolor')) {
            try {
                // Renderer GD sudah mengembalikan data URI "data:image/png;base64,...".
                $bgImage = $this->renderPuzzleBgPhotoSvg($width, $height, $targetX, $targetY, $photoPath);
                $pieceImage = $this->renderPuzzlePiecePhotoSvg($targetX, $targetY, $photoPath);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Puzzle captcha photo render error: ' . $e->getMessage());
                $bgImage = $pieceImage = '';
            }
        }

        if ($bgImage === '' || $pieceImage === '') {
            $themeIndex = random_int(0, 3);
            $bgImage = 'data:image/svg+xml;base64,' . base64_encode($this->renderPuzzleBgSvg($width, $height, $targetX, $targetY, $themeIndex));
            $pieceImage = 'data:image/svg+xml;base64,' . base64_encode($this->renderPuzzlePieceSvg($targetX, $targetY, $themeIndex));
        }

        return response()->json([
            'key' => $key,
            'bg_image' => $bgImage,
            'piece_image' => $pieceImage,
            'puzzle_y' => $targetY,
            'width' => $width,
            'height' => $height,
            'piece_size' => $pieceSize,
            'piece_width' => 58,
            'piece_height' => 58,
            'max_offset' => $width - $pieceSize,
        ]);
    }

    /**
     * Verify slider puzzle position offset (Issue #74).
     */
    public function verifyPuzzle(Request $request): JsonResponse
    {
        $key = (string) $request->input('key');
        $offset = $request->input('offset');

        if (! $key || $offset === null) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter verifikasi puzzle tidak lengkap.',
            ], 422);
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
     * Helper to get classic puzzle piece SVG path with tabs and notches.
     */
    public function getPuzzlePiecePath(int $x, int $y, int $s = 44): string
    {
        $x0 = $x;
        $y0 = $y;
        $x1 = $x + $s;
        $y1 = $y + $s;

        return "M {$x0},{$y0} "
            . "H " . ($x0 + 15) . " A 7,7 0 0,1 " . ($x0 + 29) . ",{$y0} H {$x1} "
            . "V " . ($y0 + 15) . " A 7,7 0 0,1 {$x1}," . ($y0 + 29) . " V {$y1} "
            . "H " . ($x0 + 29) . " A 7,7 0 0,0 " . ($x0 + 15) . ",{$y1} H {$x0} "
            . "V " . ($y0 + 29) . " A 7,7 0 0,0 {$x0}," . ($y0 + 15) . " Z";
    }

    /**
     * Render the puzzle background SVG with cutout hole.
     */
    protected function renderPuzzleBgSvg(int $width, int $height, int $targetX, int $targetY, int $themeIndex): string
    {
        $theme = $this->getPuzzleTheme($themeIndex);
        $sceneElements = $this->getPuzzleSceneElements($width, $height, $theme);
        $holePath = $this->getPuzzlePiecePath($targetX, $targetY, 44);

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$width}\" height=\"{$height}\" viewBox=\"0 0 {$width} {$height}\" style=\"border-radius:8px; overflow:hidden; user-select:none; display:block;\">
            <defs>
                <linearGradient id=\"bgGrad\" x1=\"0%\" y1=\"0%\" x2=\"100%\" y2=\"100%\">
                    <stop offset=\"0%\" stop-color=\"{$theme['grad1']}\"/>
                    <stop offset=\"60%\" stop-color=\"{$theme['grad2']}\"/>
                    <stop offset=\"100%\" stop-color=\"#05080E\"/>
                </linearGradient>
                <filter id=\"holeShadow\" x=\"-10%\" y=\"-10%\" width=\"120%\" height=\"120%\">
                    <feDropShadow dx=\"0\" dy=\"1\" stdDeviation=\"2\" flood-color=\"#000000\" flood-opacity=\"0.9\"/>
                </filter>
            </defs>
            <rect width=\"{$width}\" height=\"{$height}\" fill=\"url(#bgGrad)\"/>
            {$sceneElements}
            <!-- Cutout Target Hole -->
            <path d=\"{$holePath}\" fill=\"#030710\" opacity=\"0.88\" filter=\"url(#holeShadow)\"/>
            <path d=\"{$holePath}\" fill=\"none\" stroke=\"#FFFFFF\" stroke-opacity=\"0.75\" stroke-width=\"1.5\" stroke-dasharray=\"3,2\"/>
            <path d=\"{$holePath}\" fill=\"none\" stroke=\"#E10600\" stroke-opacity=\"0.5\" stroke-width=\"0.75\"/>
        </svg>";
    }

    /**
     * Render the puzzle piece SVG clipped to exact puzzle shape.
     */
    protected function renderPuzzlePieceSvg(int $targetX, int $targetY, int $themeIndex): string
    {
        $theme = $this->getPuzzleTheme($themeIndex);
        $width = 320;
        $height = 160;
        $sceneElements = $this->getPuzzleSceneElements($width, $height, $theme);

        // Piece coordinate inside 58x58 viewport
        $piecePath = $this->getPuzzlePiecePath(4, 10, 44);
        $shiftX = $targetX - 4;
        $shiftY = $targetY - 10;

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"58\" height=\"58\" viewBox=\"0 0 58 58\" style=\"user-select:none; overflow:visible;\">
            <defs>
                <clipPath id=\"pieceClip\">
                    <path d=\"{$piecePath}\"/>
                </clipPath>
                <linearGradient id=\"bgGradPiece\" x1=\"0%\" y1=\"0%\" x2=\"100%\" y2=\"100%\">
                    <stop offset=\"0%\" stop-color=\"{$theme['grad1']}\"/>
                    <stop offset=\"60%\" stop-color=\"{$theme['grad2']}\"/>
                    <stop offset=\"100%\" stop-color=\"#05080E\"/>
                </linearGradient>
                <filter id=\"pieceShadow\" x=\"-30%\" y=\"-30%\" width=\"160%\" height=\"160%\">
                    <feDropShadow dx=\"0\" dy=\"3\" stdDeviation=\"3.5\" flood-color=\"#000000\" flood-opacity=\"0.85\"/>
                </filter>
            </defs>
            <g clip-path=\"url(#pieceClip)\">
                <g transform=\"translate(-{$shiftX}, -{$shiftY})\">
                    <rect width=\"{$width}\" height=\"{$height}\" fill=\"url(#bgGradPiece)\"/>
                    {$sceneElements}
                </g>
            </g>
            <!-- Glowing Outline & Drop Shadow for contrast against background -->
            <path d=\"{$piecePath}\" fill=\"none\" stroke=\"#FFFFFF\" stroke-width=\"2\" filter=\"url(#pieceShadow)\"/>
            <path d=\"{$piecePath}\" fill=\"none\" stroke=\"#E10600\" stroke-width=\"1\" opacity=\"0.7\"/>
        </svg>";
    }

    /**
     * Reusable visual scene elements across background and piece.
     */
    protected function getPuzzleSceneElements(int $width, int $height, array $theme): string
    {
        // Grid pattern lines
        $grid = '';
        for ($x = 40; $x < $width; $x += 40) {
            $grid .= "<line x1=\"{$x}\" y1=\"0\" x2=\"{$x}\" y2=\"{$height}\" stroke=\"#1E293B\" stroke-width=\"0.75\" stroke-dasharray=\"3,4\" opacity=\"0.55\"/>";
        }
        for ($y = 40; $y < $height; $y += 40) {
            $grid .= "<line x1=\"0\" y1=\"{$y}\" x2=\"{$width}\" y2=\"{$y}\" stroke=\"#1E293B\" stroke-width=\"0.75\" stroke-dasharray=\"3,4\" opacity=\"0.55\"/>";
        }

        // Radar circles on right
        $radar = "
            <circle cx=\"265\" cy=\"52\" r=\"32\" fill=\"none\" stroke=\"#334155\" stroke-width=\"0.9\" opacity=\"0.4\"/>
            <circle cx=\"265\" cy=\"52\" r=\"18\" fill=\"none\" stroke=\"#334155\" stroke-width=\"0.75\" stroke-dasharray=\"2,2\" opacity=\"0.45\"/>
            <line x1=\"265\" y1=\"15\" x2=\"265\" y2=\"89\" stroke=\"#334155\" stroke-width=\"0.75\" opacity=\"0.3\"/>
            <line x1=\"228\" y1=\"52\" x2=\"302\" y2=\"52\" stroke=\"#334155\" stroke-width=\"0.75\" opacity=\"0.3\"/>
        ";

        // Trend chart paths
        $charts = "
            <path d=\"M 14 128 C 65 148, 110 82, 160 115 C 210 148, 255 75, 306 95\" fill=\"none\" stroke=\"{$theme['chartColor']}\" stroke-width=\"2.2\" opacity=\"0.85\"/>
            <path d=\"M 14 138 C 75 118, 125 142, 185 112 C 235 88, 275 122, 306 108\" fill=\"none\" stroke=\"#38BDF8\" stroke-width=\"1.2\" opacity=\"0.45\"/>
        ";

        // Subtle glowing accent orb
        $orb = "<circle cx=\"70\" cy=\"95\" r=\"50\" fill=\"{$theme['accent']}\" opacity=\"0.12\"/>";

        $safeTitle = htmlspecialchars($theme['title'], ENT_XML1, 'UTF-8');
        $brandText = 'INDONESIA STRATEGY FORUM';

        // Brand & Theme typography
        $brand = "
            <rect x=\"14\" y=\"14\" width=\"3\" height=\"15\" fill=\"{$theme['accent']}\"/>
            <text x=\"22\" y=\"24\" fill=\"#FFFFFF\" font-family=\"Arial, sans-serif\" font-weight=\"bold\" font-size=\"10\" letter-spacing=\"1\">{$brandText}</text>
            <text x=\"22\" y=\"36\" fill=\"#94A3B8\" font-family=\"Arial, sans-serif\" font-size=\"7.5\" font-weight=\"600\" letter-spacing=\"0.5\">{$safeTitle}</text>
        ";

        return $grid . $orb . $radar . $charts . $brand;
    }

    /**
     * Get theme configuration.
     */
    protected function getPuzzleTheme(int $index): array
    {
        $themes = [
            [
                'title' => 'ANALISIS KEBIJAKAN STRATEGIS',
                'grad1' => '#0B1220',
                'grad2' => '#141E33',
                'accent' => '#E10600',
                'chartColor' => '#EF4444',
            ],
            [
                'title' => 'GEOPOLITIK & PERTAHANAN',
                'grad1' => '#0A111F',
                'grad2' => '#16233B',
                'accent' => '#E10600',
                'chartColor' => '#F87171',
            ],
            [
                'title' => 'REFORMASI HUKUM & TATA KELOLA',
                'grad1' => '#080E1A',
                'grad2' => '#121A29',
                'accent' => '#E10600',
                'chartColor' => '#E10600',
            ],
            [
                'title' => 'TRANSFORMASI EKONOMI NASIONAL',
                'grad1' => '#0B1424',
                'grad2' => '#182740',
                'accent' => '#E10600',
                'chartColor' => '#FB7185',
            ],
        ];

        return $themes[$index % count($themes)];
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

    /**
     * Render the puzzle background as a real PNG using GD (photo + ISF brand overlay + cutout hole).
     * Returns a data URI string: "data:image/png;base64,..."
     */
    protected function renderPuzzleBgPhotoSvg(int $width, int $height, int $targetX, int $targetY, string $photoFilePath): string
    {
        // Load the photo
        $ext = strtolower(pathinfo($photoFilePath, PATHINFO_EXTENSION));
        $src = match ($ext) {
            'png'  => @imagecreatefrompng($photoFilePath),
            'webp' => @imagecreatefromwebp($photoFilePath),
            default => @imagecreatefromjpeg($photoFilePath),
        };
        if (! $src) {
            return '';
        }

        // Create destination canvas
        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, true);
        imagesavealpha($canvas, true);

        // Scale & center-crop photo to fill canvas
        $sw = imagesx($src);
        $sh = imagesy($src);
        $scaleW = $width / $sw;
        $scaleH = $height / $sh;
        $scale  = max($scaleW, $scaleH);
        $newW   = (int) round($sw * $scale);
        $newH   = (int) round($sh * $scale);
        $offX   = (int) round(($width  - $newW) / 2);
        $offY   = (int) round(($height - $newH) / 2);
        imagecopyresampled($canvas, $src, $offX, $offY, 0, 0, $newW, $newH, $sw, $sh);
        imagedestroy($src);

        // Top gradient overlay (ISF Navy, 0–38px height)
        for ($y = 0; $y < 38; $y++) {
            $alpha = (int) round(100 * (1 - $y / 38)); // 0–127 scale: GD uses 0=opaque,127=transparent
            $gdAlpha = (int) round($alpha * 127 / 100);
            // ISF Navy #0B1220 = R11 G18 B32
            $col = imagecolorallocatealpha($canvas, 11, 18, 32, $gdAlpha);
            imagefilledrectangle($canvas, 0, $y, $width, $y, $col);
        }

        // ISF Red accent bar (left of brand text)
        $red = imagecolorallocate($canvas, 225, 6, 0);
        imagefilledrectangle($canvas, 12, 11, 14, 23, $red);

        // Brand text "INDONESIA STRATEGY FORUM"
        $white = imagecolorallocate($canvas, 255, 255, 255);
        // Use GD built-in font (size 2 = 12px approx) since TTF path unknown
        imagestring($canvas, 2, 19, 12, 'INDONESIA STRATEGY FORUM', $white);

        // Draw puzzle hole (filled dark + dashed border)
        $this->gdDrawPuzzleHole($canvas, $targetX, $targetY, 44);

        // Output to PNG buffer
        ob_start();
        imagepng($canvas, null, 6);
        $pngData = ob_get_clean();
        imagedestroy($canvas);

        return 'data:image/png;base64,' . base64_encode($pngData);
    }

    /**
     * Render the movable puzzle piece as a real PNG using GD (cropped from photo).
     * Returns a data URI string: "data:image/png;base64,..."
     */
    protected function renderPuzzlePiecePhotoSvg(int $targetX, int $targetY, string $photoFilePath): string
    {
        $bgWidth  = 320;
        $bgHeight = 160;
        $pieceSize = 44;
        $canvasSize = 58; // piece sits inside 58x58 canvas with 4px/10px offset
        $offsetX = 4;
        $offsetY = 10;

        // Load & scale photo same as background
        $ext = strtolower(pathinfo($photoFilePath, PATHINFO_EXTENSION));
        $src = match ($ext) {
            'png'  => @imagecreatefrompng($photoFilePath),
            'webp' => @imagecreatefromwebp($photoFilePath),
            default => @imagecreatefromjpeg($photoFilePath),
        };
        if (! $src) {
            return '';
        }
        $sw = imagesx($src);
        $sh = imagesy($src);
        $scaleW = $bgWidth / $sw;
        $scaleH = $bgHeight / $sh;
        $scale  = max($scaleW, $scaleH);
        $newW   = (int) round($sw * $scale);
        $newH   = (int) round($sh * $scale);
        $bgOffX = (int) round(($bgWidth  - $newW) / 2);
        $bgOffY = (int) round(($bgHeight - $newH) / 2);

        // Full-size background render
        $bgCanvas = imagecreatetruecolor($bgWidth, $bgHeight);
        imagecopyresampled($bgCanvas, $src, $bgOffX, $bgOffY, 0, 0, $newW, $newH, $sw, $sh);
        imagedestroy($src);

        // Create piece canvas (58x58, transparent)
        $pieceCanvas = imagecreatetruecolor($canvasSize, $canvasSize);
        $transparent = imagecolorallocatealpha($pieceCanvas, 0, 0, 0, 127);
        imagefill($pieceCanvas, 0, 0, $transparent);
        imagealphablending($pieceCanvas, false);
        imagesavealpha($pieceCanvas, true);

        // For each pixel in the puzzle piece area, copy from background
        // Puzzle piece occupies from ($targetX, $targetY) in bg → ($offsetX, $offsetY) in piece canvas
        for ($py = 0; $py < $canvasSize; $py++) {
            for ($px = 0; $px < $canvasSize; $px++) {
                $bgX = $targetX - $offsetX + $px;
                $bgY = $targetY - $offsetY + $py;
                if ($bgX < 0 || $bgX >= $bgWidth || $bgY < 0 || $bgY >= $bgHeight) {
                    continue;
                }
                $col = imagecolorat($bgCanvas, $bgX, $bgY);
                imagesetpixel($pieceCanvas, $px, $py, $col);
            }
        }
        imagedestroy($bgCanvas);

        // Now mask: only keep pixels inside the puzzle piece path shape
        // We approximate the puzzle shape with a rounded rect + tab on top/right
        $this->gdMaskPuzzlePiece($pieceCanvas, $offsetX, $offsetY, $pieceSize, $canvasSize);

        // White border outline on piece edge
        $this->gdDrawPuzzlePieceOutline($pieceCanvas, $offsetX, $offsetY, $pieceSize);

        ob_start();
        imagepng($pieceCanvas, null, 6);
        $pngData = ob_get_clean();
        imagedestroy($pieceCanvas);

        return 'data:image/png;base64,' . base64_encode($pngData);
    }

    /**
     * Draw puzzle hole (dark filled shape with white dashed border) on background canvas.
     */
    protected function gdDrawPuzzleHole(\GdImage $canvas, int $x, int $y, int $s): void
    {
        $dark  = imagecolorallocatealpha($canvas, 3, 7, 16, 28); // ~88% opaque dark
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $red   = imagecolorallocate($canvas, 225, 6, 0);

        // Fill puzzle shape with dark color (approximated as rectangle + tab bump)
        imagefilledrectangle($canvas, $x, $y, $x + $s, $y + $s, $dark);
        // Top tab (bump out above)
        imagefilledrectangle($canvas, $x + 15, $y - 7, $x + 29, $y, $dark);
        // Right tab (bump out right)
        imagefilledrectangle($canvas, $x + $s, $y + 15, $x + $s + 7, $y + 29, $dark);

        // White border rectangle
        imagerectangle($canvas, $x, $y, $x + $s, $y + $s, $white);
        // Red thin border inside
        imagerectangle($canvas, $x + 1, $y + 1, $x + $s - 1, $y + $s - 1, $red);
    }

    /**
     * Mask piece canvas: make pixels outside puzzle piece shape transparent.
     * Approximates the puzzle tab shape with a simple bounding box + tab region.
     */
    protected function gdMaskPuzzlePiece(\GdImage $canvas, int $ox, int $oy, int $s, int $cSize): void
    {
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagealphablending($canvas, false);

        for ($py = 0; $py < $cSize; $py++) {
            for ($px = 0; $px < $cSize; $px++) {
                $rx = $px - $ox; // relative to piece origin
                $ry = $py - $oy;
                // Inside main square body
                $inBody = $rx >= 0 && $rx <= $s && $ry >= 0 && $ry <= $s;
                // Inside top tab: horizontal center, sticks up
                $inTopTab = $rx >= 15 && $rx <= 29 && $ry >= -7 && $ry < 0;
                // Inside right tab: vertical center, sticks right
                $inRightTab = $rx > $s && $rx <= $s + 7 && $ry >= 15 && $ry <= 29;

                if (! $inBody && ! $inTopTab && ! $inRightTab) {
                    imagesetpixel($canvas, $px, $py, $transparent);
                }
            }
        }
        imagesavealpha($canvas, true);
    }

    /**
     * Draw white+red outline around puzzle piece shape.
     */
    protected function gdDrawPuzzlePieceOutline(\GdImage $canvas, int $ox, int $oy, int $s): void
    {
        imagealphablending($canvas, true);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $red   = imagecolorallocate($canvas, 225, 6, 0);
        // Main rectangle outline
        imagerectangle($canvas, $ox, $oy, $ox + $s, $oy + $s, $white);
        // Top tab outline
        imagerectangle($canvas, $ox + 15, $oy - 7, $ox + 29, $oy, $white);
        // Right tab outline
        imagerectangle($canvas, $ox + $s, $oy + 15, $ox + $s + 7, $oy + 29, $white);
        // Red accent
        imagerectangle($canvas, $ox + 1, $oy + 1, $ox + $s - 1, $oy + $s - 1, $red);
    }
}

