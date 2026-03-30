<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Lista tutti gli utenti con ruoli e permessi
     */
    public function index(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole('super-admin') && !$authUser->hasRole('admin') && $authUser->role !== 'admin') {
            abort(403, 'Non autorizzato');
        }

        $users = User::orderBy('name')->get()->map(function ($user) {
            $primaryRole = 'viewer';
            try {
                $spatieRole = $user->roles->first()?->name;
                if ($spatieRole) {
                    $primaryRole = $spatieRole;
                } elseif ($user->role) {
                    $primaryRole = $user->role;
                }
            } catch (\Exception $e) {
                $primaryRole = $user->role ?? 'viewer';
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $primaryRole,
                'permissions' => $user->permissions ?? [],
            ];
        });

        return response()->json($users);
    }

    /**
     * Crea nuovo utente
     */
    public function store(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole('super-admin') && !$authUser->hasRole('admin') && $authUser->role !== 'admin') {
            abort(403, 'Non autorizzato');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,viewer',
            'permissions' => 'nullable|array',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'permissions' => $validated['role'] === 'admin'
                ? ['dashboard', 'clients', 'contracts', 'tasks', 'invoices', 'deliveries', 'accounting', 'crm', 'orders', 'menu']
                : ($validated['permissions'] ?? []),
        ]);

        // Assegna ruolo Spatie se disponibile
        try {
            $user->assignRole($validated['role']);
        } catch (\Exception $e) {
            // Spatie roles potrebbe non essere configurato
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $validated['role'],
            'permissions' => $user->permissions ?? [],
        ], 201);
    }

    /**
     * Modifica utente
     */
    public function update(Request $request, string $id)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole('super-admin') && !$authUser->hasRole('admin') && $authUser->role !== 'admin') {
            abort(403, 'Non autorizzato');
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|in:admin,viewer',
            'permissions' => 'nullable|array',
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        if (isset($validated['role'])) {
            $user->role = $validated['role'];

            // Aggiorna ruolo Spatie
            try {
                $user->syncRoles([$validated['role']]);
            } catch (\Exception $e) {
                // Spatie roles potrebbe non essere configurato
            }

            // Se admin, assegna tutti i permessi
            if ($validated['role'] === 'admin') {
                $user->permissions = ['dashboard', 'clients', 'contracts', 'tasks', 'invoices', 'deliveries', 'accounting', 'crm', 'orders', 'menu'];
            }
        }
        if (isset($validated['permissions']) && ($user->role ?? $validated['role'] ?? '') !== 'admin') {
            $user->permissions = $validated['permissions'];
        }

        $user->save();

        $primaryRole = 'viewer';
        try {
            $primaryRole = $user->roles->first()?->name ?? $user->role ?? 'viewer';
        } catch (\Exception $e) {
            $primaryRole = $user->role ?? 'viewer';
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $primaryRole,
            'permissions' => $user->permissions ?? [],
        ]);
    }

    /**
     * Elimina utente
     */
    public function destroy(string $id)
    {
        $authUser = auth()->user();

        if (!$authUser->hasRole('super-admin') && !$authUser->hasRole('admin') && $authUser->role !== 'admin') {
            abort(403, 'Non autorizzato');
        }

        // Non può eliminare se stesso
        if ((int) $id === $authUser->id) {
            return response()->json(['message' => 'Non puoi eliminare il tuo account'], 422);
        }

        $user = User::findOrFail($id);

        // Revoca tutti i token
        $user->tokens()->delete();

        // Rimuovi ruoli Spatie
        try {
            $user->syncRoles([]);
        } catch (\Exception $e) {
            // Ignora
        }

        $user->delete();

        return response()->json(['message' => 'Utente eliminato con successo']);
    }
}
