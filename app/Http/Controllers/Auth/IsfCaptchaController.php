<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class IsfCaptchaController extends Controller
{
    /**
     * Generate a fresh visual CAPTCHA challenge.
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
        // Store in cache for 10 minutes
        Cache::put('captcha:' . $key, strtolower($code), now()->addMinutes(10));

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
}

