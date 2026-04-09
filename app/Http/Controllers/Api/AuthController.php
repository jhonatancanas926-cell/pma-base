<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users',
            'password'  => ['required', 'confirmed', Password::min(8)],
            'documento' => 'nullable|string|max:20|unique:users',
            'programa'  => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name'      => $datos['name'],
            'email'     => $datos['email'],
            'password'  => Hash::make($datos['password']),
            'documento' => $datos['documento'] ?? null,
            'programa'  => $datos['programa'] ?? null,
            'role'      => 'evaluado',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Registro exitoso.',
            'user'    => $user->only(['id','name','email','role','documento','programa']),
            'token'   => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas.'], 401);
        }

        $user->tokens()->delete(); // Revocar tokens anteriores
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Sesión iniciada.',
            'user'    => $user->only(['id','name','email','role','documento','programa']),
            'token'   => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }
}
