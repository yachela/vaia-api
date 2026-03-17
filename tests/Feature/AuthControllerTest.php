<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $registerData = [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $registerData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'access_token',
                    'token_type',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
        ]);
    }

    public function test_registration_fails_with_existing_email(): void
    {
        $existingUser = User::factory()->create();

        $registerData = [
            'name' => 'Test User',
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $registerData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'access_token',
                    'token_type',
                ],
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/login', $loginData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_get_their_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }

    public function test_authenticated_user_can_update_their_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Nombre Original',
            'bio' => null,
            'country' => null,
            'language' => null,
            'currency' => null,
        ]);

        $updateData = [
            'name' => 'Nombre Actualizado',
            'bio' => 'Viajero frecuente',
            'country' => 'España',
            'language' => 'es',
            'currency' => 'EUR',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Nombre Actualizado',
                    'bio' => 'Viajero frecuente',
                    'country' => 'España',
                    'language' => 'es',
                    'currency' => 'EUR',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nombre Actualizado',
            'bio' => 'Viajero frecuente',
        ]);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Sesión cerrada exitosamente']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_user_can_upload_avatar(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->post('/api/user/avatar', [
                'avatar' => \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg', 200, 200),
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'avatar_url'],
            ]);

        $this->assertNotNull($user->fresh()->avatar_url);
    }

    public function test_avatar_upload_fails_with_invalid_file(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson('/api/user/avatar', [
                'avatar' => \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 1024),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_login_rate_limited_after_5_attempts(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'wrongpassword',
            ]);
            $response->assertStatus(422);
        }

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
    }
}
