<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuthorizedBinkUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BinkAuthController extends Controller
{
    /**
     * Authenticate via Bink OAuth code exchange
     */
    public function authenticate(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required|string',
                'redirect_uri' => 'required|string',
            ]);

            // 1. Exchange code for access token
            $tokenResponse = Http::post('https://binatomy.link/api/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => config('services.bink.client_id'),
                'client_secret' => config('services.bink.client_secret'),
                'code' => $request->code,
                'redirect_uri' => $request->redirect_uri,
            ]);

            if (!$tokenResponse->successful()) {
                Log::error('Bink OAuth token exchange failed', [
                    'status' => $tokenResponse->status(),
                    'body' => $tokenResponse->body(),
                    'client_id' => config('services.bink.client_id'),
                    'has_secret' => !empty(config('services.bink.client_secret')),
                ]);
                return response()->json([
                    'message' => 'Errore durante lo scambio del codice OAuth con Bink.',
                    'debug' => config('app.debug') ? [
                        'status' => $tokenResponse->status(),
                        'response' => $tokenResponse->json(),
                        'has_client_id' => !empty(config('services.bink.client_id')),
                        'has_client_secret' => !empty(config('services.bink.client_secret')),
                    ] : null,
                ], 401);
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'] ?? null;

            if (!$accessToken) {
                return response()->json([
                    'message' => 'Token di accesso non ricevuto da Bink.',
                ], 401);
            }

            // 2. Fetch user profile from Bink
            $userResponse = Http::withToken($accessToken)
                ->get('https://binatomy.link/api/oauth/user');

            if (!$userResponse->successful()) {
                Log::error('Bink user profile fetch failed', [
                    'status' => $userResponse->status(),
                    'body' => $userResponse->body(),
                ]);
                return response()->json([
                    'message' => 'Errore nel recupero del profilo utente da Bink.',
                ], 401);
            }

            $binkUser = $userResponse->json();
            $binkUsername = $binkUser['username'] ?? null;

            if (!$binkUsername) {
                return response()->json([
                    'message' => 'Username Bink non disponibile.',
                ], 401);
            }

            // 3. Check if username is authorized
            $authorizedUser = AuthorizedBinkUser::where('bink_username', $binkUsername)->first();

            if (!$authorizedUser) {
                return response()->json([
                    'message' => 'Username non autorizzato ad accedere a OneManager.',
                    'bink_username' => $binkUsername,
                ], 403);
            }

            // 4. Create or update local User
            $user = User::where('email', $binkUsername . '@bink.onemanager')->first();

            if (!$user) {
                $user = User::create([
                    'name' => $authorizedUser->display_name ?? $binkUser['name'] ?? $binkUsername,
                    'email' => $binkUsername . '@bink.onemanager',
                    'password' => Hash::make(Str::random(32)),
                ]);
            } else {
                $user->update([
                    'name' => $authorizedUser->display_name ?? $binkUser['name'] ?? $binkUsername,
                ]);
            }

            // 5. Assign role
            $validRoles = ['admin', 'rider-manager', 'viewer'];
            $role = in_array($authorizedUser->role, $validRoles) ? $authorizedUser->role : 'viewer';

            // Sync Spatie roles if available
            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles([$role]);
            } else {
                $user->role = $role;
                $user->save();
            }

            // Sync permissions for non-admin users
            // rider-manager gets permissions from the Spatie role directly (set in migration)
            if (!in_array($role, ['admin', 'rider-manager']) && !empty($authorizedUser->permissions)) {
                $permissionMap = [
                    'dashboard' => 'view-dashboard',
                    'clients' => 'view-clients',
                    'orders' => 'view-orders',
                    'invoices' => 'view-invoices',
                    'tasks' => 'view-tasks',
                    'contracts' => 'view-contracts',
                    'deliveries' => 'view-deliveries',
                    'menu' => 'view-menu',
                    'riders' => 'manage-riders',
                ];

                $spatiPermissions = array_filter(
                    array_map(fn($p) => $permissionMap[$p] ?? null, $authorizedUser->permissions)
                );

                if (method_exists($user, 'syncPermissions') && !empty($spatiPermissions)) {
                    $user->syncPermissions($spatiPermissions);
                }
            }

            // 6. Generate Sanctum token
            $token = $user->createToken('bink-auth')->plainTextToken;

            $primaryRole = $user->roles->first()?->name ?? $user->role ?? 'viewer';

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'bink_username' => $binkUsername,
                    'role' => $primaryRole,
                    'roles' => $user->roles->pluck('name')->toArray(),
                    'permissions' => $user->getPermissions(),
                ],
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            Log::error('Bink auth exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Errore interno durante l\'autenticazione.',
                'debug' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile() . ':' . $e->getLine(),
                ] : null,
            ], 500);
        }
    }

    /**
     * List authorized Bink users (admin only)
     */
    public function listUsers(Request $request)
    {
        $this->authorizeAdmin($request);

        return response()->json(
            AuthorizedBinkUser::orderBy('created_at', 'desc')->get()
        );
    }

    /**
     * Add authorized Bink user (admin only)
     */
    public function addUser(Request $request)
    {
        $this->authorizeAdmin($request);

        $request->validate([
            'bink_username' => 'required|string|unique:authorized_bink_users,bink_username',
            'display_name' => 'nullable|string|max:255',
            'role' => 'required|in:admin,rider-manager,viewer',
            'permissions' => 'nullable|array',
        ]);

        $user = AuthorizedBinkUser::create([
            'bink_username' => $request->bink_username,
            'display_name' => $request->display_name,
            'role' => $request->role,
            'permissions' => $request->role === 'admin' ? [] : ($request->permissions ?? []),
        ]);

        return response()->json($user, 201);
    }

    /**
     * Update authorized Bink user (admin only)
     */
    public function updateUser(Request $request, int $id)
    {
        $this->authorizeAdmin($request);

        $user = AuthorizedBinkUser::findOrFail($id);

        $request->validate([
            'display_name' => 'nullable|string|max:255',
            'role' => 'required|in:admin,rider-manager,viewer',
            'permissions' => 'nullable|array',
        ]);

        $user->update([
            'display_name' => $request->display_name,
            'role' => $request->role,
            'permissions' => $request->role === 'admin' ? [] : ($request->permissions ?? []),
        ]);

        return response()->json($user);
    }

    /**
     * Remove authorized Bink user (admin only)
     */
    public function removeUser(Request $request, int $id)
    {
        $this->authorizeAdmin($request);

        $user = AuthorizedBinkUser::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Utente rimosso']);
    }

    /**
     * Check if the requesting user is an admin
     */
    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user();

        $isAdmin = $user->roles->contains('name', 'admin')
            || $user->roles->contains('name', 'super-admin')
            || $user->role === 'admin'
            || $user->role === 'super-admin';

        if (!$isAdmin) {
            abort(403, 'Accesso non autorizzato');
        }
    }
}
