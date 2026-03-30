<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ContractAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'name',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by_type',
        'uploaded_by_id',
        'description',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function uploadedBy()
    {
        return $this->morphTo();
    }

    /**
     * Ottieni URL file
     */
    public function getUrl(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Elimina file da storage
     */
    public function deleteFile(): bool
    {
        if (Storage::exists($this->file_path)) {
            return Storage::delete($this->file_path);
        }
        return true;
    }

    /**
     * Formato dimensione leggibile
     */
    public function getReadableSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }
}
