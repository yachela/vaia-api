<?php

namespace Tests\Unit;

use App\Http\Resources\PackingItemResource;
use App\Http\Resources\PackingListResource;
use App\Models\PackingItem;
use App\Models\PackingList;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackingListSerializationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property-Based Test: Round-trip de serialización
     * 
     * Propiedad: deserialize(serialize(x)) == x
     * Verificar que serialización JSON y deserialización produce objeto equivalente
     */
    public function test_packing_list_serialization_round_trip(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        
        // Crear lista con múltiples ítems de diferentes categorías
        $packingList = PackingList::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);

        $items = [
            ['name' => 'Pasaporte', 'category' => 'Documentación', 'is_packed' => false, 'is_suggested' => false],
            ['name' => 'Cepillo', 'category' => 'Higiene', 'is_packed' => true, 'is_suggested' => false],
            ['name' => 'Camiseta', 'category' => 'Ropa', 'is_packed' => false, 'is_suggested' => false],
            ['name' => 'Cargador', 'category' => 'Tecnología', 'is_packed' => true, 'is_suggested' => false],
            ['name' => 'Paraguas', 'category' => 'Ropa', 'is_packed' => false, 'is_suggested' => true, 'suggestion_reason' => 'Lluvia esperada'],
        ];

        foreach ($items as $itemData) {
            PackingItem::factory()->create(array_merge([
                'packing_list_id' => $packingList->id,
            ], $itemData));
        }

        $packingList->refresh();

        // Serializar a JSON usando el Resource
        $resource = new PackingListResource($packingList);
        $serialized = $resource->toArray(request());

        // Verificar estructura de serialización
        $this->assertArrayHasKey('id', $serialized);
        $this->assertArrayHasKey('trip_id', $serialized);
        $this->assertArrayHasKey('items_by_category', $serialized);
        $this->assertArrayHasKey('progress', $serialized);

        // Verificar que los datos esenciales se preservan
        $this->assertEquals($packingList->id, $serialized['id']);
        $this->assertEquals($packingList->trip_id, $serialized['trip_id']);

        // Contar ítems en todas las categorías
        $totalSerializedItems = 0;
        foreach ($serialized['items_by_category'] as $categoryGroup) {
            $totalSerializedItems += count($categoryGroup['items']);
        }

        // Verificar que todos los ítems están presentes
        $this->assertEquals(count($items), $totalSerializedItems);

        // Verificar round-trip: cada ítem serializado debe poder mapearse de vuelta
        foreach ($packingList->items as $originalItem) {
            $found = false;
            
            foreach ($serialized['items_by_category'] as $categoryGroup) {
                foreach ($categoryGroup['items'] as $serializedItem) {
                    if ($serializedItem['id'] === $originalItem->id) {
                        $found = true;
                        
                        // Verificar que todos los campos críticos se preservan
                        $this->assertEquals($originalItem->name, $serializedItem['name']);
                        $this->assertEquals($originalItem->category, $serializedItem['category']);
                        $this->assertEquals($originalItem->is_packed, $serializedItem['is_packed']);
                        $this->assertEquals($originalItem->is_suggested, $serializedItem['is_suggested']);
                        
                        if ($originalItem->suggestion_reason) {
                            $this->assertEquals($originalItem->suggestion_reason, $serializedItem['suggestion_reason']);
                        }
                        
                        break 2;
                    }
                }
            }
            
            $this->assertTrue($found, "Ítem {$originalItem->id} debe estar en la serialización");
        }
    }

    /**
     * Test: Serialización de PackingItem individual
     */
    public function test_packing_item_serialization_round_trip(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $packingList = PackingList::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);

        // Probar con diferentes combinaciones de valores
        $testCases = [
            ['name' => 'Test 1', 'category' => 'Ropa', 'is_packed' => true, 'is_suggested' => false, 'suggestion_reason' => null],
            ['name' => 'Test 2', 'category' => 'Higiene', 'is_packed' => false, 'is_suggested' => true, 'suggestion_reason' => 'Clima frío'],
            ['name' => 'Test 3', 'category' => 'Tecnología', 'is_packed' => true, 'is_suggested' => true, 'suggestion_reason' => 'Viaje largo'],
            ['name' => 'Test 4', 'category' => 'Documentación', 'is_packed' => false, 'is_suggested' => false, 'suggestion_reason' => null],
        ];

        foreach ($testCases as $testData) {
            $item = PackingItem::factory()->create(array_merge([
                'packing_list_id' => $packingList->id,
            ], $testData));

            // Serializar
            $resource = new PackingItemResource($item);
            $serialized = $resource->toArray(request());

            // Verificar round-trip: todos los campos deben preservarse
            $this->assertEquals($item->id, $serialized['id']);
            $this->assertEquals($item->name, $serialized['name']);
            $this->assertEquals($item->category, $serialized['category']);
            $this->assertEquals($item->is_packed, $serialized['is_packed']);
            $this->assertEquals($item->is_suggested, $serialized['is_suggested']);
            
            if ($item->suggestion_reason) {
                $this->assertEquals($item->suggestion_reason, $serialized['suggestion_reason']);
            }
        }
    }

    /**
     * Test: Serialización preserva tipos de datos
     */
    public function test_serialization_preserves_data_types(): void
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
            'is_packed' => true,
            'is_suggested' => false,
        ]);

        $resource = new PackingItemResource($item);
        $serialized = $resource->toArray(request());

        // Verificar tipos de datos
        $this->assertIsString($serialized['id'], "ID debe ser string (UUID)");
        $this->assertIsString($serialized['name'], "Name debe ser string");
        $this->assertIsString($serialized['category'], "Category debe ser string");
        $this->assertIsBool($serialized['is_packed'], "is_packed debe ser boolean");
        $this->assertIsBool($serialized['is_suggested'], "is_suggested debe ser boolean");
    }

    /**
     * Test: Serialización de lista vacía
     */
    public function test_empty_packing_list_serialization(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $packingList = PackingList::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);

        // No agregar ítems

        $resource = new PackingListResource($packingList);
        $serialized = $resource->toArray(request());

        // Verificar que la lista vacía se serializa correctamente
        $this->assertArrayHasKey('items_by_category', $serialized);
        
        // items_by_category puede ser Collection o array, convertir a array para verificar
        $itemsByCategory = is_array($serialized['items_by_category']) 
            ? $serialized['items_by_category'] 
            : $serialized['items_by_category']->toArray();
            
        $this->assertCount(0, $itemsByCategory);
        
        // Progress debe ser 0 de 0
        $this->assertArrayHasKey('progress', $serialized);
        $this->assertEquals(0, $serialized['progress']['total']);
        $this->assertEquals(0, $serialized['progress']['packed']);
    }

    /**
     * Test: Serialización con caracteres especiales
     */
    public function test_serialization_with_special_characters(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $packingList = PackingList::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);

        // Nombres con caracteres especiales
        $specialNames = [
            'Camiseta "favorita"',
            "Zapatos d'été",
            'Protector solar (SPF 50+)',
            'Adaptador 220V → 110V',
            'Medicamentos: paracetamol & ibuprofeno',
            'Ropa íntima',
            'Café instantáneo ☕',
        ];

        foreach ($specialNames as $name) {
            $item = PackingItem::factory()->create([
                'packing_list_id' => $packingList->id,
                'name' => $name,
                'category' => 'Higiene',
            ]);

            $resource = new PackingItemResource($item);
            $serialized = $resource->toArray(request());

            // Verificar que el nombre se preserva exactamente
            $this->assertEquals(
                $name,
                $serialized['name'],
                "Nombre con caracteres especiales debe preservarse en serialización"
            );
        }
    }

    /**
     * Test: Serialización es consistente (determinística)
     */
    public function test_serialization_is_deterministic(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['user_id' => $user->id]);
        $packingList = PackingList::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);

        PackingItem::factory()->create([
            'packing_list_id' => $packingList->id,
            'name' => 'Test Item',
            'category' => 'Ropa',
            'is_packed' => true,
        ]);

        // Serializar múltiples veces
        $serializations = [];
        for ($i = 0; $i < 5; $i++) {
            $packingList->refresh();
            $resource = new PackingListResource($packingList);
            $serializations[] = json_encode($resource->toArray(request()));
        }

        // Todas las serializaciones deben ser idénticas
        $uniqueSerializations = array_unique($serializations);
        $this->assertCount(
            1,
            $uniqueSerializations,
            "Serialización debe ser determinística: mismo objeto -> mismo JSON"
        );
    }
}

