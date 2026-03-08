<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Models\Trip;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lista gastos de un viaje específico, verifica autorización.
     */
    public function index(Trip $trip)
    {
        $this->authorize('view', $trip);

        try {
            $expenses = $trip->expenses()->paginate(15);

            return ExpenseResource::collection($expenses);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los gastos.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Crea gasto para el viaje, maneja upload de imagen, verifica autorización.
     */
    public function store(StoreExpenseRequest $request, Trip $trip)
    {
        $this->authorize('update', $trip);

        try {
            $data = $request->validated();

            if ($request->hasFile('receipt_image')) {
                $data['receipt_image'] = $request->file('receipt_image')->store('receipts', 'private');
            }

            $expense = $trip->expenses()->create($data);

            return new ExpenseResource($expense);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el gasto.'], 500);
        }
    }

    /**
     * Display the specified resource.
     * Muestra gasto específico, verifica que pertenezca al viaje autorizado.
     */
    public function show(Trip $trip, Expense $expense)
    {
        $this->authorize('view', $trip);

        if ($expense->trip_id !== $trip->id) {
            return response()->json(['message' => 'Gasto no pertenece al viaje.'], 404);
        }

        return new ExpenseResource($expense);
    }

    /**
     * Update the specified resource in storage.
     * Actualiza gasto, maneja nueva imagen, verifica autorización.
     */
    public function update(UpdateExpenseRequest $request, Trip $trip, Expense $expense)
    {
        $this->authorize('update', $trip);

        if ($expense->trip_id !== $trip->id) {
            return response()->json(['message' => 'Gasto no pertenece al viaje.'], 404);
        }

        try {
            $data = $request->validated();

            if ($request->hasFile('receipt_image')) {
                // Eliminar imagen anterior si existe
                if ($expense->receipt_image) {
                    Storage::disk('private')->delete($expense->receipt_image);
                }
                $data['receipt_image'] = $request->file('receipt_image')->store('receipts', 'private');
            }

            $expense->update($data);

            return new ExpenseResource($expense);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al actualizar el gasto.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Soft delete gasto, elimina imagen, verifica autorización.
     */
    public function destroy(Trip $trip, Expense $expense)
    {
        $this->authorize('update', $trip);

        if ($expense->trip_id !== $trip->id) {
            return response()->json(['message' => 'Gasto no pertenece al viaje.'], 404);
        }

        try {
            // Eliminar imagen si existe
            if ($expense->receipt_image) {
                Storage::disk('private')->delete($expense->receipt_image);
            }

            $expense->delete();

            return response()->json(['message' => 'Gasto eliminado correctamente.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el gasto.'], 500);
        }
    }

    /**
     * Descarga el comprobante de un gasto de forma autenticada.
     * El archivo se sirve desde disco privado; no es accesible públicamente.
     */
    public function downloadReceipt(Trip $trip, Expense $expense): mixed
    {
        $this->authorize('view', $trip);

        if ($expense->trip_id !== $trip->id) {
            return response()->json(['message' => 'Comprobante no encontrado.'], 404);
        }

        if (! $expense->receipt_image) {
            return response()->json(['message' => 'Este gasto no tiene comprobante.'], 404);
        }

        $path = storage_path('app/private/'.$expense->receipt_image);

        if (! file_exists($path)) {
            return response()->json(['message' => 'Archivo no encontrado.'], 404);
        }

        return response()->download($path, basename($expense->receipt_image));
    }
}
