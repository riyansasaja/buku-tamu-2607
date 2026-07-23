<?php

namespace Tests\Feature;

use App\Enums\VisitStatus;
use App\Models\Visit;
use App\Models\VisitDecisionToken;
use App\Services\DecisionLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DecisionPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_pending_visit_can_issue_one_time_secret_link_and_show_private_detail(): void
    {
        $visit = Visit::factory()->create(['guest_name' => 'Maria Manado']);
        Storage::disk('local')->put($visit->photo_path, 'image-content');

        $issued = app(DecisionLinkService::class)->issue($visit);
        $rawHash = VisitDecisionToken::query()->sole()->getRawOriginal('token_hash');

        $this->assertSame(hash('sha256', $issued->plainToken), $rawHash);
        $this->assertStringNotContainsString($issued->plainToken, json_encode($issued->decisionToken->toArray(), JSON_THROW_ON_ERROR));

        $response = $this->get($issued->url);
        $response->assertOk()
            ->assertSee('Maria Manado')
            ->assertSee($visit->address)
            ->assertSee($visit->visit_purpose)
            ->assertSee($visit->employee->name)
            ->assertDontSee($visit->photo_path)
            ->assertDontSee($visit->guest_whatsapp)
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Request-ID');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_invalid_revoked_used_and_decided_links_have_same_unavailable_page(): void
    {
        $visit = Visit::factory()->create();
        $issued = app(DecisionLinkService::class)->issue($visit);

        $invalid = $this->get('/decisions/'.str_repeat('A', 64))->assertNotFound();
        $issued->decisionToken->update(['revoked_at' => now()]);
        $revoked = $this->get($issued->url)->assertNotFound();
        $issued->decisionToken->update(['revoked_at' => null, 'used_at' => now()]);
        $used = $this->get($issued->url)->assertNotFound();
        $issued->decisionToken->update(['used_at' => null]);
        $visit->update(['status' => VisitStatus::Accepted]);
        $decided = $this->get($issued->url)->assertNotFound();

        foreach ([$invalid, $revoked, $used, $decided] as $response) {
            $response->assertSee('Tautan tidak tersedia')->assertDontSee($visit->guest_name);
        }
    }

    public function test_token_is_bound_to_exact_visit_and_reissuing_revokes_previous_link(): void
    {
        $firstVisit = Visit::factory()->create(['guest_name' => 'Tamu Pertama']);
        $secondVisit = Visit::factory()->create(['guest_name' => 'Tamu Kedua']);
        $links = app(DecisionLinkService::class);
        $old = $links->issue($firstVisit);
        $second = $links->issue($secondVisit);
        $replacement = $links->issue($firstVisit);

        $this->get($old->url)->assertNotFound()->assertDontSee('Tamu Pertama');
        $this->get($replacement->url)->assertOk()->assertSee('Tamu Pertama')->assertDontSee('Tamu Kedua');
        $this->get($second->url)->assertOk()->assertSee('Tamu Kedua')->assertDontSee('Tamu Pertama');
        $this->assertDatabaseCount('visit_decision_tokens', 2);
    }

    public function test_non_pending_visit_cannot_issue_decision_link(): void
    {
        $visit = Visit::factory()->create(['status' => VisitStatus::Rejected]);

        $this->expectException(\DomainException::class);
        app(DecisionLinkService::class)->issue($visit);
    }

    public function test_decision_page_is_rate_limited_without_exposing_guest_data(): void
    {
        config(['api.rate_limits.decision_pages' => 2]);
        $visit = Visit::factory()->create(['guest_name' => 'Nama Sangat Rahasia']);
        $issued = app(DecisionLinkService::class)->issue($visit);

        $this->get($issued->url)->assertOk();
        $this->get($issued->url)->assertOk();
        $this->get($issued->url)
            ->assertStatus(429)
            ->assertSee('Tautan tidak tersedia')
            ->assertDontSee('Nama Sangat Rahasia')
            ->assertHeader('X-Request-ID');
    }
}
