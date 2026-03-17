<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        Log::info('Login attempt', ['email' => $request->input('email')]);

        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            Log::warning('Login failed', ['email_prefix' => substr($request->input('email'), 0, 3)]);
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info('Login successful', ['user_id' => $user->id]);

        return response()->json([
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'data' => null,
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'data' => $request->user(),
        ]);
    }

    public function updateUser(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'country' => 'nullable|string|max:120',
            'language' => 'nullable|string|max:60',
            'currency' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'data' => $user->fresh(),
            'message' => 'Perfil actualizado',
        ]);
    }

    /**
     * Sube o reemplaza el avatar del usuario autenticado.
     * POST /api/user/avatar  (multipart, campo: avatar)
     */
    public function uploadAvatar(Request $request): mixed
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $user = $request->user();

        // Eliminar avatar anterior si es un path interno
        $old = $user->getRawOriginal('avatar_url') ?? $user->attributes['avatar_url'] ?? null;
        if ($old && ! str_starts_with($old, 'http')) {
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('avatar')->store("avatars/{$user->id}", 'public');

        $user->update(['avatar_url' => $path]);

        return response()->json([
            'data' => $user->fresh(),
            'message' => 'Avatar actualizado correctamente.',
        ]);
    }
}
