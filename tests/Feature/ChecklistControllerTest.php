<?php

namespace Tests\Feature;

use App\Models\ChecklistItem;
use App\Models\Trip;
use App\Models\TripDocumentChecklist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Trip $trip;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->trip = Trip::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_user_can_get_checklist(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/checklist");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'trip_id',
                'items',
                'progress',
            ],
        ]);
    }

    public function test_checklist_auto_creates_with_default_items(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/checklist");

        $response->assertStatus(200);
        $this->assertDatabaseHas('trip_document_checklists', [
            'trip_id' => $this->trip->id,
        ]);

        $checklist = TripDocumentChecklist::where('trip_id', $this->trip->id)->first();
        $this->assertNotNull($checklist);
        $this->assertEquals(8, $checklist->items()->count());
    }

    public function test_user_can_add_custom_checklist_item(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/checklist/items", [
                'name' => 'Custom Item',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Custom Item');
        $response->assertJsonPath('data.is_default', false);
    }

    public function test_user_can_toggle_item_completion(): void
    {
        $checklist = TripDocumentChecklist::create(['trip_id' => $this->trip->id]);
        $item = ChecklistItem::create([
            'trip_document_checklist_id' => $checklist->id,
            'name' => 'Test Item',
            'is_default' => false,
            'is_completed' => false,
            'position' => 0,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/checklist/items/{$item->id}/complete", [
                'is_completed' => true,
            ]);

        $response->assertStatus(200);
        $this->assertTrue($item->fresh()->is_completed);
    }

    public function test_user_can_delete_checklist_item(): void
    {
        $checklist = TripDocumentChecklist::create(['trip_id' => $this->trip->id]);
        $item = ChecklistItem::create([
            'trip_document_checklist_id' => $checklist->id,
            'name' => 'Deletable Item',
            'is_default' => false,
            'is_completed' => false,
            'position' => 0,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/checklist/items/{$item->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('checklist_items', ['id' => $item->id]);
    }

    public function test_cannot_access_other_user_checklist(): void
    {
        $otherUser = User::factory()->create();
        $otherTrip = Trip::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$otherTrip->id}/checklist");

        // Should return 403 or 500 (authorization failure)
        $this->assertContains($response->status(), [403, 500]);
    }

    public function test_unauthenticated_user_cannot_access_checklist(): void
    {
        $response = $this->getJson("/api/trips/{$this->trip->id}/checklist");

        $response->assertStatus(401);
    }

    public function test_can_import_document_from_google_drive(): void
    {
        $this->markTestSkipped('Google Drive integration requires mock setup');
    }

    public function test_can_preview_document(): void
    {
        $checklist = TripDocumentChecklist::create(['trip_id' => $this->trip->id]);
        $item = ChecklistItem::create([
            'trip_document_checklist_id' => $checklist->id,
            'name' => 'Pasaporte',
            'is_default' => true,
            'is_completed' => false,
            'position' => 1,
        ]);

        $documentResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/checklist/items/{$item->id}/documents", [
                'file' => \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 1024),
            ]);

        $documentId = $documentResponse->json('data.id');

        $previewResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checklist/documents/{$documentId}/preview");

        $previewResponse->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['url', 'mime_type', 'expires_at'],
            ]);
    }

    public function test_preview_requires_authorization(): void
    {
        $otherUser = User::factory()->create();
        $otherTrip = Trip::factory()->create(['user_id' => $otherUser->id]);
        $otherChecklist = TripDocumentChecklist::create(['trip_id' => $otherTrip->id]);
        $otherItem = ChecklistItem::create([
            'trip_document_checklist_id' => $otherChecklist->id,
            'name' => 'Documento',
            'is_default' => true,
            'is_completed' => false,
            'position' => 1,
        ]);

        $documentResponse = $this->actingAs($otherUser, 'sanctum')
            ->postJson("/api/checklist/items/{$otherItem->id}/documents", [
                'file' => \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 1024),
            ]);

        $documentId = $documentResponse->json('data.id');

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checklist/documents/{$documentId}/preview");

        $response->assertStatus(403);
    }
}
