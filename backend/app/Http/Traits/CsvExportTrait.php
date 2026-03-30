<?php

namespace App\Http\Traits;

trait CsvExportTrait
{
    /**
     * Stream a CSV response with BOM UTF-8 and semicolon separator (Excel IT compatible).
     *
     * @param array $data  Array of associative arrays (keys = column headers)
     * @param string $filename  Name of the downloaded file
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function streamCsv(array $data, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // BOM UTF-8 for Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if (!empty($data)) {
                // Header row
                fputcsv($file, array_keys($data[0]), ';');

                // Data rows
                foreach ($data as $row) {
                    fputcsv($file, $row, ';');
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
