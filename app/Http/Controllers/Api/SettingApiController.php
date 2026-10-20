<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wedding;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingApiController extends Controller
{
    public function show($slug)
    {
        if (str_starts_with($slug, 'demo-')) {
            $templateId = str_replace('demo-', '', $slug);
            
            $themeColors = [
                'primary' => '#1b4332',
                'secondary' => '#40916c',
                'accent' => '#d8f3dc',
            ];
            $fontFamily = 'Playfair Display';
            
            $hardcodedDefaults = [
                'jawa-klasik' => ['primary' => '#8B4513', 'secondary' => '#D2691E', 'accent' => '#FFD700', 'font' => 'Playfair Display'],
                'sunda-pasundan' => ['primary' => '#2D5016', 'secondary' => '#4A7C2E', 'accent' => '#C5A028', 'font' => 'Lora'],
                'bali-dewata' => ['primary' => '#8B1A1A', 'secondary' => '#C41E3A', 'accent' => '#FFD700', 'font' => 'Cinzel'],
                'betawi-jakarta' => ['primary' => '#D4652F', 'secondary' => '#2E7D32', 'accent' => '#FFC107', 'font' => 'Poppins'],
                'minang-rantau' => ['primary' => '#7B2D26', 'secondary' => '#C9A84C', 'accent' => '#F5E6CA', 'font' => 'Cinzel'],
                'bugis-makassar' => ['primary' => '#1B3A5C', 'secondary' => '#2E6B9E', 'accent' => '#C9A84C', 'font' => 'Cormorant Garamond'],
                'dayak-borneo' => ['primary' => '#8B0000', 'secondary' => '#2C1810', 'accent' => '#DAA520', 'font' => 'Montserrat'],
                'toraja-sulawesi' => ['primary' => '#4A1C1C', 'secondary' => '#8B4513', 'accent' => '#CD853F', 'font' => 'Playfair Display'],
                'melayu-riau' => ['primary' => '#B8860B', 'secondary' => '#8B6914', 'accent' => '#FFFACD', 'font' => 'Lora'],
                'papua-cendrawasih' => ['primary' => '#5C3317', 'secondary' => '#8B4513', 'accent' => '#CD5C5C', 'font' => 'Montserrat'],
            ];
            
            if (isset($hardcodedDefaults[$templateId])) {
                $themeColors = [
                    'primary' => $hardcodedDefaults[$templateId]['primary'],
                    'secondary' => $hardcodedDefaults[$templateId]['secondary'],
                    'accent' => $hardcodedDefaults[$templateId]['accent'],
                ];
                $fontFamily = $hardcodedDefaults[$templateId]['font'];
            } else {
                $custom = \App\Models\CustomTemplate::where('slug', $templateId)->first();
                if ($custom && is_array($custom->config)) {
                    $themeColors = [
                        'primary' => $custom->config['primary_color'] ?? '#1b4332',
                        'secondary' => $custom->config['secondary_color'] ?? '#40916c',
                        'accent' => $custom->config['accent_color'] ?? '#d8f3dc',
                    ];
                    $fontFamily = $custom->config['font_family'] ?? 'Playfair Display';
                }
            }
            
            return response()->json([
                'id' => 9999,
                'templateId' => $templateId,
                'themeColors' => $themeColors,
                'galleryLayout' => 'masonry',
                'moderationEnabled' => true,
                'passwordProtected' => false,
                'password' => '',
                'showWatermark' => true,
                'customDomain' => '',
                'healthProtocol' => '',
                'liveStreamUrl' => '',
                'shippingAddress' => '',
                'seoMeta' => [
                    'title' => 'Reza & Reza Wedding',
                    'description' => 'Kami mengundang Anda untuk hadir di hari bahagia kami',
                    'image' => '',
                ],
                'qrisImageUrl' => '',
                'createdAt' => now()->toIso8601String(),
                'updatedAt' => now()->toIso8601String(),
            ]);
        }

        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $setting = Setting::where('wedding_id', $wedding->id)->firstOrFail();

        return response()->json($this->formatSetting($setting));
    }

    public function store(Request $request, $slug)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $setting = Setting::where('wedding_id', $wedding->id)->firstOrFail();

        $setting->update([
            'template_id' => $request->input('templateId', 'batik-elegance'),
            'theme_colors' => $request->input('themeColors', []),
            'gallery_layout' => $request->input('galleryLayout', 'masonry'),
            'moderation_enabled' => filter_var($request->input('moderationEnabled', true), FILTER_VALIDATE_BOOLEAN),
            'password_protected' => filter_var($request->input('passwordProtected', false), FILTER_VALIDATE_BOOLEAN),
            'password' => $request->input('password', ''),
            'show_watermark' => filter_var($request->input('showWatermark', true), FILTER_VALIDATE_BOOLEAN),
            'custom_domain' => $request->input('customDomain', ''),
            'health_protocol' => $request->input('healthProtocol', ''),
            'live_stream_url' => $request->input('liveStreamUrl', ''),
            'shipping_address' => $request->input('shippingAddress', ''),
            'seo_meta' => $request->input('seoMeta', []),
            'qris_image_url' => $request->input('qrisImageUrl', ''),
        ]);

        return response()->json($this->formatSetting($setting));
    }

    private function formatSetting(Setting $setting)
    {
        return [
            'id' => $setting->id,
            'templateId' => $setting->template_id,
            'themeColors' => $setting->theme_colors ?? [],
            'galleryLayout' => $setting->gallery_layout,
            'moderationEnabled' => (bool)$setting->moderation_enabled,
            'passwordProtected' => (bool)$setting->password_protected,
            'password' => $setting->password,
            'showWatermark' => (bool)$setting->show_watermark,
            'customDomain' => $setting->custom_domain,
            'healthProtocol' => $setting->health_protocol,
            'liveStreamUrl' => $setting->live_stream_url,
            'shippingAddress' => $setting->shipping_address,
            'seoMeta' => $setting->seo_meta ?? [],
            'qrisImageUrl' => $setting->qris_image_url,
            'createdAt' => $setting->created_at ? $setting->created_at->toIso8601String() : null,
            'updatedAt' => $setting->updated_at ? $setting->updated_at->toIso8601String() : null,
        ];
    }
}
