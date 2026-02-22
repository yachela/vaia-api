<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_trip_has_uuid(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);

        $this->assertNotNull($trip->id);
        $this->assertIsString($trip->id);
    }

    public function test_trip_fillable_attributes(): void
    {
        $user = User::factory()->create();
        $tripData = [
            'user_id' => $user->id,
            'title' => 'Test Trip',
            'destination' => 'Test Destination',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-05',
            'budget' => 1000.00,
        ];

        $trip = Trip::create($tripData);

        $this->assertEquals($tripData['title'], $trip->title);
        $this->assertEquals($tripData['destination'], $trip->destination);
        $this->assertEquals($tripData['budget'], $trip->budget);
    }

    public function test_trip_casts(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-05',
            'budget' => 1000.50,
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $trip->start_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $trip->end_date);
        $this->assertEquals('1000.50', $trip->budget);
    }

    public function test_trip_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $trip->user);
        $this->assertEquals($user->id, $trip->user->id);
    }

    public function test_trip_has_many_activities(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $activity = Activity::factory()->create(['trip_id' => $trip->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $trip->activities);
        $this->assertCount(1, $trip->activities);
        $this->assertEquals($activity->id, $trip->activities->first()->id);
    }

    public function test_trip_has_many_expenses(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $expense = Expense::factory()->create(['trip_id' => $trip->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $trip->expenses);
        $this->assertCount(1, $trip->expenses);
        $this->assertEquals($expense->id, $trip->expenses->first()->id);
    }

    public function test_trip_total_expenses_accessor(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        Expense::factory()->create(['trip_id' => $trip->id, 'amount' => 100.00]);
        Expense::factory()->create(['trip_id' => $trip->id, 'amount' => 50.00]);

        $this->assertEquals(150.00, $trip->total_expenses);
    }

    public function test_trip_upcoming_scope(): void
    {
        $user = User::factory()->create();
        $pastTrip = Trip::factory()->create([
            'user_id' => $user->id,
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDays(1),
        ]);
        $upcomingTrip = Trip::factory()->create([
            'user_id' => $user->id,
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(10),
        ]);

        $upcomingTrips = Trip::upcoming()->get();

        $this->assertCount(1, $upcomingTrips);
        $this->assertEquals($upcomingTrip->id, $upcomingTrips->first()->id);
    }
}
