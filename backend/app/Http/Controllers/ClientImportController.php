<?php

namespace App\Http\Controllers;

use App\Services\ClientImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ClientImportController extends Controller
{
    protected $importService;

    public function __construct(ClientImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Importa titolari da file CSV
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240' // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $path = $file->storeAs('imports', 'clients_' . time() . '.csv');
            $fullPath = storage_path('app/' . $path);

            $results = $this->importService->importFromCsvFile($fullPath);

            // Elimina file temporaneo
            unlink($fullPath);

            return response()->json([
                'success' => true,
                'message' => 'Importazione completata',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Errore importazione CSV titolari: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'importazione',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Importa titolari da dati JSON
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function importJson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'data.*.ragione_sociale' => 'required|string',
            'data.*.piva' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = $this->importService->importClientsFromCsv($request->input('data'));

            return response()->json([
                'success' => true,
                'message' => 'Importazione completata',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Errore importazione JSON titolari: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'importazione',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
