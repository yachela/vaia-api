<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_list_their_trips(): void
    {
        Trip::factory()->create(['user_id' => $this->user->id]);
        Trip::factory()->create(); // Otro usuario

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/trips');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'title', 'destination', 'start_date', 'end_date', 'budget',
                        'total_expenses', 'activities_count', 'expenses_count',
                    ],
                ],
                'links', 'meta',
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_create_trip(): void
    {
        $tripData = [
            'title' => 'Nuevo Viaje',
            'destination' => 'Madrid, España',
            'start_date' => now()->addDays(10)->format('Y-m-d'),
            'end_date' => now()->addDays(15)->format('Y-m-d'),
            'budget' => 1500.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/trips', $tripData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'title', 'destination', 'start_date', 'end_date', 'budget'],
            ]);

        $this->assertDatabaseHas('trips', [
            'title' => 'Nuevo Viaje',
            'destination' => 'Madrid, España',
            'budget' => 1500,
            'user_id' => (string) $this->user->id,
        ]);
    }

    public function test_user_can_view_their_trip(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        Activity::factory()->create(['trip_id' => $trip->id]);
        Expense::factory()->create(['trip_id' => $trip->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$trip->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'destination', 'start_date', 'end_date', 'budget',
                    'total_expenses', 'activities', 'expenses',
                ],
            ]);
    }

    public function test_user_cannot_view_others_trip(): void
    {
        $otherUser = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$trip->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_update_their_trip(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $updateData = [
            'title' => 'Viaje Actualizado',
            'budget' => 2000.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/trips/{$trip->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Viaje Actualizado',
                    'budget' => 2000.00,
                ],
            ]);

        $this->assertDatabaseHas('trips', array_merge($updateData, ['id' => $trip->id]));
    }

    public function test_user_can_delete_their_trip(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/trips/{$trip->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Viaje eliminado correctamente.']);

        $this->assertSoftDeleted('trips', ['id' => $trip->id]);
    }

    public function test_unauthenticated_user_cannot_access_trips(): void
    {
        $response = $this->getJson('/api/trips');

        $response->assertStatus(401);
    }

    public function test_validation_errors_on_create(): void
    {
        $invalidData = [
            'title' => '',
            'destination' => '',
            'start_date' => 'invalid-date',
            'end_date' => now()->subDays(1)->format('Y-m-d'),
            'budget' => -100,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/trips', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }
}
