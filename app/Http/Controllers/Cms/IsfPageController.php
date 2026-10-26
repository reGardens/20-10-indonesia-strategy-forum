<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the ISF CMS Inertia page shells. The pages read and write their
 * data through the CMS JSON API (/api/cms/*), so no data props are passed
 * except the record id on edit pages.
 */
class IsfPageController extends Controller
{
    /**
     * URL segment => Inertia page folder under Pages/Cms/Isf/.
     */
    private const MODULES = [
        'banner' => 'Banner',
        'artikel' => 'Artikel',
        'infografik' => 'Infografik',
        'tokoh' => 'Tokoh',
        'isu' => 'Isu',
        'data-grafik' => 'DataGrafik',
        'alert' => 'Alert',
        'email-briefing' => 'EmailBriefing',
    ];

    public function index(Request $request): Response
    {
        return Inertia::render('Cms/Isf/'.$this->folder($request).'/Index');
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Cms/Isf/'.$this->folder($request).'/Form', ['id' => null]);
    }

    public function edit(Request $request, string $id): Response
    {
        abort_unless(ctype_digit($id), 404);

        return Inertia::render('Cms/Isf/'.$this->folder($request).'/Form', ['id' => (int) $id]);
    }

    public function settings(): Response
    {
        return Inertia::render('Cms/Isf/PengaturanHalaman/Index');
    }

    /**
     * The module comes from the route default "module" (see routes/web.php).
     */
    private function folder(Request $request): string
    {
        $module = (string) $request->route('module');
        abort_unless(isset(self::MODULES[$module]), 404);

        return self::MODULES[$module];
    }
}
