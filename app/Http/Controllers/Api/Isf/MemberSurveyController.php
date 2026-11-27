<?php

namespace App\Http\Controllers\Api\Isf;

use App\Http\Controllers\Controller;
use App\Models\Isf\Survey;
use App\Models\Isf\SurveyResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberSurveyController extends Controller
{
    /**
     * GET /api/isf/member/survei — Katalog survei aktif dengan status pengisian per member.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $surveys = Survey::query()
            ->where('status', 'terbit')
            ->where('is_member_active', true)
            ->orderBy('order')
            ->orderByDesc('id')
            ->get();

        $responsesByUser = $user
            ? SurveyResponse::where('user_id', $user->id)
                ->whereIn('survey_id', $surveys->pluck('id'))
                ->get()
                ->keyBy('survey_id')
            : collect();

        $data = $surveys->map(function (Survey $survey) use ($responsesByUser) {
            $resp = $responsesByUser->get($survey->id);
            $questions = is_array($survey->questions) ? $survey->questions : [];
            $totalQuestions = count($questions);

            $memberStatus = 'belum_diisi';
            $progress = 0;
            $answeredCount = 0;

            if ($resp) {
                if ($resp->status === 'completed') {
                    $memberStatus = 'selesai';
                    $progress = 100;
                    $answeredCount = $totalQuestions;
                } else {
                    $memberStatus = 'draft';
                    $answers = is_array($resp->answers) ? $resp->answers : [];
                    $answeredCount = count(array_filter($answers, fn ($v) => !empty($v)));
                    $progress = $totalQuestions > 0 ? (int) round(($answeredCount / $totalQuestions) * 100) : 50;
                }
            }

            return [
                'id' => $survey->id,
                'title' => $survey->title,
                'slug' => $survey->slug,
                'institution' => $survey->institution,
                'is_isf' => (bool) $survey->is_isf,
                'topic' => $survey->topic,
                'region' => $survey->region,
                'survey_period' => $survey->survey_period,
                'estimated_minutes' => $survey->estimated_minutes ?: 5,
                'editorial_intro' => $survey->editorial_intro,
                'total_questions' => $totalQuestions,
                'answered_count' => $answeredCount,
                'member_status' => $memberStatus, // belum_diisi | draft | selesai
                'progress' => $progress,
                'completed_at' => $resp?->completed_at?->format('d M Y H:i'),
                'updated_at' => $resp?->updated_at?->format('d M Y H:i'),
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * GET /api/isf/member/survei/widget — Data ringkasan survei untuk Member Dashboard.
     */
    public function widget(Request $request): JsonResponse
    {
        $user = $request->user();

        $surveys = Survey::query()
            ->where('status', 'terbit')
            ->where('is_member_active', true)
            ->orderBy('order')
            ->orderByDesc('id')
            ->get();

        $responsesByUser = $user
            ? SurveyResponse::where('user_id', $user->id)
                ->whereIn('survey_id', $surveys->pluck('id'))
                ->get()
                ->keyBy('survey_id')
            : collect();

        $waitingList = [];
        $draftList = [];
        $completedList = [];

        foreach ($surveys as $survey) {
            $resp = $responsesByUser->get($survey->id);
            $questions = is_array($survey->questions) ? $survey->questions : [];
            $totalQuestions = count($questions);

            $item = [
                'id' => $survey->id,
                'title' => $survey->title,
                'slug' => $survey->slug,
                'topic' => $survey->topic,
                'estimated_minutes' => $survey->estimated_minutes ?: 5,
                'total_questions' => $totalQuestions,
                'is_isf' => (bool) $survey->is_isf,
            ];

            if ($resp && $resp->status === 'completed') {
                $item['completed_at'] = $resp->completed_at?->format('d M Y');
                $completedList[] = $item;
            } elseif ($resp && $resp->status === 'draft') {
                $answers = is_array($resp->answers) ? $resp->answers : [];
                $answeredCount = count(array_filter($answers, fn ($v) => !empty($v)));
                $item['progress'] = $totalQuestions > 0 ? (int) round(($answeredCount / $totalQuestions) * 100) : 50;
                $item['updated_at'] = $resp->updated_at?->diffForHumans();
                $draftList[] = $item;
            } else {
                $waitingList[] = $item;
            }
        }

        return response()->json([
            'summary' => [
                'total_active' => $surveys->count(),
                'waiting_count' => count($waitingList),
                'draft_count' => count($draftList),
                'completed_count' => count($completedList),
            ],
            'survei_menunggu' => array_slice(array_merge($draftList, $waitingList), 0, 5),
            'survei_selesai' => array_slice($completedList, 0, 5),
        ]);
    }

    /**
     * GET /api/isf/member/survei/{id} — Detail survei, pertanyaan, dan respon member.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $survey = $this->findSurvey($id);
        if (!$survey) {
            return response()->json(['message' => 'Survei tidak ditemukan atau belum aktif.'], 404);
        }

        $user = $request->user();
        $response = $user
            ? SurveyResponse::where('survey_id', $survey->id)
                ->where('user_id', $user->id)
                ->first()
            : null;

        $questions = is_array($survey->questions) ? $survey->questions : [];

        // Pre-fill demografi default jika belum tersimpan di draf
        $demographics = $response?->demographics ?? [
            'age' => '',
            'domicile' => '',
            'profession' => '',
        ];

        return response()->json([
            'survey' => [
                'id' => $survey->id,
                'title' => $survey->title,
                'slug' => $survey->slug,
                'institution' => $survey->institution,
                'topic' => $survey->topic,
                'region' => $survey->region,
                'survey_period' => $survey->survey_period,
                'estimated_minutes' => $survey->estimated_minutes ?: 5,
                'editorial_intro' => $survey->editorial_intro,
                'sample_size' => $survey->sample_size,
                'questions' => $questions,
                'is_isf' => (bool) $survey->is_isf,
            ],
            'response' => $response ? [
                'id' => $response->id,
                'status' => $response->status,
                'answers' => $response->answers ?? (object)[],
                'demographics' => $demographics,
                'current_step' => $response->current_step ?? 1,
                'completed_at' => $response->completed_at?->format('d M Y H:i'),
            ] : null,
            'is_completed' => $response?->status === 'completed',
            'is_draft' => $response?->status === 'draft',
        ]);
    }

    /**
     * POST /api/isf/member/survei/{id}/draft — Simpan draf jawaban sementara (Auto-Save / Simpan).
     */
    public function saveDraft(Request $request, $id): JsonResponse
    {
        $survey = $this->findSurvey($id);
        if (!$survey) {
            return response()->json(['message' => 'Survei tidak ditemukan.'], 404);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Cek double-voting / sudah selesai: Draf tidak boleh menimpa jawaban yang sudah final
        $existing = SurveyResponse::where('survey_id', $survey->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && $existing->status === 'completed') {
            return response()->json([
                'message' => 'Survei ini sudah diselesaikan dan tidak dapat diubah kembali.',
            ], 422);
        }

        $response = SurveyResponse::updateOrCreate(
            [
                'survey_id' => $survey->id,
                'user_id' => $user->id,
            ],
            [
                'status' => 'draft',
                'answers' => $request->input('answers', []),
                'demographics' => $request->input('demographics', []),
                'current_step' => (int) $request->input('current_step', 1),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Draf berhasil disimpan.',
            'saved_at' => now()->format('H:i:s'),
            'response_id' => $response->id,
        ]);
    }

    /**
     * POST /api/isf/member/survei/{id}/submit — Kirim jawaban final dengan pencegahan double-voting.
     */
    public function submit(Request $request, $id): JsonResponse
    {
        $survey = $this->findSurvey($id);
        if (!$survey) {
            return response()->json(['message' => 'Survei tidak ditemukan.'], 404);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // 1. Pencegahan Double-Voting (Satu Akun, Satu Suara)
        $existing = SurveyResponse::where('survey_id', $survey->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing && $existing->status === 'completed') {
            return response()->json([
                'message' => 'Anda sudah pernah mengisi survei ini. Satu akun hanya dapat memberikan satu suara.',
            ], 422);
        }

        $answers = $request->input('answers', []);
        $demographics = $request->input('demographics', []);

        // 2. Validasi Pertanyaan Wajib (Required)
        $questions = is_array($survey->questions) ? $survey->questions : [];
        $errors = [];

        foreach ($questions as $q) {
            $qId = $q['id'] ?? null;
            if (!$qId) continue;

            $isRequired = !empty($q['required']);
            $ans = $answers[$qId] ?? null;

            if ($isRequired) {
                if (is_null($ans) || $ans === '' || (is_array($ans) && empty($ans))) {
                    $errors["answers.{$qId}"] = "Pertanyaan \"{$q['question']}\" wajib diisi.";
                }
            }
        }

        // 3. Validasi Data Demografi Wajib
        if (empty($demographics['age'])) {
            $errors['demographics.age'] = 'Kelompok usia wajib dipilih untuk analisis segmen data.';
        }
        if (empty($demographics['domicile'])) {
            $errors['demographics.domicile'] = 'Domisili wilayah wajib diisi.';
        }
        if (empty($demographics['profession'])) {
            $errors['demographics.profession'] = 'Latar belakang profesi wajib dipilih.';
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Mohon lengkapi semua isian wajib sebelum mengirim jawaban.',
                'errors' => $errors,
            ], 422);
        }

        // 4. Simpan Final Jawaban secara Atomik
        DB::transaction(function () use ($survey, $user, $answers, $demographics) {
            SurveyResponse::updateOrCreate(
                [
                    'survey_id' => $survey->id,
                    'user_id' => $user->id,
                ],
                [
                    'status' => 'completed',
                    'answers' => $answers,
                    'demographics' => $demographics,
                    'completed_at' => now(),
                ]
            );
        });

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih! Jawaban survei Anda berhasil dikirim dan dicatat.',
            'redirect' => "/member/survei/{$survey->id}/selesai",
        ]);
    }

    /**
     * GET /api/isf/member/survei/{id}/results — Tampilkan grafik hasil sementara khusus untuk member sebagai reward.
     */
    public function results(Request $request, $id): JsonResponse
    {
        $survey = $this->findSurvey($id);
        if (!$survey) {
            return response()->json(['message' => 'Survei tidak ditemukan.'], 404);
        }

        $user = $request->user();
        $response = $user
            ? SurveyResponse::where('survey_id', $survey->id)
                ->where('user_id', $user->id)
                ->first()
            : null;

        $completedCount = SurveyResponse::where('survey_id', $survey->id)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'survey' => [
                'id' => $survey->id,
                'title' => $survey->title,
                'slug' => $survey->slug,
                'institution' => $survey->institution,
                'headline_stat_value' => $survey->headline_stat_value,
                'headline_stat_label' => $survey->headline_stat_label,
                'summary' => $survey->summary,
                'expert_analysis' => $survey->expert_analysis,
                'chart_data' => $survey->chart_data,
                'sample_size' => $survey->sample_size,
            ],
            'user_response' => $response ? [
                'status' => $response->status,
                'completed_at' => $response->completed_at?->format('d M Y H:i'),
                'answers' => $response->answers,
            ] : null,
            'stats' => [
                'total_member_respondents' => $completedCount,
            ],
        ]);
    }

    protected function findSurvey($id): ?Survey
    {
        return Survey::query()
            ->where('is_member_active', true)
            ->where(function ($q) use ($id) {
                if (is_numeric($id)) {
                    $q->where('id', (int) $id);
                } else {
                    $q->where('slug', $id);
                }
            })
            ->first();
    }
}
