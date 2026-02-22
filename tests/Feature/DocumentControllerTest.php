<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
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

    public function test_user_can_list_documents_for_their_trip(): void
    {
        $doc1 = Document::factory()->create([
            'trip_id' => $this->trip->id,
            'user_id' => $this->user->id,
        ]);
        $doc2 = Document::factory()->create([
            'trip_id' => $this->trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/documents");

        $response->assertStatus(200);
        $this->assertNotNull($response->json());
    }

    public function test_user_cannot_list_documents_for_others_trip(): void
    {
        $otherUser = User::factory()->create();
        $otherTrip = Trip::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$otherTrip->id}/documents");

        $response->assertStatus(403);
    }

    public function test_upload_document_fails_without_file(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/documents", [
                'description' => 'Test document',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document']);
    }

    public function test_upload_document_fails_for_others_trip(): void
    {
        $otherUser = User::factory()->create();
        $otherTrip = Trip::factory()->create(['user_id' => $otherUser->id]);

        Storage::fake('public');
        $file = UploadedFile::fake()->create('test-document.pdf', 1024);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$otherTrip->id}/documents", [
                'document' => $file,
            ]);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_document_from_others_trip(): void
    {
        Storage::fake('public');
        
        $otherUser = User::factory()->create();
        $otherTrip = Trip::factory()->create(['user_id' => $otherUser->id]);
        $document = Document::factory()->create([
            'trip_id' => $otherTrip->id,
            'user_id' => $otherUser->id,
            'file_path' => 'documents/test.pdf',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/documents/{$document->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_documents(): void
    {
        $response = $this->getJson("/api/trips/{$this->trip->id}/documents");

        $response->assertStatus(401);
    }
}
