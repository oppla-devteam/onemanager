<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'html_template',
        'required_fields',
        'is_active',
        'category',
    ];

    protected $casts = [
        'required_fields' => 'array',
        'is_active' => 'boolean',
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'template_id');
    }

    /**
     * Compila il template con i dati forniti
     */
    public function compile(array $data): string
    {
        $html = $this->html_template;
        
        foreach ($data as $key => $value) {
            $html = str_replace("{{" . $key . "}}", $value, $html);
        }
        
        return $html;
    }

    /**
     * Valida che tutti i campi richiesti siano presenti
     */
    public function validateRequiredFields(array $data): array
    {
        $missing = [];
        
        foreach ($this->required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        return $missing;
    }
}
