<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Le credenziali fornite non sono corrette.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        // Determina il ruolo principale
        $primaryRole = $user->roles->first()?->name ?? $user->role ?? 'user';

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $primaryRole,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $user->getPermissions(),
            ],
            'token' => $token,
        ]);
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout effettuato con successo',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();
        
        // Determina il ruolo principale
        $primaryRole = $user->roles->first()?->name ?? $user->role ?? 'user';
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $primaryRole,
            'roles' => $user->roles->pluck('name')->toArray(),
            'permissions' => $user->getPermissions(),
        ]);
    }

    /**
     * Register new user
     */
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

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Get all users (for admin use in dropdowns)
     */
    public function getAllUsers(Request $request)
    {
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    /**
     * List API tokens for the authenticated user
     */
    public function listApiTokens(Request $request)
    {
        $tokens = $request->user()->tokens()
            ->where('name', 'like', 'api-%')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'created_at' => $token->created_at,
                'last_used_at' => $token->last_used_at,
            ]);

        return response()->json($tokens);
    }

    /**
     * Create a new API token (for MCP server or external integrations)
     */
    public function createApiToken(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $tokenName = 'api-' . $request->name;
        $token = $request->user()->createToken($tokenName);

        return response()->json([
            'token' => $token->plainTextToken,
            'id' => $token->accessToken->id,
            'name' => $tokenName,
        ]);
    }

    /**
     * Revoke an API token
     */
    public function revokeApiToken(Request $request, $id)
    {
        $deleted = $request->user()->tokens()->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Token non trovato'], 404);
        }

        return response()->json(['message' => 'Token revocato con successo']);
    }
}
