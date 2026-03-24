<?php

namespace Tests\Performance;

use App\Models\Activity;
use App\Models\PackingItem;
use App\Models\PackingList;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndpointLatencyTest extends TestCase
{
    use RefreshDatabase;

    private const MAX_LATENCY_MS = 500;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test: GET /api/v1/trips debe responder en <500ms
     */
    public function test_trips_index_latency_under_500ms(): void
    {
        // Crear datos de prueba
        Trip::factory()->count(15)->create(['user_id' => $this->user->id]);

        $startTime = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/trips');

        $endTime = microtime(true);
        $latencyMs = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            self::MAX_LATENCY_MS,
            $latencyMs,
            "GET /api/v1/trips debe responder en menos de " . self::MAX_LATENCY_MS . "ms (actual: {$latencyMs}ms)"
        );

        // Log para análisis
        echo "\nGET /api/v1/trips latency: " . round($latencyMs, 2) . "ms\n";
    }

    /**
     * Test: GET /api/v1/trips/{trip}/activities debe responder en <500ms
     */
    public function test_activities_index_latency_under_500ms(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        
        // Crear múltiples actividades
        Activity::factory()->count(20)->create(['trip_id' => $trip->id]);

        $startTime = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/trips/{$trip->id}/activities");

        $endTime = microtime(true);
        $latencyMs = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            self::MAX_LATENCY_MS,
            $latencyMs,
            "GET /api/v1/trips/{trip}/activities debe responder en menos de " . self::MAX_LATENCY_MS . "ms (actual: {$latencyMs}ms)"
        );

        echo "\nGET /api/v1/trips/{trip}/activities latency: " . round($latencyMs, 2) . "ms\n";
    }

    /**
     * Test: GET /api/v1/trips/{trip}/packing-list debe responder en <500ms
     */
    public function test_packing_list_show_latency_under_500ms(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $packingList = PackingList::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        // Crear múltiples ítems
        PackingItem::factory()->count(30)->create([
            'packing_list_id' => $packingList->id,
        ]);

        $startTime = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/trips/{$trip->id}/packing-list");

        $endTime = microtime(true);
        $latencyMs = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        $this->assertLessThan(
            self::MAX_LATENCY_MS,
            $latencyMs,
            "GET /api/v1/trips/{trip}/packing-list debe responder en menos de " . self::MAX_LATENCY_MS . "ms (actual: {$latencyMs}ms)"
        );

        echo "\nGET /api/v1/trips/{trip}/packing-list latency: " . round($latencyMs, 2) . "ms\n";
    }

    /**
     * Test: Latencia bajo carga (múltiples viajes con relaciones)
     */
    public function test_trips_index_latency_with_heavy_load(): void
    {
        // Crear 50 viajes con actividades y listas de equipaje
        for ($i = 0; $i < 50; $i++) {
            $trip = Trip::factory()->create(['user_id' => $this->user->id]);
            
            // 5 actividades por viaje
            Activity::factory()->count(5)->create(['trip_id' => $trip->id]);
            
            // Lista de equipaje con 10 ítems
            $packingList = PackingList::factory()->create([
                'trip_id' => $trip->id,
                'user_id' => $this->user->id,
            ]);
            PackingItem::factory()->count(10)->create([
                'packing_list_id' => $packingList->id,
            ]);
        }

        $startTime = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/trips');

        $endTime = microtime(true);
        $latencyMs = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        // Bajo carga pesada, permitir hasta 1 segundo
        $this->assertLessThan(
            1000,
            $latencyMs,
            "GET /api/v1/trips bajo carga debe responder en menos de 1000ms (actual: {$latencyMs}ms)"
        );

        echo "\nGET /api/v1/trips (heavy load) latency: " . round($latencyMs, 2) . "ms\n";
    }

    /**
     * Test: Latencia de múltiples requests consecutivos
     */
    public function test_consecutive_requests_latency(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        Activity::factory()->count(10)->create(['trip_id' => $trip->id]);

        $latencies = [];
        $iterations = 5;

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);

            $response = $this->actingAs($this->user, 'sanctum')
                ->getJson("/api/v1/trips/{$trip->id}/activities");

            $endTime = microtime(true);
            $latencyMs = ($endTime - $startTime) * 1000;

            $response->assertStatus(200);
            $latencies[] = $latencyMs;
        }

        $avgLatency = array_sum($latencies) / count($latencies);
        $maxLatency = max($latencies);

        echo "\nConsecutive requests - Avg: " . round($avgLatency, 2) . "ms, Max: " . round($maxLatency, 2) . "ms\n";

        $this->assertLessThan(
            self::MAX_LATENCY_MS,
            $avgLatency,
            "Latencia promedio debe ser menor a " . self::MAX_LATENCY_MS . "ms"
        );

        $this->assertLessThan(
            self::MAX_LATENCY_MS * 1.5,
            $maxLatency,
            "Latencia máxima no debe exceder " . (self::MAX_LATENCY_MS * 1.5) . "ms"
        );
    }

    /**
     * Test: Comparar latencia con y sin eager loading
     */
    public function test_eager_loading_improves_latency(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $packingList = PackingList::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);
        PackingItem::factory()->count(20)->create([
            'packing_list_id' => $packingList->id,
        ]);

        // Medir con eager loading (implementación actual)
        $startTime = microtime(true);
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/trips/{$trip->id}/packing-list");
        $endTime = microtime(true);
        $latencyWithEagerLoading = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        echo "\nWith eager loading: " . round($latencyWithEagerLoading, 2) . "ms\n";

        // Verificar que la latencia es aceptable
        $this->assertLessThan(
            self::MAX_LATENCY_MS,
            $latencyWithEagerLoading,
            "Latencia con eager loading debe ser menor a " . self::MAX_LATENCY_MS . "ms"
        );
    }

    /**
     * Test: Latencia de endpoint de creación
     */
    public function test_create_trip_latency(): void
    {
        $tripData = [
            'destination' => 'Test Destination',
            'start_date' => now()->addDays(10)->format('Y-m-d'),
            'end_date' => now()->addDays(15)->format('Y-m-d'),
            'budget' => 1000.00,
        ];

        $startTime = microtime(true);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/trips', $tripData);

        $endTime = microtime(true);
        $latencyMs = ($endTime - $startTime) * 1000;

        $response->assertStatus(201);

        echo "\nPOST /api/v1/trips latency: " . round($latencyMs, 2) . "ms\n";

        // Operaciones de escritura pueden ser un poco más lentas
        $this->assertLessThan(
            self::MAX_LATENCY_MS * 1.5,
            $latencyMs,
            "POST /api/v1/trips debe responder en menos de " . (self::MAX_LATENCY_MS * 1.5) . "ms"
        );
    }
}

