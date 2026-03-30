<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskBoard;
use App\Models\TaskList;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Task::with('taskList.taskBoard');
        
        // Se non è admin, filtra solo task delle board assegnate
        if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
            $query->whereHas('taskList.taskBoard.users', function($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        if ($request->has('task_list_id')) {
            $query->where('task_list_id', $request->task_list_id);
        }

        if ($request->has('board_id')) {
            $query->whereHas('taskList', function($q) use ($request) {
                $q->where('task_board_id', $request->board_id);
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->has('due_date_from')) {
            $query->whereDate('due_date', '>=', $request->due_date_from);
        }

        if ($request->has('due_date_to')) {
            $query->whereDate('due_date', '<=', $request->due_date_to);
        }

        if ($request->has('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        $query->orderBy('position');
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'task_list_id' => 'required|exists:task_lists,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'assigned_to' => 'nullable|string',
            'due_date' => 'nullable|date',
            'tags' => 'nullable|array',
        ]);
        
        // Verifica accesso alla board per utenti non-admin
        if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
            $taskList = TaskList::with('taskBoard')->findOrFail($validated['task_list_id']);
            if (!$taskList->taskBoard->hasUser($user)) {
                abort(403, 'Non hai accesso a questa board');
            }
        }

        $validated['status'] = 'todo';
        $validated['position'] = Task::where('task_list_id', $validated['task_list_id'])->max('position') + 1;

        $task = Task::create($validated);
        return response()->json($task->load('taskList'), 201);
    }

    public function show(string $id)
    {
        $user = auth()->user();
        $task = Task::with('taskList.taskBoard')->findOrFail($id);
        
        // Verifica accesso per utenti non-admin
        if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
            if (!$task->taskList->taskBoard->hasUser($user)) {
                abort(403, 'Non hai accesso a questa task');
            }
        }
        
        return response()->json($task);
    }

    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        $task = Task::with('taskList.taskBoard')->findOrFail($id);
        
        // Verifica accesso per utenti non-admin
        if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
            if (!$task->taskList->taskBoard->hasUser($user)) {
                abort(403, 'Non hai accesso a questa task');
            }
        }

        $validated = $request->validate([
            'task_list_id' => 'sometimes|exists:task_lists,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:todo,in_progress,done',
            'priority' => 'sometimes|in:low,medium,high',
            'assigned_to' => 'nullable|string',
            'due_date' => 'nullable|date',
            'position' => 'sometimes|integer|min:0',
            'tags' => 'nullable|array',
        ]);

        $task->update($validated);
        return response()->json($task->load('taskList'));
    }

    public function destroy(string $id)
    {
        $user = auth()->user();
        $task = Task::with('taskList.taskBoard')->findOrFail($id);
        
        // Verifica accesso per utenti non-admin
        if (!$user->hasRole('super-admin') && !$user->hasRole('admin')) {
            if (!$task->taskList->taskBoard->hasUser($user)) {
                abort(403, 'Non hai accesso a questa task');
            }
        }
        
        $task->delete();
        return response()->json(['message' => 'Task eliminato con successo']);
    }

    public function stats()
    {
        $stats = [
            'total' => Task::count(),
            'todo' => Task::where('status', 'todo')->count(),
            'in_progress' => Task::where('status', 'in_progress')->count(),
            'done' => Task::where('status', 'done')->count(),
            'by_priority' => [
                'high' => Task::where('priority', 'high')->count(),
                'medium' => Task::where('priority', 'medium')->count(),
                'low' => Task::where('priority', 'low')->count(),
            ],
        ];
        return response()->json($stats);
    }
}
