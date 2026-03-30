<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

class TrashController extends Controller
{
    private function getModelMap(): array
    {
        return [
            'task'        => ['model' => Task::class,        'label' => 'Task',        'name_field' => 'title'],
            'task_board'  => ['model' => TaskBoard::class,   'label' => 'Board',       'name_field' => 'name'],
            'client'      => ['model' => Client::class,      'label' => 'Cliente',     'name_field' => 'ragione_sociale'],
            'lead'        => ['model' => Lead::class,        'label' => 'Lead',        'name_field' => 'company_name'],
            'opportunity' => ['model' => Opportunity::class,  'label' => 'Opportunità', 'name_field' => 'name'],
            'invoice'     => ['model' => Invoice::class,     'label' => 'Fattura',     'name_field' => 'numero_fattura'],
            'contract'    => ['model' => Contract::class,    'label' => 'Contratto',   'name_field' => 'contract_number'],
            'supplier'    => ['model' => Supplier::class,    'label' => 'Fornitore',   'name_field' => 'ragione_sociale'],
            'partner'     => ['model' => Partner::class,     'label' => 'Partner',     'name_field' => 'nome'],
            'restaurant'  => ['model' => Restaurant::class,  'label' => 'Ristorante',  'name_field' => 'nome'],
        ];
    }

    /**
     * GET /api/trash
     */
    public function index(Request $request)
    {
        $filterType = $request->get('type');
        $modelMap = $this->getModelMap();
        $items = [];

        $typesToQuery = $filterType && isset($modelMap[$filterType])
            ? [$filterType => $modelMap[$filterType]]
            : $modelMap;

        foreach ($typesToQuery as $typeKey => $config) {
            $modelClass = $config['model'];
            $nameField = $config['name_field'];
            $label = $config['label'];

            $trashed = $modelClass::onlyTrashed()
                ->orderBy('deleted_at', 'desc')
                ->get();

            foreach ($trashed as $record) {
                $items[] = [
                    'id'          => $record->id,
                    'type'        => $typeKey,
                    'type_label'  => $label,
                    'name'        => $record->{$nameField} ?? "#{$record->id}",
                    'deleted_at'  => $record->deleted_at->toISOString(),
                    'days_left'   => max(0, 30 - $record->deleted_at->diffInDays(now())),
                    'created_at'  => $record->created_at?->toISOString(),
                ];
            }
        }

        usort($items, fn($a, $b) => $b['deleted_at'] <=> $a['deleted_at']);

        $perPage = (int) $request->get('per_page', 50);
        $page = (int) $request->get('page', 1);
        $total = count($items);
        $paginatedItems = array_slice($items, ($page - 1) * $perPage, $perPage);

        // Summary counts per tipo
        $summary = [];
        foreach ($this->getModelMap() as $typeKey => $config) {
            $count = $config['model']::onlyTrashed()->count();
            if ($count > 0) {
                $summary[$typeKey] = [
                    'label' => $config['label'],
                    'count' => $count,
                ];
            }
        }

        return response()->json([
            'data'         => array_values($paginatedItems),
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'last_page'    => (int) ceil($total / $perPage),
            'summary'      => $summary,
        ]);
    }

    /**
     * POST /api/trash/{type}/{id}/restore
     */
    public function restore(string $type, string $id)
    {
        $config = $this->resolveModel($type);
        $record = $config['model']::onlyTrashed()->findOrFail($id);

        // Ripristino task: se il parent (lista/board) è eliminato, ripristina la catena
        if ($type === 'task') {
            $taskList = TaskList::withTrashed()->find($record->task_list_id);
            if ($taskList && $taskList->trashed()) {
                $taskBoard = TaskBoard::withTrashed()->find($taskList->task_board_id);
                if ($taskBoard && $taskBoard->trashed()) {
                    $taskBoard->restore(); // cascade restore liste e task
                    return response()->json([
                        'message' => 'Task ripristinato insieme alla board e alle liste associate',
                    ]);
                }
                $taskList->restore(); // cascade restore task
                return response()->json([
                    'message' => 'Task ripristinato insieme alla lista associata',
                ]);
            }
        }

        $record->restore();

        return response()->json([
            'message' => "{$config['label']} ripristinato con successo",
        ]);
    }

    /**
     * DELETE /api/trash/{type}/{id}
     */
    public function forceDelete(string $type, string $id)
    {
        $config = $this->resolveModel($type);
        $record = $config['model']::onlyTrashed()->findOrFail($id);

        $record->forceDelete();

        return response()->json([
            'message' => "{$config['label']} eliminato definitivamente",
        ]);
    }

    /**
     * DELETE /api/trash/empty
     */
    public function empty()
    {
        $modelMap = $this->getModelMap();
        $totalDeleted = 0;

        // Elimina in ordine: prima i figli, poi i genitori
        $orderedTypes = ['task', 'task_board', 'client', 'lead', 'opportunity', 'invoice', 'contract', 'supplier', 'partner', 'restaurant'];

        foreach ($orderedTypes as $typeKey) {
            if (!isset($modelMap[$typeKey])) continue;
            $config = $modelMap[$typeKey];
            $count = $config['model']::onlyTrashed()->count();
            if ($count > 0) {
                $config['model']::onlyTrashed()->forceDelete();
                $totalDeleted += $count;
            }
        }

        return response()->json([
            'message' => "Cestino svuotato: {$totalDeleted} elementi eliminati definitivamente",
            'deleted_count' => $totalDeleted,
        ]);
    }

    private function resolveModel(string $type): array
    {
        $map = $this->getModelMap();
        if (!isset($map[$type])) {
            abort(404, "Tipo '{$type}' non trovato");
        }
        return $map[$type];
    }
}
