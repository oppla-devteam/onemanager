<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskList extends Model
{
    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (TaskList $list) {
            if ($list->isForceDeleting()) {
                return;
            }
            $list->tasks()->each(function (Task $task) {
                $task->delete();
            });
        });

        static::restoring(function (TaskList $list) {
            $list->tasks()->onlyTrashed()->each(function (Task $task) {
                $task->restore();
            });
        });
    }
    protected $fillable = [
        'task_board_id',
        'name',
        'status_type',
        'position',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function taskBoard(): BelongsTo
    {
        return $this->belongsTo(TaskBoard::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }
}
