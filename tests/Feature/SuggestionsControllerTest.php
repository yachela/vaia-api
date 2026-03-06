<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SuggestionsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Trip $trip;

    private array $validAnthropicResponse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->trip = Trip::factory()->create([
            'user_id' => $this->user->id,
            'destination' => 'Roma, Italia',
            'budget' => 2000,
        ]);

        $this->validAnthropicResponse = [
            'content' => [[
                'type' => 'text',
                'text' => json_encode([
                    'suggestions' => [
                        ['title' => 'Visita al Coliseo', 'description' => 'Recorrido guiado por el anfiteatro romano.', 'location' => 'Coliseo Romano', 'cost' => 18, 'time' => '09:00'],
                        ['title' => 'Cena en Trastevere', 'description' => 'Restaurante tradicional en el barrio bohemio.', 'location' => 'Trastevere', 'cost' => 35, 'time' => '20:00'],
                        ['title' => 'Museos Vaticanos', 'description' => 'Arte y la Capilla Sixtina.', 'location' => 'Ciudad del Vaticano', 'cost' => 20, 'time' => '10:00'],
                    ],
                ]),
            ]],
        ];
    }

    public function test_owner_can_get_suggestions(): void
    {
        Config::set('services.anthropic.key', 'test-key');
        Http::fake(['https://api.anthropic.com/*' => Http::response($this->validAnthropicResponse, 200)]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/suggestions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['title', 'description', 'location', 'cost', 'time'],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_suggestions_include_existing_activities_in_prompt(): void
    {
        Config::set('services.anthropic.key', 'test-key');

        Activity::factory()->create([
            'trip_id' => $this->trip->id,
            'title' => 'Tour del Foro Romano',
            'location' => 'Foro Romano',
        ]);

        Http::fake(['https://api.anthropic.com/*' => function ($request) {
            $body = json_decode($request->body(), true);
            $prompt = $body['messages'][0]['content'];
            $this->assertStringContainsString('Tour del Foro Romano', $prompt);
            $this->assertStringContainsString('Roma, Italia', $prompt);

            return Http::response($this->validAnthropicResponse, 200);
        }]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/suggestions")
            ->assertStatus(200);
    }

    public function test_returns_503_when_api_key_not_configured(): void
    {
        Config::set('services.anthropic.key', null);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/suggestions")
            ->assertStatus(503)
            ->assertJson(['error' => 'Servicio de IA no configurado.']);
    }

    public function test_returns_502_when_anthropic_api_fails(): void
    {
        Config::set('services.anthropic.key', 'test-key');
        Http::fake(['https://api.anthropic.com/*' => Http::response([], 500)]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/suggestions")
            ->assertStatus(502)
            ->assertJson(['error' => 'Error al contactar el servicio de IA.']);
    }

    public function test_returns_500_when_anthropic_response_has_invalid_json(): void
    {
        Config::set('services.anthropic.key', 'test-key');
        Http::fake(['https://api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => 'No puedo ayudarte con eso.']],
        ], 200)]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/suggestions")
            ->assertStatus(500)
            ->assertJson(['error' => 'Respuesta inesperada del servicio de IA.']);
    }

    public function test_handles_json_embedded_in_text(): void
    {
        Config::set('services.anthropic.key', 'test-key');

        $jsonPart = json_encode([
            'suggestions' => [
                ['title' => 'Visita al Coliseo', 'description' => 'Recorrido.', 'location' => 'Coliseo', 'cost' => 18, 'time' => '09:00'],
                ['title' => 'Cena', 'description' => 'Buena comida.', 'location' => 'Trastevere', 'cost' => 30, 'time' => '20:00'],
                ['title' => 'Vaticano', 'description' => 'Arte.', 'location' => 'Vaticano', 'cost' => 20, 'time' => '10:00'],
            ],
        ]);

        Http::fake(['https://api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => "Aquí están las sugerencias: {$jsonPart} ¡Espero que te sean útiles!"]],
        ], 200)]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/suggestions")
            ->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_other_user_cannot_get_suggestions(): void
    {
        Config::set('services.anthropic.key', 'test-key');
        $other = User::factory()->create();

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/trips/{$this->trip->id}/suggestions")
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_get_suggestions(): void
    {
        $this->postJson("/api/trips/{$this->trip->id}/suggestions")
            ->assertStatus(401);
    }
}
