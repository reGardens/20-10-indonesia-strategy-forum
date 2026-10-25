<?php

namespace App\Http\Controllers\Isf;

use App\Http\Controllers\Controller;
use App\Models\Isf\Briefing;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

/**
 * Browser preview of the ISF member "Email Briefing" template.
 *
 * Preview only: no mail is sent, no scheduler, no mail configuration.
 * Content comes from the CMS module "Email Briefing" (isf_briefings).
 * The public route is NOT protected by auth yet; it shows only the latest
 * published briefing. Drafts are previewable from the CMS (auth) route.
 */
class BriefingPreviewController extends Controller
{
    /**
     * GET /anggota/briefing/preview — latest published ("terbit") briefing.
     */
    public function show(): View
    {
        $briefing = Briefing::query()->terbit()
            ->orderByDesc('tanggal')->orderByDesc('id')->first();

        abort_if(! $briefing, 404);

        return $this->render($briefing);
    }

    /**
     * GET /cms/email-briefing/{briefing}/preview — any status (CMS, auth + feature).
     */
    public function cmsPreview(Briefing $briefing): View
    {
        return $this->render($briefing);
    }

    private function render(Briefing $briefing): View
    {
        return view('emails.isf.briefing', ['briefing' => $this->toTemplateData($briefing)]);
    }

    /**
     * Maps the model to the array shape used by resources/views/emails/isf/briefing.blade.php.
     */
    private function toTemplateData(Briefing $briefing): array
    {
        return [
            'tanggal' => Carbon::parse($briefing->tanggal)->locale('id')->translatedFormat('j F Y'),
            'penerima' => $briefing->penerima,
            'organisasi' => $briefing->organisasi,
            'ringkasan' => [
                'judul' => $briefing->ringkasan_judul,
                'catatan' => $briefing->ringkasan_catatan,
                'data' => $briefing->ringkasan_data ?? [],
            ],
            'pembuka' => $briefing->pembuka,
            'poin' => array_map(fn (array $poin) => [
                ...$poin,
                'url' => $this->absoluteUrl($poin['url'] ?? '/'),
            ], $briefing->poin ?? []),
            'penutup' => $briefing->penutup,
            'ctaUrl' => $this->absoluteUrl($briefing->cta_url),
            'tautan' => array_map(fn (array $tautan) => [
                ...$tautan,
                'url' => $this->absoluteUrl($tautan['url'] ?? '/'),
            ], $briefing->tautan ?? []),
        ];
    }

    /**
     * Relative in-site paths become absolute (email links need a full URL).
     */
    private function absoluteUrl(string $url): string
    {
        return str_starts_with($url, '/') ? url($url) : $url;
    }
}
