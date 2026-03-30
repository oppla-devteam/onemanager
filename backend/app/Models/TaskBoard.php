<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskBoard extends Model
{
    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (TaskBoard $board) {
            if ($board->isForceDeleting()) {
                return;
            }
            $board->taskLists()->each(function (TaskList $list) {
                $list->delete();
            });
        });

        static::restoring(function (TaskBoard $board) {
            $board->taskLists()->onlyTrashed()->each(function (TaskList $list) {
                $list->restore();
            });
        });
    }
    protected $fillable = [
        'name',
        'description',
        'color',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function taskLists(): HasMany
    {
        return $this->hasMany(TaskList::class);
    }

    /**
     * Utenti assegnati a questa board
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_board_user')
            ->withTimestamps();
    }

    /**
     * Controlla se un utente ha accesso a questa board
     */
    public function hasUser(User $user): bool
    {
        // Super-admin e admin hanno sempre accesso
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return true;
        }

        return $this->users()->where('users.id', $user->id)->exists();
    }
}
