<?php

namespace App\Services;

use App\Models\PackingItem;
use App\Models\PackingList;
use App\Models\Trip;
use Illuminate\Support\Facades\Log;

class PackingListService
{
    /**
     * Genera una lista base de equipaje según la duración del viaje
     */
    public function generateBaseList(Trip $trip): PackingList
    {
        $packingList = PackingList::firstOrCreate([
            'trip_id' => $trip->id,
            'user_id' => $trip->user_id,
        ]);

        // Eliminar ítems existentes para regenerar
        $packingList->items()->delete();

        $duration = $trip->start_date->diffInDays($trip->end_date) + 1;

        // Generar ítems por categoría
        $this->generateDocumentacionItems($packingList);
        $this->generateHigieneItems($packingList, $duration);
        $this->generateRopaItems($packingList, $duration, $trip->trip_type);
        $this->generateTecnologiaItems($packingList);

        Log::info("Lista base generada para viaje {$trip->id} con duración de {$duration} días");

        return $packingList->fresh('items');
    }

    /**
     * Genera ítems de categoría Documentación
     */
    private function generateDocumentacionItems(PackingList $packingList): void
    {
        $items = [
            'Pasaporte',
            'Tarjeta de embarque',
            'Documento de identidad',
            'Seguro de viaje',
        ];

        foreach ($items as $item) {
            PackingItem::create([
                'packing_list_id' => $packingList->id,
                'name' => $item,
                'category' => 'Documentación',
                'is_packed' => false,
                'is_suggested' => false,
            ]);
        }
    }

    /**
     * Genera ítems de categoría Higiene
     */
    private function generateHigieneItems(PackingList $packingList, int $duration): void
    {
        $items = [
            'Cepillo de dientes',
            'Pasta dental',
            'Champú',
            'Jabón',
            'Desodorante',
            'Toalla',
        ];

        // Para viajes largos, agregar más ítems
        if ($duration > 7) {
            $items[] = 'Protector solar';
            $items[] = 'Repelente de insectos';
        }

        foreach ($items as $item) {
            PackingItem::create([
                'packing_list_id' => $packingList->id,
                'name' => $item,
                'category' => 'Higiene',
                'is_packed' => false,
                'is_suggested' => false,
            ]);
        }
    }

    /**
     * Genera ítems de categoría Ropa proporcional a la duración y tipo de viaje
     */
    private function generateRopaItems(PackingList $packingList, int $duration, ?string $tripType = null): void
    {
        // Ítems base
        $items = [
            'Ropa interior (juego por día)',
            'Calcetines (pares según días)',
            'Pantalones',
            'Camisetas',
            'Zapatos cómodos',
        ];

        // Para viajes largos (>7 días), agregar más variedad
        if ($duration > 7) {
            $items[] = 'Ropa formal (opcional)';
            $items[] = 'Traje de baño';
            $items[] = 'Zapatillas deportivas';
            $items[] = 'Gorra o sombrero';
        }

        // Ítems específicos por tipo de viaje
        match ($tripType) {
            'aventura' => array_push($items, 'Ropa deportiva de secado rápido', 'Calzado de montaña o trekking'),
            'familiar' => array_push($items, 'Ropa extra para los niños'),
            default => null,
        };

        foreach ($items as $item) {
            PackingItem::create([
                'packing_list_id' => $packingList->id,
                'name' => $item,
                'category' => 'Ropa',
                'is_packed' => false,
                'is_suggested' => false,
            ]);
        }
    }

    /**
     * Genera ítems de categoría Tecnología
     */
    private function generateTecnologiaItems(PackingList $packingList): void
    {
        $items = [
            'Cargador de teléfono',
            'Adaptador de corriente',
            'Auriculares',
            'Power bank',
        ];

        foreach ($items as $item) {
            PackingItem::create([
                'packing_list_id' => $packingList->id,
                'name' => $item,
                'category' => 'Tecnología',
                'is_packed' => false,
                'is_suggested' => false,
            ]);
        }
    }
}
