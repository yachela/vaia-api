<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $trip;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->trip = Trip::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_user_can_list_activities_of_their_trip(): void
    {
        Activity::factory()->create(['trip_id' => $this->trip->id]);
        Activity::factory()->create(); // Otro viaje

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/activities");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'description', 'date', 'time', 'location', 'cost'],
                ],
                'links', 'meta',
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_create_activity_for_their_trip(): void
    {
        $activityData = [
            'title' => 'Nueva Actividad',
            'description' => 'Descripción de la actividad',
            'date' => now()->addDays(1)->format('Y-m-d'),
            'time' => '14:00',
            'location' => 'Centro de la ciudad',
            'cost' => 50.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/activities", $activityData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'title', 'description', 'date', 'time', 'location', 'cost'],
            ]);

        $this->assertDatabaseHas('activities', [
            'title' => 'Nueva Actividad',
            'description' => 'Descripción de la actividad',
            'time' => '14:00',
            'location' => 'Centro de la ciudad',
            'cost' => 50,
            'trip_id' => $this->trip->id,
        ]);
    }

    public function test_user_can_view_activity_of_their_trip(): void
    {
        $activity = Activity::factory()->create(['trip_id' => $this->trip->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/activities/{$activity->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $activity->id,
                    'title' => $activity->title,
                ],
            ]);
    }

    public function test_user_cannot_view_activity_of_others_trip(): void
    {
        $otherTrip = Trip::factory()->create();
        $activity = Activity::factory()->create(['trip_id' => $otherTrip->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/activities/{$activity->id}");

        $response->assertStatus(404);
    }

    public function test_user_can_update_activity_of_their_trip(): void
    {
        $activity = Activity::factory()->create(['trip_id' => $this->trip->id]);
        $updateData = [
            'title' => 'Actividad Actualizada',
            'cost' => 75.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/trips/{$this->trip->id}/activities/{$activity->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'title' => 'Actividad Actualizada',
                    'cost' => 75.00,
                ],
            ]);

        $this->assertDatabaseHas('activities', array_merge($updateData, ['id' => $activity->id]));
    }

    public function test_user_can_delete_activity_of_their_trip(): void
    {
        $activity = Activity::factory()->create(['trip_id' => $this->trip->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/trips/{$this->trip->id}/activities/{$activity->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Actividad eliminada correctamente.']);

        $this->assertSoftDeleted('activities', ['id' => $activity->id]);
    }

    public function test_validation_errors_on_create_activity(): void
    {
        $invalidData = [
            'title' => '',
            'date' => 'invalid-date',
            'time' => '25:00',
            'cost' => 'not-a-number',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/activities", $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }
}
