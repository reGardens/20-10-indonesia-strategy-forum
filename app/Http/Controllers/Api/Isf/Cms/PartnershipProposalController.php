<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Requests\Isf\PartnershipProposalUpdateRequest;
use App\Models\Isf\PartnershipProposal;
use App\Models\Isf\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CMS API — Manajemen Permintaan Kemitraan (RFP) (feature: media_kit).
 */
class PartnershipProposalController extends CmsResourceController
{
    protected string $model = PartnershipProposal::class;

    protected array $listColumns = [
        'id', 'institution_name', 'pic_name', 'email', 'phone',
        'partnership_type', 'status', 'internal_notes', 'contacted_at',
        'contacted_by', 'created_at', 'updated_at',
    ];

    protected array $searchColumns = [
        'institution_name', 'pic_name', 'email', 'phone', 'message', 'internal_notes',
    ];

    protected array $filterColumns = ['status', 'partnership_type'];

    protected array $order = ['id' => 'desc'];

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(PartnershipProposal::STATUSES))],
            'partnership_type' => ['nullable', 'in:'.implode(',', array_keys(PartnershipProposal::TYPES))],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $query = PartnershipProposal::query()->with('contactedBy:id,name');

        if ($keyword = trim((string) $request->query('q', ''))) {
            $like = '%'.addcslashes($keyword, '\\%_').'%';
            $query->where(function (Builder $q) use ($like) {
                foreach ($this->searchColumns as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('partnership_type')) {
            $query->where('partnership_type', $type);
        }

        foreach ($this->order as $column => $direction) {
            $query->orderBy($column, $direction);
        }

        $perPage = (int) $request->query('per_page', 10);
        if ($perPage < 1 || $perPage > 500) {
            $perPage = 10;
        }

        return response()->json($query->paginate($perPage)->withQueryString());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'institution_name' => ['required', 'string', 'min:2', 'max:255'],
            'pic_name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'phone' => ['required', 'string', 'min:8', 'max:50'],
            'partnership_type' => ['required', 'string', 'in:'.implode(',', array_keys(PartnershipProposal::TYPES))],
            'message' => ['required', 'string', 'min:5', 'max:10000'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_keys(PartnershipProposal::STATUSES))],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $proposal = PartnershipProposal::create([
            ...$validated,
            'status' => $validated['status'] ?? PartnershipProposal::STATUS_BARU,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data kemitraan baru berhasil ditambahkan.',
            'data' => $proposal,
        ], 201);
    }

    public function show(PartnershipProposal $partnershipProposal): JsonResponse
    {
        $partnershipProposal->load('contactedBy:id,name,email');

        return response()->json([
            'data' => [
                ...$partnershipProposal->toArray(),
                'partnership_type_label' => $partnershipProposal->partnership_type_label,
                'status_label' => $partnershipProposal->status_label,
                'created_at_formatted' => $partnershipProposal->created_at?->format('d M Y, H:i'),
                'contacted_at_formatted' => $partnershipProposal->contacted_at?->format('d M Y, H:i'),
            ],
        ]);
    }

    public function update(PartnershipProposalUpdateRequest $request, PartnershipProposal $partnershipProposal): JsonResponse
    {
        $validated = $request->validated();
        $payload = [...$validated];

        if (isset($validated['status']) && $validated['status'] === PartnershipProposal::STATUS_DIHUBUNGI && empty($partnershipProposal->contacted_at)) {
            $payload['contacted_at'] = now();
            $payload['contacted_by'] = $request->user()?->id;
        }

        $partnershipProposal->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Data permintaan kemitraan berhasil diperbarui.',
            'data' => $partnershipProposal->fresh(['contactedBy:id,name']),
        ]);
    }

    public function destroy(PartnershipProposal $partnershipProposal): Response
    {
        return $this->destroyModel($partnershipProposal);
    }

    public function markContacted(Request $request, PartnershipProposal $partnershipProposal): JsonResponse
    {
        $partnershipProposal->update([
            'status' => PartnershipProposal::STATUS_DIHUBUNGI,
            'contacted_at' => now(),
            'contacted_by' => $request->user()?->id,
            'internal_notes' => $request->input('notes') ?? $partnershipProposal->internal_notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status berhasil diubah menjadi "Dihubungi".',
            'data' => $partnershipProposal->fresh(['contactedBy:id,name']),
        ]);
    }

    public function updateStatus(Request $request, PartnershipProposal $partnershipProposal): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(PartnershipProposal::STATUSES))],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $payload = [
            'status' => $request->input('status'),
        ];

        if ($request->has('internal_notes')) {
            $payload['internal_notes'] = $request->input('internal_notes');
        }

        if ($payload['status'] === PartnershipProposal::STATUS_DIHUBUNGI && empty($partnershipProposal->contacted_at)) {
            $payload['contacted_at'] = now();
            $payload['contacted_by'] = $request->user()?->id;
        }

        $partnershipProposal->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Status proposal kemitraan diperbarui.',
            'data' => $partnershipProposal->fresh(['contactedBy:id,name']),
        ]);
    }

    /**
     * Ekspor data permintaan kemitraan ke file CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $status = $request->query('status');
        $type = $request->query('partnership_type');

        $query = PartnershipProposal::query()->with('contactedBy:id,name');

        if ($status) {
            $query->where('status', $status);
        }
        if ($type) {
            $query->where('partnership_type', $type);
        }

        $proposals = $query->orderByDesc('id')->get();
        $filename = 'permintaan-kemitraan-isf-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($proposals) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Tanggal Masuk',
                'Nama Lembaga / Perusahaan',
                'Nama PIC',
                'Email',
                'Telepon / WhatsApp',
                'Jenis Kemitraan',
                'Status',
                'Pesan',
                'Catatan Internal',
                'Waktu Dihubungi',
                'Dihubungi Oleh',
            ]);

            foreach ($proposals as $p) {
                fputcsv($handle, [
                    $p->id,
                    $p->created_at?->format('Y-m-d H:i:s') ?? '',
                    $p->institution_name,
                    $p->pic_name,
                    $p->email,
                    $p->phone,
                    $p->partnership_type_label,
                    $p->status_label,
                    $p->message,
                    $p->internal_notes ?? '',
                    $p->contacted_at?->format('Y-m-d H:i:s') ?? '',
                    $p->contactedBy?->name ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Ambil pengaturan konten section halaman /media-kit/ dari isf_settings.
     */
    public function getSections(): JsonResponse
    {
        $settings = Setting::get('media_kit', []);

        return response()->json([
            'data' => [
                'pitch' => $settings['pitch'] ?? [
                    'badge' => 'Informasi Iklan & Kemitraan',
                    'title' => 'Menghubungkan Gagasan, Kebijakan, dan Pengambil Keputusan Nasional',
                    'deck' => 'Indonesia Strategy Forum (ISF) adalah kanal referensi independen untuk para pembuat kebijakan, eksekutif puncak korporasi, akademisi, dan profesional hukum yang membentuk arah pembangunan Indonesia.',
                ],
                'audiens' => $settings['audiens'] ?? [
                    'total_monthly_readers' => '150.000+',
                    'total_monthly_readers_label' => 'Pembaca Terverifikasi / Bulan',
                    'executive_share' => '45.000+',
                    'executive_share_label' => 'Eksekutif & C-Suite',
                    'retention_rate' => '85%',
                    'retention_rate_label' => 'Tingkat Retensi Pembaca',
                ],
                'channels' => $settings['channels'] ?? [
                    [
                        'id' => 'banner',
                        'title' => 'Display Banner & Slot Iklan Strategis',
                        'kicker' => 'VISIBILITAS TERTARGET',
                        'desc' => 'Penempatan visual terkurasi tanpa gangguan clutter: Banner Beranda, Billboard Rubrik Utama, dan In-Article Sidebar.',
                    ],
                    [
                        'id' => 'advertorial',
                        'title' => 'Advertorial & Analisis Kebijakan Khusus',
                        'kicker' => 'THOUGHT LEADERSHIP',
                        'desc' => 'Kajian komprehensif, wawancara kepemimpinan, dan laporan sektoral mendalam bertanda Transparansi Kemitraan.',
                    ],
                    [
                        'id' => 'event',
                        'title' => 'Event & Roundtable Diskusi Kebijakan',
                        'kicker' => 'DIALOG LANGSUNG',
                        'desc' => 'Penyelenggaraan roundtable tertutup atau seminar eksekutif yang mempertemukan regulator dan pimpinan industri.',
                    ],
                    [
                        'id' => 'riset',
                        'title' => 'Sponsorship Riset & Policy Brief',
                        'kicker' => 'EVIDENCE-BASED POLICY',
                        'desc' => 'Kolaborasi penyusunan naskah ringkasan kebijakan (Policy Brief) dan visualisasi infografik data.',
                    ],
                ],
                'workflow' => $settings['workflow'] ?? [
                    ['step' => '01', 'title' => 'Pengajuan Kemitraan (RFP)', 'desc' => 'Klien atau lembaga mengisi formulir Request for Proposal di bawah ini dengan melampirkan objektif dan jenis kemitraan.'],
                    ['step' => '02', 'title' => 'Konsultasi & Penyelarasan (1x24 Jam)', 'desc' => 'Tim Kemitraan ISF akan menghubungi PIC dalam 1x24 jam kerja untuk menyelaraskan kebutuhan.'],
                    ['step' => '03', 'title' => 'Penyusunan Proposal Khusus', 'desc' => 'ISF menyusun proposal kerjasama yang disesuaikan secara terukur (tailor-made).'],
                    ['step' => '04', 'title' => 'Eksekusi & Laporan Dampak', 'desc' => 'Materi dipublikasikan atau event dijalankan sesuai standar editorial ISF, disertai laporan dampak.'],
                ],
                'faq' => $settings['faq'] ?? [
                    ['q' => 'Mengapa ISF tidak menampilkan daftar harga (Rate Card) di website?', 'a' => 'ISF tidak menggunakan tarif baku satu harga karena setiap kolaborasi dirancang secara tailor-made berdasarkan format kampanye, kedalaman riset, dan tujuan strategis mitra.'],
                    ['q' => 'Berapa lama formulir RFP ditindaklanjuti?', 'a' => 'Tim Kemitraan ISF berkomitmen menghubungi Anda dalam kurun waktu 1x24 jam kerja setelah formulir dikirimkan.'],
                    ['q' => 'Apakah instansi pemerintah, BUMN, dan asosiasi diperbolehkan bermitra?', 'a' => 'Ya, ISF bermitra aktif dengan kementerian, lembaga negara, BUMN, asosiasi industri, institusi akademik, dan korporasi swasta.'],
                    ['q' => 'Bagaimana pedoman etika editorial untuk konten berbayar?', 'a' => 'ISF memegang teguh independensi editorial. Seluruh konten berbayar diberi penanda transparan seperti "Kemitraan Strategis" atau "Advertorial".'],
                ],
            ],
        ]);
    }

    /**
     * Simpan pembaruan konten section halaman kemitraan.
     */
    public function updateSections(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pitch' => ['nullable', 'array'],
            'pitch.badge' => ['nullable', 'string', 'max:255'],
            'pitch.title' => ['nullable', 'string', 'max:255'],
            'pitch.deck' => ['nullable', 'string', 'max:2000'],
            'audiens' => ['nullable', 'array'],
            'audiens.total_monthly_readers' => ['nullable', 'string', 'max:100'],
            'audiens.total_monthly_readers_label' => ['nullable', 'string', 'max:255'],
            'audiens.executive_share' => ['nullable', 'string', 'max:100'],
            'audiens.executive_share_label' => ['nullable', 'string', 'max:255'],
            'audiens.retention_rate' => ['nullable', 'string', 'max:50'],
            'audiens.retention_rate_label' => ['nullable', 'string', 'max:255'],
            'channels' => ['nullable', 'array'],
            'channels.*.id' => ['nullable', 'string'],
            'channels.*.title' => ['required', 'string', 'max:255'],
            'channels.*.kicker' => ['nullable', 'string', 'max:255'],
            'channels.*.desc' => ['required', 'string', 'max:2000'],
            'workflow' => ['nullable', 'array'],
            'workflow.*.step' => ['required', 'string', 'max:10'],
            'workflow.*.title' => ['required', 'string', 'max:255'],
            'workflow.*.desc' => ['required', 'string', 'max:2000'],
            'faq' => ['nullable', 'array'],
            'faq.*.q' => ['required', 'string', 'max:500'],
            'faq.*.a' => ['required', 'string', 'max:3000'],
        ]);

        $existing = Setting::get('media_kit', []);
        $merged = array_merge($existing, $validated);
        Setting::put('media_kit', $merged);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan konten section kemitraan berhasil disimpan.',
            'data' => $merged,
        ]);
    }
}

