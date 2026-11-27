<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Controllers\Controller;
use App\Mail\MemberReportNotificationMail;
use App\Models\Isf\MemberReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MemberReportController extends Controller
{
    /**
     * GET /api/cms/laporan-member — Daftar laporan member (Role selain member melihat semua, member melihat miliknya).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isOnlyMember = $user->hasRole('member') && !$user->hasAnyRole(['admin', 'editor', 'super-admin']);

        $query = MemberReport::with(['user:id,name,email,whatsapp'])
            ->orderByDesc('id');

        if ($isOnlyMember) {
            $query->where('user_id', $user->id);
        } else {
            // Filter status untuk admin / editor / super-admin
            if ($status = $request->query('status')) {
                if ($status !== 'semua') {
                    $query->where('status', $status);
                }
            }
            if ($cat = $request->query('category')) {
                if ($cat !== 'semua') {
                    $query->where('category', $cat);
                }
            }
            if ($q = $request->query('search')) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('subject', 'like', "%{$q}%")
                        ->orWhere('message', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($uq) use ($q) {
                            $uq->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        });
                });
            }
        }

        $perPage = (int) $request->query('per_page', 15);
        $reports = $query->paginate($perPage);

        return response()->json([
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
            'counts' => [
                'pending' => MemberReport::where('status', 'pending')->count(),
                'in_review' => MemberReport::where('status', 'in_review')->count(),
                'resolved' => MemberReport::where('status', 'resolved')->count(),
            ],
        ]);
    }

    /**
     * POST /api/cms/laporan-member — Kirim laporan baru dari member + kirim email ke ISF.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in([
                'teknis', 'kendala_teknis',
                'konten', 'konten_naskah',
                'survei',
                'tokoh_rating',
                'akun', 'kebijakan',
                'lainnya'
            ])],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'attachment' => ['nullable', 'file', 'max:5120'], // Max 5MB
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('reports', 'public');
        }

        $report = MemberReport::create([
            'user_id' => $user->id,
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'attachment_path' => $attachmentPath ? "/storage/{$attachmentPath}" : null,
            'status' => 'pending',
        ]);

        $report->load('user');

        // Kirim notifikasi email ke email resmi ISF
        try {
            $recipient = config('mail.from.address') ?: 'redaksi@indonesiastrategyforum.id';
            Mail::to($recipient)->send(new MemberReportNotificationMail($report));
        } catch (\Throwable $e) {
            Log::warning('[MemberReport] Gagal mengirim email notifikasi: ' . $e->getMessage());
        }

        // Kirim Web Push Notification HANYA kepada Super Admin dan Admin
        try {
            $pushService = app(\App\Services\IsfPushNotificationService::class);
            $admins = \App\Models\User::role(['super-admin', 'admin'])->get();
            foreach ($admins as $adminUser) {
                $pushService->sendToUser(
                    $adminUser,
                    'Report Baru dari Member',
                    ($user->name ?: 'Member') . ': ' . \Illuminate\Support\Str::limit($report->subject, 60),
                    url('/cms/laporan-member'),
                    ['type' => 'member_report', 'report_id' => $report->id]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('[MemberReport] Gagal mengirim push notifikasi ke admin: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Laporan Anda berhasil dikirim ke tim redaksi & sistem ISF. Terima kasih atas masukan Anda.',
            'data' => $report,
        ], 201);
    }

    /**
     * GET /api/cms/laporan-member/{id} — Detail laporan.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $isOnlyMember = $user->hasRole('member') && !$user->hasAnyRole(['admin', 'editor', 'super-admin']);

        $query = MemberReport::with(['user:id,name,email,whatsapp']);
        if ($isOnlyMember) {
            $query->where('user_id', $user->id);
        }

        $report = $query->find($id);
        if (!$report) {
            return response()->json(['message' => 'Laporan tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => $report,
        ]);
    }

    /**
     * PUT /api/cms/laporan-member/{id} — Update status & catatan tindak lanjut laporan (Admin/Super-Admin/Editor).
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        if ($user->hasRole('member') && !$user->hasAnyRole(['admin', 'editor', 'super-admin'])) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $report = MemberReport::find($id);
        if (!$report) {
            return response()->json(['message' => 'Laporan tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['pending', 'in_review', 'resolved', 'rejected'])],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $updateData = [
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $report->admin_notes,
        ];

        if ($validated['status'] === 'resolved' && !$report->resolved_at) {
            $updateData['resolved_at'] = now();
        }

        $report->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Status laporan berhasil diperbarui.',
            'data' => $report,
        ]);
    }
}
