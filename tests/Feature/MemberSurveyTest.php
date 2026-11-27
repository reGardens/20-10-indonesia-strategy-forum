<?php

namespace Tests\Feature;

use App\Models\Isf\Survey;
use App\Models\Isf\SurveyResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberSurveyTest extends TestCase
{
    use DatabaseTransactions;

    protected function createUser(): User
    {
        return User::create([
            'name' => 'Member Test ISF',
            'email' => 'member-test-' . uniqid() . '@example.test',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_member_survey_page(): void
    {
        $response = $this->get('/member/survei');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_member_survey_catalog_api(): void
    {
        $user = $this->createUser();

        $survey = Survey::create([
            'title' => 'Survei Evaluasi Kebijakan Publik',
            'slug' => 'survei-evaluasi-kebijakan-publik-' . uniqid(),
            'institution' => 'ISF Research',
            'is_isf' => true,
            'topic' => 'politik',
            'region' => 'nasional',
            'status' => 'terbit',
            'is_member_active' => true,
            'estimated_minutes' => 5,
            'questions' => [
                [
                    'id' => 'q1',
                    'type' => 'scale',
                    'question' => 'Tingkat kepuasan kebijakan',
                    'required' => true,
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('/api/isf/member/survei');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'slug',
                    'member_status',
                    'progress',
                ],
            ],
        ]);

        $matched = collect($response->json('data'))->firstWhere('id', $survey->id);
        $this->assertNotNull($matched);
        $this->assertEquals('belum_diisi', $matched['member_status']);
    }

    public function test_member_can_save_survey_draft(): void
    {
        $user = $this->createUser();

        $survey = Survey::create([
            'title' => 'Survei Draft Test',
            'slug' => 'survei-draft-test-' . uniqid(),
            'institution' => 'ISF Research',
            'is_isf' => true,
            'status' => 'terbit',
            'is_member_active' => true,
            'questions' => [
                ['id' => 'q1', 'type' => 'scale', 'question' => 'Soal 1', 'required' => true],
            ],
        ]);

        $payload = [
            'answers' => ['q1' => 8],
            'demographics' => ['age' => '28-43', 'domicile' => 'DKI Jakarta', 'profession' => 'Profesional Swasta'],
            'current_step' => 1,
        ];

        $response = $this->actingAs($user)->postJson("/api/isf/member/survei/{$survey->id}/draft", $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('isf_survey_responses', [
            'survey_id' => $survey->id,
            'user_id' => $user->id,
            'status' => 'draft',
        ]);
    }

    public function test_member_can_submit_survey_response(): void
    {
        $user = $this->createUser();

        $survey = Survey::create([
            'title' => 'Survei Submit Test',
            'slug' => 'survei-submit-test-' . uniqid(),
            'institution' => 'ISF Research',
            'is_isf' => true,
            'status' => 'terbit',
            'is_member_active' => true,
            'questions' => [
                ['id' => 'q1', 'type' => 'scale', 'question' => 'Soal 1', 'required' => true],
            ],
        ]);

        $payload = [
            'answers' => ['q1' => 9],
            'demographics' => [
                'age' => '28-43',
                'domicile' => 'DKI Jakarta',
                'profession' => 'Akademisi / Peneliti / Dosen',
            ],
        ];

        $response = $this->actingAs($user)->postJson("/api/isf/member/survei/{$survey->id}/submit", $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('isf_survey_responses', [
            'survey_id' => $survey->id,
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
    }

    public function test_double_voting_prevention_rejects_second_submission(): void
    {
        $user = $this->createUser();

        $survey = Survey::create([
            'title' => 'Survei Anti Double Voting',
            'slug' => 'survei-anti-double-voting-' . uniqid(),
            'institution' => 'ISF Research',
            'is_isf' => true,
            'status' => 'terbit',
            'is_member_active' => true,
            'questions' => [
                ['id' => 'q1', 'type' => 'scale', 'question' => 'Soal 1', 'required' => true],
            ],
        ]);

        // Respon pertama berhasil
        SurveyResponse::create([
            'survey_id' => $survey->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'answers' => ['q1' => 10],
            'demographics' => ['age' => '28-43', 'domicile' => 'Jawa Barat', 'profession' => 'Wirausaha'],
            'completed_at' => now(),
        ]);

        // Upaya kirim kedua harus ditolak (error 422)
        $payload = [
            'answers' => ['q1' => 5],
            'demographics' => ['age' => '28-43', 'domicile' => 'Jawa Barat', 'profession' => 'Wirausaha'],
        ];

        $response = $this->actingAs($user)->postJson("/api/isf/member/survei/{$survey->id}/submit", $payload);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Anda sudah pernah mengisi survei ini. Satu akun hanya dapat memberikan satu suara.',
        ]);
    }

    public function test_member_can_view_survey_results_reward(): void
    {
        $user = $this->createUser();

        $survey = Survey::create([
            'title' => 'Survei Hasil & Reward',
            'slug' => 'survei-hasil-reward-' . uniqid(),
            'institution' => 'ISF Research',
            'is_isf' => true,
            'status' => 'terbit',
            'is_member_active' => true,
            'headline_stat_value' => '84.5%',
            'headline_stat_label' => 'Tingkat Kepercayaan Publik',
            'chart_data' => [
                'segments' => [
                    [
                        'id' => 'overall',
                        'name' => 'Nasional',
                        'type' => 'bar',
                        'items' => [['label' => 'Setuju', 'value' => 84.5]],
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson("/api/isf/member/survei/{$survey->id}/results");

        $response->assertOk();
        $response->assertJsonPath('survey.headline_stat_value', '84.5%');
    }
}
