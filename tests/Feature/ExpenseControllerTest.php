<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $trip;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->trip = Trip::factory()->create(['user_id' => $this->user->id]);
        Storage::fake('private');
    }

    public function test_user_can_list_expenses_of_their_trip(): void
    {
        Expense::factory()->create(['trip_id' => $this->trip->id]);
        Expense::factory()->create(); // Otro viaje

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/expenses");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'amount', 'description', 'date', 'category', 'receipt_image_url'],
                ],
                'links', 'meta',
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_create_expense_for_their_trip(): void
    {
        $expenseData = [
            'amount' => 100.50,
            'description' => 'Gasto de transporte',
            'date' => now()->format('Y-m-d'),
            'category' => 'Transporte',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/expenses", $expenseData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'amount', 'description', 'date', 'category', 'receipt_image_url'],
            ]);

        $this->assertDatabaseHas('expenses', [
            'amount' => 100.50,
            'description' => 'Gasto de transporte',
            'category' => 'Transporte',
            'trip_id' => $this->trip->id,
        ]);
    }

    public function test_user_can_create_expense_with_image(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg');

        $expenseData = [
            'amount' => 50.00,
            'description' => 'Comida',
            'date' => now()->format('Y-m-d'),
            'receipt_image' => $file,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/expenses", $expenseData);

        $response->assertStatus(201);

        $expense = Expense::first();
        $this->assertNotNull($expense->receipt_image);
        Storage::disk('private')->assertExists($expense->receipt_image);
    }

    public function test_user_can_update_expense_of_their_trip(): void
    {
        $expense = Expense::factory()->create(['trip_id' => $this->trip->id]);
        $updateData = [
            'amount' => 75.00,
            'description' => 'Gasto actualizado',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/trips/{$this->trip->id}/expenses/{$expense->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'amount' => 75.00,
                    'description' => 'Gasto actualizado',
                ],
            ]);

        $this->assertDatabaseHas('expenses', array_merge($updateData, ['id' => $expense->id]));
    }

    public function test_user_can_update_expense_with_new_image(): void
    {
        $oldFile = UploadedFile::fake()->image('old.jpg');
        $expense = Expense::factory()->create([
            'trip_id' => $this->trip->id,
            'receipt_image' => $oldFile->store('receipts', 'private'),
        ]);

        $newFile = UploadedFile::fake()->image('new.jpg');
        $updateData = [
            'amount' => 100.00,
            'receipt_image' => $newFile,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/trips/{$this->trip->id}/expenses/{$expense->id}", $updateData);

        $response->assertStatus(200);

        $updatedExpense = $expense->fresh();
        $this->assertNotEquals($expense->receipt_image, $updatedExpense->receipt_image);
        Storage::disk('private')->assertMissing($expense->receipt_image);
        Storage::disk('private')->assertExists($updatedExpense->receipt_image);
    }

    public function test_user_can_delete_expense_of_their_trip(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg');
        $expense = Expense::factory()->create([
            'trip_id' => $this->trip->id,
            'receipt_image' => $file->store('receipts', 'private'),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/trips/{$this->trip->id}/expenses/{$expense->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Gasto eliminado correctamente.']);

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
        Storage::disk('private')->assertMissing($expense->receipt_image);
    }

    public function test_image_validation(): void
    {
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);

        $expenseData = [
            'amount' => 50.00,
            'description' => 'Test',
            'date' => now()->format('Y-m-d'),
            'receipt_image' => $invalidFile,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/expenses", $expenseData);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['receipt_image']]);
    }

    public function test_validation_errors_on_create_expense(): void
    {
        $invalidData = [
            'amount' => -50,
            'description' => '',
            'date' => 'invalid-date',
            'category' => str_repeat('a', 101), // Too long
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/expenses", $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }
}
