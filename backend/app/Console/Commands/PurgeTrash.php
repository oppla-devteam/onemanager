<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Task;
use App\Models\TaskBoard;
use App\Models\TaskList;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Invoice;
use App\Models\Contract;
use App\Models\Supplier;
use App\Models\Partner;
use App\Models\Restaurant;

class PurgeTrash extends Command
{
    protected $signature = 'trash:purge {--days=30 : Giorni dopo i quali eliminare definitivamente}';
    protected $description = 'Elimina definitivamente gli elementi nel cestino più vecchi di N giorni';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $this->info("Pulizia cestino: eliminazione elementi cancellati prima di {$cutoff->toDateString()}");

        // Ordine: prima i figli (Task), poi i genitori (TaskList, TaskBoard)
        $models = [
            'Task'        => Task::class,
            'TaskList'    => TaskList::class,
            'TaskBoard'   => TaskBoard::class,
            'Client'      => Client::class,
            'Lead'        => Lead::class,
            'Opportunity' => Opportunity::class,
            'Invoice'     => Invoice::class,
            'Contract'    => Contract::class,
            'Supplier'    => Supplier::class,
            'Partner'     => Partner::class,
            'Restaurant'  => Restaurant::class,
        ];

        $totalDeleted = 0;

        foreach ($models as $name => $modelClass) {
            $count = $modelClass::onlyTrashed()
                ->where('deleted_at', '<', $cutoff)
                ->count();

            if ($count > 0) {
                $modelClass::onlyTrashed()
                    ->where('deleted_at', '<', $cutoff)
                    ->forceDelete();

                $this->info("  {$name}: {$count} eliminati definitivamente");
                $totalDeleted += $count;
            }
        }

        if ($totalDeleted === 0) {
            $this->info('Nessun elemento da eliminare.');
        } else {
            $this->info("Totale eliminati: {$totalDeleted}");
        }

        Log::info('[TrashPurge] Pulizia cestino completata', [
            'days' => $days,
            'total_deleted' => $totalDeleted,
        ]);

        return Command::SUCCESS;
    }
}
