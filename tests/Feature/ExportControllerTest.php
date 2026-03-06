<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ── Itinerary PDF ──────────────────────────────────────────────────────────

    public function test_owner_can_download_itinerary_pdf(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        Activity::factory()->count(3)->create(['trip_id' => $trip->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get("/api/trips/{$trip->id}/export/itinerary.pdf");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString(
            'itinerario-',
            $response->headers->get('Content-Disposition') ?? ''
        );
    }

    public function test_itinerary_pdf_works_for_trip_without_activities(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get("/api/trips/{$trip->id}/export/itinerary.pdf");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_other_user_cannot_download_itinerary_pdf(): void
    {
        $other = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get("/api/trips/{$trip->id}/export/itinerary.pdf");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_download_itinerary_pdf(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $this->withHeaders(['Accept' => 'application/json'])
            ->get("/api/trips/{$trip->id}/export/itinerary.pdf")
            ->assertStatus(401);
    }

    // ── Expenses CSV ───────────────────────────────────────────────────────────

    public function test_owner_can_download_expenses_csv(): void
    {
        $trip = Trip::factory()->create([
            'user_id' => $this->user->id,
            'budget' => 1000,
        ]);
        Expense::factory()->count(3)->create([
            'trip_id' => $trip->id,
            'amount' => 100,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get("/api/trips/{$trip->id}/export/expenses.csv");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Fecha', $content);
        $this->assertStringContainsString('TOTAL', $content);
        $this->assertStringContainsString('PRESUPUESTO', $content);
        $this->assertStringContainsString('1000.00', $content);
    }

    public function test_csv_contains_expense_rows(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        Expense::factory()->create([
            'trip_id' => $trip->id,
            'description' => 'Vuelo Madrid',
            'amount' => 350.50,
            'category' => 'Transporte',
            'date' => '2026-06-01',
        ]);

        $content = $this->actingAs($this->user, 'sanctum')
            ->get("/api/trips/{$trip->id}/export/expenses.csv")
            ->streamedContent();

        $this->assertStringContainsString('Vuelo Madrid', $content);
        $this->assertStringContainsString('350.50', $content);
        $this->assertStringContainsString('Transporte', $content);
        $this->assertStringContainsString('2026-06-01', $content);
    }

    public function test_csv_works_for_trip_without_expenses(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id, 'budget' => 500]);

        $content = $this->actingAs($this->user, 'sanctum')
            ->get("/api/trips/{$trip->id}/export/expenses.csv")
            ->streamedContent();

        $this->assertStringContainsString('Fecha', $content);
        $this->assertStringContainsString('0.00', $content);
        $this->assertStringContainsString('500.00', $content);
    }

    public function test_other_user_cannot_download_expenses_csv(): void
    {
        $other = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $other->id]);

        $this->actingAs($this->user, 'sanctum')
            ->get("/api/trips/{$trip->id}/export/expenses.csv")
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_download_expenses_csv(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $this->withHeaders(['Accept' => 'application/json'])
            ->get("/api/trips/{$trip->id}/export/expenses.csv")
            ->assertStatus(401);
    }
}
