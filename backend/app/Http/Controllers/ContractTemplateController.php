<?php

namespace App\Http\Controllers;

use App\Models\ContractTemplate;
use Illuminate\Http\Request;

class ContractTemplateController extends Controller
{
    /**
     * Lista templates
     */
    public function index(Request $request)
    {
        $query = ContractTemplate::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        $templates = $query->orderBy('name')->get();

        return response()->json($templates);
    }

    /**
     * Dettaglio template
     */
    public function show(ContractTemplate $template)
    {
        return response()->json($template);
    }

    /**
     * Crea template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:contract_templates,code',
            'description' => 'nullable|string',
            'html_template' => 'required|string',
            'required_fields' => 'required|array',
            'category' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $template = ContractTemplate::create($validated);

        return response()->json($template, 201);
    }

    /**
     * Aggiorna template
     */
    public function update(Request $request, ContractTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:100|unique:contract_templates,code,' . $template->id,
            'description' => 'nullable|string',
            'html_template' => 'sometimes|string',
            'required_fields' => 'sometimes|array',
            'category' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return response()->json($template);
    }

    /**
     * Elimina template (soft delete)
     */
    public function destroy(ContractTemplate $template)
    {
        // Verifica se ci sono contratti attivi con questo template
        $activeContracts = $template->contracts()
            ->whereIn('status', ['active', 'signed', 'sent_to_client'])
            ->count();

        if ($activeContracts > 0) {
            return response()->json([
                'error' => 'Impossibile eliminare: ci sono contratti attivi che usano questo template'
            ], 422);
        }

        $template->delete();

        return response()->json([
            'message' => 'Template eliminato'
        ]);
    }

    /**
     * Anteprima template compilato
     */
    public function preview(Request $request, ContractTemplate $template)
    {
        $validated = $request->validate([
            'data' => 'required|array',
        ]);

        $compiledHtml = $template->compile($validated['data']);

        return response()->json([
            'html' => $compiledHtml,
            'missing_fields' => $template->validateRequiredFields($validated['data']),
        ]);
    }

    /**
     * Duplica template
     */
    public function duplicate(ContractTemplate $template)
    {
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copia)';
        $newTemplate->code = $template->code . '_copy_' . time();
        $newTemplate->is_active = false;
        $newTemplate->save();

        return response()->json($newTemplate, 201);
    }
}
