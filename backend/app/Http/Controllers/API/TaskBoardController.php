<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaskBoard;

class TaskBoardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = TaskBoard::withCount('taskLists');

        // Se non è admin, mostra solo le board assegnate
        if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
            $query->whereHas('users', function($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $boards = $query->orderBy('position')->get();
        return response()->json($boards);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $validated['position'] = TaskBoard::max('position') + 1;
        $board = TaskBoard::create($validated);
        
        // Crea automaticamente le 3 liste di default
        $board->taskLists()->createMany([
            ['name' => 'Da Fare', 'status_type' => 'todo', 'position' => 0],
            ['name' => 'In Corso', 'status_type' => 'in_progress', 'position' => 1],
            ['name' => 'Completati', 'status_type' => 'done', 'position' => 2],
        ]);

        return response()->json($board->load('taskLists'), 201);
    }

    public function show(string $id)
    {
        $user = auth()->user();
        $board = TaskBoard::with(['taskLists.tasks' => function($query) {
            $query->orderBy('position');
        }])->findOrFail($id);
        
        // Verifica accesso per utenti non-admin
        if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
            if (!$board->hasUser($user)) {
                abort(403, 'Non hai accesso a questa board');
            }
        }
        
        return response()->json($board);
    }

    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        $board = TaskBoard::findOrFail($id);
        
        // Verifica accesso per utenti non-admin
        if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
            if (!$board->hasUser($user)) {
                abort(403, 'Non hai accesso a questa board');
            }
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'color' => 'nullable|string',
            'description' => 'nullable|string',
            'position' => 'sometimes|integer|min:0',
        ]);

        $board->update($validated);
        return response()->json($board);
    }

    public function destroy(string $id)
    {
        $board = TaskBoard::findOrFail($id);
        $board->delete();
        return response()->json(['message' => 'Board eliminata con successo']);
    }

    /**
     * Assegna utenti a una board
     */
    public function assignUsers(Request $request, string $id)
    {
        $board = TaskBoard::findOrFail($id);

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $board->users()->sync($validated['user_ids']);

        return response()->json([
            'message' => 'Utenti assegnati con successo',
            'board' => $board->load('users')
        ]);
    }

    /**
     * Ottieni utenti assegnati a una board
     */
    public function getUsers(string $id)
    {
        $board = TaskBoard::findOrFail($id);
        return response()->json($board->users);
    }
}
