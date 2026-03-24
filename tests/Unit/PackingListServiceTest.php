<?php

namespace Tests\Unit;

use App\Models\PackingList;
use App\Models\Trip;
use App\Models\User;
use App\Services\PackingListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackingListServiceTest extends TestCase
{
    use RefreshDatabase;

    private PackingListService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PackingListService();
    }

    /**
     * Property-Based Test: Lista base contiene todas las categorías
     * 
     * Propiedad: ∀ viaje con duración 1-365 días, 
     * lista generada contiene ≥1 ítem por categoría
     */
    public function test_base_list_contains_all_categories_for_any_duration(): void
    {
        $user = User::factory()->create();
        $expectedCategories = ['Documentación', 'Higiene', 'Ropa', 'Tecnología'];
        
        // Generar datos aleatorios: duraciones de 1 a 365 días
        $testCases = [
            1,    // Viaje de 1 día
            3,    // Viaje corto
            7,    // Viaje de una semana
            8,    // Justo después del umbral de 7 días
            14,   // Dos semanas
            30,   // Un mes
            90,   // Tres meses
            180,  // Medio año
            365,  // Un año
        ];

        // Agregar algunos casos aleatorios
        for ($i = 0; $i < 10; $i++) {
            $testCases[] = rand(1, 365);
        }

        foreach ($testCases as $duration) {
            $startDate = now();
            $endDate = now()->addDays($duration - 1);

            $trip = Trip::factory()->create([
                'user_id' => $user->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);

            // Generar lista base
            $packingList = $this->service->generateBaseList($trip);

            // Verificar que existe la lista
            $this->assertInstanceOf(PackingList::class, $packingList);
            $this->assertEquals($trip->id, $packingList->trip_id);

            // Obtener categorías únicas de los ítems generados
            $generatedCategories = $packingList->items()
                ->pluck('category')
                ->unique()
                ->toArray();

            // Propiedad: Todas las categorías esperadas deben estar presentes
            foreach ($expectedCategories as $category) {
                $this->assertContains(
                    $category,
                    $generatedCategories,
                    "Lista para viaje de {$duration} días debe contener categoría '{$category}'"
                );
            }

            // Verificar que cada categoría tiene al menos 1 ítem
            foreach ($expectedCategories as $category) {
                $itemsInCategory = $packingList->items()
                    ->where('category', $category)
                    ->count();

                $this->assertGreaterThanOrEqual(
                    1,
                    $itemsInCategory,
                    "Categoría '{$category}' debe tener al menos 1 ítem para viaje de {$duration} días"
                );
            }
        }
    }

    /**
     * Property-Based Test: Número de ítems ≥ número de categorías
     * 
     * Propiedad metamórfica: count(items) >= count(categories)
     */
    public function test_item_count_is_greater_than_or_equal_to_category_count(): void
    {
        $user = User::factory()->create();
        $expectedCategoryCount = 4; // Documentación, Higiene, Ropa, Tecnología

        // Probar con diferentes duraciones
        $durations = [1, 5, 7, 8, 15, 30, 60, 100, 200, 365];

        foreach ($durations as $duration) {
            $trip = Trip::factory()->create([
                'user_id' => $user->id,
                'start_date' => now(),
                'end_date' => now()->addDays($duration - 1),
            ]);

            $packingList = $this->service->generateBaseList($trip);

            $totalItems = $packingList->items()->count();
            $uniqueCategories = $packingList->items()
                ->pluck('category')
                ->unique()
                ->count();

            // Propiedad: Número de ítems debe ser >= número de categorías
            $this->assertGreaterThanOrEqual(
                $uniqueCategories,
                $totalItems,
                "Total de ítems ({$totalItems}) debe ser >= categorías únicas ({$uniqueCategories}) para duración {$duration} días"
            );

            // Verificar que tenemos exactamente 4 categorías
            $this->assertEquals(
                $expectedCategoryCount,
                $uniqueCategories,
                "Debe haber exactamente {$expectedCategoryCount} categorías para duración {$duration} días"
            );

            // Propiedad adicional: Más ítems para viajes largos
            if ($duration > 7) {
                $shortTrip = Trip::factory()->create([
                    'user_id' => $user->id,
                    'start_date' => now(),
                    'end_date' => now()->addDays(3),
                ]);

                $shortPackingList = $this->service->generateBaseList($shortTrip);
                $shortItemCount = $shortPackingList->items()->count();

                $this->assertGreaterThanOrEqual(
                    $shortItemCount,
                    $totalItems,
                    "Viaje largo ({$duration} días) debe tener >= ítems que viaje corto (4 días)"
                );
            }
        }
    }

    /**
     * Test adicional: Viajes largos tienen más ítems que viajes cortos
     */
    public function test_long_trips_have_more_items_than_short_trips(): void
    {
        $user = User::factory()->create();

        // Viaje corto (≤7 días)
        $shortTrip = Trip::factory()->create([
            'user_id' => $user->id,
            'start_date' => now(),
            'end_date' => now()->addDays(5),
        ]);

        // Viaje largo (>7 días)
        $longTrip = Trip::factory()->create([
            'user_id' => $user->id,
            'start_date' => now(),
            'end_date' => now()->addDays(15),
        ]);

        $shortPackingList = $this->service->generateBaseList($shortTrip);
        $longPackingList = $this->service->generateBaseList($longTrip);

        $shortItemCount = $shortPackingList->items()->count();
        $longItemCount = $longPackingList->items()->count();

        $this->assertGreaterThan(
            $shortItemCount,
            $longItemCount,
            "Viaje largo debe tener más ítems que viaje corto"
        );
    }

    /**
     * Test: Todos los ítems generados tienen valores por defecto correctos
     */
    public function test_generated_items_have_correct_default_values(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'start_date' => now(),
            'end_date' => now()->addDays(10),
        ]);

        $packingList = $this->service->generateBaseList($trip);

        foreach ($packingList->items as $item) {
            // Todos los ítems base deben estar sin empacar
            $this->assertFalse($item->is_packed, "Ítem '{$item->name}' debe estar sin empacar por defecto");
            
            // Todos los ítems base no son sugeridos (no vienen del clima)
            $this->assertFalse($item->is_suggested, "Ítem '{$item->name}' no debe ser sugerido por defecto");
            
            // Todos los ítems deben tener nombre
            $this->assertNotEmpty($item->name, "Ítem debe tener nombre");
            
            // Todos los ítems deben tener categoría válida
            $this->assertContains(
                $item->category,
                ['Documentación', 'Higiene', 'Ropa', 'Tecnología'],
                "Categoría '{$item->category}' debe ser válida"
            );
        }
    }
}

