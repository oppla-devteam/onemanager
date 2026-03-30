<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\MenuImport;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MenuImportService
{
    /**
     * Import menu items from CSV array
     *
     * @param int $restaurantId
     * @param array $rows
     * @param int|null $userId
     * @param string|null $filename
     * @return array
     */
    public function importMenuItemsFromCsv(
        int $restaurantId,
        array $rows,
        ?int $userId = null,
        ?string $filename = null
    ): array {
        // Validate restaurant exists
        $restaurant = Restaurant::findOrFail($restaurantId);

        // Create import record
        $import = MenuImport::create([
            'restaurant_id' => $restaurantId,
            'user_id' => $userId,
            'filename' => $filename ?? 'import.csv',
            'total_rows' => count($rows),
            'status' => 'processing',
        ]);

        $results = [
            'import_id' => $import->id,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                try {
                    // Validate row data
                    $validationResult = $this->validateRow($row, $index);

                    if (!$validationResult['valid']) {
                        $results['skipped']++;
                        $results['errors'][] = $validationResult['error'];
                        continue;
                    }

                    // Prepare menu item data
                    $menuItemData = $this->prepareMenuItemData($row, $restaurantId);

                    // Check if menu item already exists (by product_name + category)
                    $existingItem = MenuItem::where('restaurant_id', $restaurantId)
                        ->where('product_name', $menuItemData['product_name'])
                        ->where('category', $menuItemData['category'])
                        ->first();

                    if ($existingItem) {
                        // Update existing item
                        $existingItem->update($menuItemData);
                        $results['updated']++;
                        Log::info("MenuItem updated: {$existingItem->product_name} in {$existingItem->category}");
                    } else {
                        // Create new item
                        MenuItem::create($menuItemData);
                        $results['created']++;
                        Log::info("MenuItem created: {$menuItemData['product_name']} in {$menuItemData['category']}");
                    }

                } catch (\Exception $e) {
                    $results['skipped']++;
                    $results['errors'][] = [
                        'row' => $index + 2, // +2 because of 0-index and header row
                        'data' => $row,
                        'error' => $e->getMessage(),
                    ];
                    Log::error("Error importing menu item row " . ($index + 1) . ": " . $e->getMessage());
                }
            }

            // Update import record
            $import->update([
                'created_count' => $results['created'],
                'updated_count' => $results['updated'],
                'skipped_count' => $results['skipped'],
                'error_count' => count($results['errors']),
                'errors' => $results['errors'],
                'status' => 'completed',
            ]);

            DB::commit();

            Log::info("Menu import completed for restaurant {$restaurantId}", $results);

        } catch (\Exception $e) {
            DB::rollBack();

            $import->update([
                'status' => 'failed',
                'errors' => [['error' => $e->getMessage()]],
            ]);

            Log::error("Menu import failed for restaurant {$restaurantId}: " . $e->getMessage());
            throw $e;
        }

        return $results;
    }

    /**
     * Validate CSV row
     *
     * @param array $row
     * @param int $index
     * @return array
     */
    private function validateRow(array $row, int $index): array
    {
        $validator = Validator::make($row, [
            'category' => 'required|string|max:255',
            'product_name' => 'required|string|max:255',
            'price_cents' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'available_for_delivery' => 'nullable',
            'available_for_pickup' => 'nullable',
            'image_url' => 'nullable|max:500',
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'error' => [
                    'row' => $index + 2,
                    'data' => $row,
                    'error' => $validator->errors()->first(),
                ],
            ];
        }

        return ['valid' => true];
    }

    /**
     * Prepare menu item data from CSV row
     *
     * @param array $row
     * @param int $restaurantId
     * @return array
     */
    private function prepareMenuItemData(array $row, int $restaurantId): array
    {
        return [
            'restaurant_id' => $restaurantId,
            'category' => trim($row['category']),
            'product_name' => trim($row['product_name']),
            'description' => !empty($row['description']) ? trim($row['description']) : null,
            'price_cents' => (int) $row['price_cents'],
            'available_for_delivery' => $this->parseBooleanValue($row['available_for_delivery'] ?? true),
            'available_for_pickup' => $this->parseBooleanValue($row['available_for_pickup'] ?? true),
            'is_active' => true,
            'image_url' => !empty($row['image_url']) ? trim($row['image_url']) : null,
            'sort_order' => 0,
        ];
    }

    /**
     * Parse boolean value from CSV (handles 1/0, true/false, yes/no, TRUE/FALSE)
     *
     * @param mixed $value
     * @return bool
     */
    private function parseBooleanValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));
        return in_array($value, ['1', 'true', 'yes', 'y', 'si', 'sì'], true);
    }

    /**
     * Import from CSV file path
     *
     * @param int $restaurantId
     * @param string $filePath
     * @param int|null $userId
     * @return array
     */
    public function importFromCsvFile(
        int $restaurantId,
        string $filePath,
        ?int $userId = null
    ): array {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $rows = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \Exception("Failed to open file: {$filePath}");
        }

        // Read header
        $header = fgetcsv($handle, 0, ',');

        if ($header === false) {
            fclose($handle);
            throw new \Exception("Failed to read CSV header");
        }

        // Normalize header
        $header = array_map(function ($col) {
            return trim(strtolower(str_replace(' ', '_', $col)));
        }, $header);

        // Read rows
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($row) === count($header)) {
                $rows[] = array_combine($header, $row);
            }
        }

        fclose($handle);

        return $this->importMenuItemsFromCsv(
            $restaurantId,
            $rows,
            $userId,
            basename($filePath)
        );
    }

    /**
     * Get import history for a restaurant
     *
     * @param int $restaurantId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getImportHistory(int $restaurantId, int $limit = 10)
    {
        return MenuImport::where('restaurant_id', $restaurantId)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
