<?php

namespace Tests\Unit;

use App\Models\PackingItem;
use App\Models\PackingList;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackingItemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property-Based Test: Toggle es idempotente
     * 
     * Propiedad: toggle(toggle(x)) == x
     * Verificar que aplicar toggle dos veces restaura estado original
     */
    public function test_toggle_is_idempotent(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $packingList = PackingList::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);

        // Probar con múltiples estados iniciales
        $initialStates = [true, false];

        foreach ($initialStates as $initialState) {
            $item = PackingItem::factory()->create([
                'packing_list_id' => $packingList->id,
                'is_packed' => $initialState,
            ]);

            $originalState = $item->is_packed;

            // Primer toggle
            $item->is_packed = !$item->is_packed;
            $item->save();
            $item->refresh();

            $afterFirstToggle = $item->is_packed;

            // Verificar que cambió
            $this->assertNotEquals(
                $originalState,
                $afterFirstToggle,
                "Primer toggle debe cambiar el estado"
            );

            // Segundo toggle
            $item->is_packed = !$item->is_packed;
            $item->save();
            $item->refresh();

            $afterSecondToggle = $item->is_packed;

            // Propiedad: toggle(toggle(x)) == x
            $this->assertEquals(
                $originalState,
                $afterSecondToggle,
                "Aplicar toggle dos veces debe restaurar el estado original (idempotencia)"
            );
        }
    }

    /**
     * Test: Toggle múltiple es idempotente (n veces)
     * 
     * Propiedad: toggle aplicado un número par de veces restaura el estado original
     */
    public function test_toggle_multiple_times_is_idempotent(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $packingList = PackingList::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);

        $item = PackingItem::factory()->create([
            'packing_list_id' => $packingList->id,
            'is_packed' => false,
        ]);

        $originalState = $item->is_packed;

        // Probar con diferentes números de toggles
        $toggleCounts = [2, 4, 6, 8, 10, 20, 50, 100];

        foreach ($toggleCounts as $count) {
            // Resetear al estado original
            $item->is_packed = $originalState;
            $item->save();

            // Aplicar toggle n veces
            for ($i = 0; $i < $count; $i++) {
                $item->is_packed = !$item->is_packed;
                $item->save();
                $item->refresh();
            }

            // Si el número de toggles es par, debe volver al estado original
            if ($count % 2 === 0) {
                $this->assertEquals(
                    $originalState,
                    $item->is_packed,
                    "Después de {$count} toggles (par), debe volver al estado original"
                );
            } else {
                $this->assertNotEquals(
                    $originalState,
                    $item->is_packed,
                    "Después de {$count} toggles (impar), debe estar en estado opuesto"
                );
            }
        }
    }

    /**
     * Test: Toggle preserva otros atributos
     * 
     * Propiedad: toggle solo afecta is_packed, no otros atributos
     */
    public function test_toggle_preserves_other_attributes(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $packingList = PackingList::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);

        $item = PackingItem::factory()->create([
            'packing_list_id' => $packingList->id,
            'name' => 'Test Item',
            'category' => 'Ropa',
            'is_packed' => false,
            'is_suggested' => true,
            'suggestion_reason' => 'Test reason',
        ]);

        // Guardar valores originales
        $originalName = $item->name;
        $originalCategory = $item->category;
        $originalIsSuggested = $item->is_suggested;
        $originalSuggestionReason = $item->suggestion_reason;
        $originalPackingListId = $item->packing_list_id;

        // Aplicar toggle
        $item->is_packed = !$item->is_packed;
        $item->save();
        $item->refresh();

        // Verificar que otros atributos no cambiaron
        $this->assertEquals($originalName, $item->name, "Nombre no debe cambiar");
        $this->assertEquals($originalCategory, $item->category, "Categoría no debe cambiar");
        $this->assertEquals($originalIsSuggested, $item->is_suggested, "is_suggested no debe cambiar");
        $this->assertEquals($originalSuggestionReason, $item->suggestion_reason, "suggestion_reason no debe cambiar");
        $this->assertEquals($originalPackingListId, $item->packing_list_id, "packing_list_id no debe cambiar");
    }

    /**
     * Test: Toggle es conmutativo con refresh
     * 
     * Propiedad: toggle -> refresh -> toggle == toggle -> toggle -> refresh
     */
    public function test_toggle_is_commutative_with_refresh(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $packingList = PackingList::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);

        // Crear dos ítems idénticos
        $item1 = PackingItem::factory()->create([
            'packing_list_id' => $packingList->id,
            'is_packed' => false,
        ]);

        $item2 = PackingItem::factory()->create([
            'packing_list_id' => $packingList->id,
            'is_packed' => false,
        ]);

        // Secuencia 1: toggle -> refresh -> toggle
        $item1->is_packed = !$item1->is_packed;
        $item1->save();
        $item1->refresh();
        $item1->is_packed = !$item1->is_packed;
        $item1->save();
        $item1->refresh();

        // Secuencia 2: toggle -> toggle -> refresh
        $item2->is_packed = !$item2->is_packed;
        $item2->save();
        $item2->is_packed = !$item2->is_packed;
        $item2->save();
        $item2->refresh();

        // Ambos deben tener el mismo estado final
        $this->assertEquals(
            $item1->is_packed,
            $item2->is_packed,
            "Ambas secuencias deben resultar en el mismo estado"
        );
    }

    /**
     * Test: Toggle es determinístico
     * 
     * Propiedad: Dado el mismo estado inicial, toggle siempre produce el mismo resultado
     */
    public function test_toggle_is_deterministic(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $packingList = PackingList::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);

        $initialStates = [true, false];
        $iterations = 10;

        foreach ($initialStates as $initialState) {
            $results = [];

            // Ejecutar toggle múltiples veces con el mismo estado inicial
            for ($i = 0; $i < $iterations; $i++) {
                $item = PackingItem::factory()->create([
                    'packing_list_id' => $packingList->id,
                    'is_packed' => $initialState,
                ]);

                $item->is_packed = !$item->is_packed;
                $item->save();
                $item->refresh();

                $results[] = $item->is_packed;
            }

            // Todos los resultados deben ser idénticos
            $uniqueResults = array_unique($results);
            $this->assertCount(
                1,
                $uniqueResults,
                "Toggle debe ser determinístico: mismo input -> mismo output"
            );

            // El resultado debe ser el opuesto del estado inicial
            $this->assertEquals(
                !$initialState,
                $results[0],
                "Toggle debe invertir el estado"
            );
        }
    }
}

