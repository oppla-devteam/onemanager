<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpplaUsersController extends Controller
{
    /**
     * Get all users from Oppla database
     */
    public function getUsers()
    {
        try {
            $users = DB::connection('oppla')
                ->table('users')
                ->select('id', 'name', 'email', 'phone', 'created_at')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero degli utenti: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all restaurants from Oppla database
     */
    public function getRestaurants()
    {
        try {
            $restaurants = DB::connection('oppla')
                ->table('restaurants')
                ->select('id', 'name', 'slug', 'address', 'phone', 'email', 'user_id', 'created_at')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $restaurants
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dei ristoranti: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users with their restaurants grouped
     */
    public function getUsersWithRestaurants()
    {
        try {
            $users = DB::connection('oppla')
                ->table('users')
                ->select('id', 'name', 'email', 'phone', 'created_at')
                ->orderBy('name')
                ->get();

            $usersWithRestaurants = $users->map(function($user) {
                $restaurants = DB::connection('oppla')
                    ->table('restaurants')
                    ->where('user_id', $user->id)
                    ->select('id', 'name', 'slug', 'address', 'phone', 'email', 'created_at')
                    ->get();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'created_at' => $user->created_at,
                    'restaurants' => $restaurants,
                    'restaurants_count' => $restaurants->count()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $usersWithRestaurants
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dei dati: ' . $e->getMessage()
            ], 500);
        }
    }
}
