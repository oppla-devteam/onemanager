<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'category',
        'description',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function clients()
    {
        return $this->morphedByMany(Client::class, 'taggable');
    }

    public function leads()
    {
        return $this->morphedByMany(Lead::class, 'taggable');
    }

    public function opportunities()
    {
        return $this->morphedByMany(Opportunity::class, 'taggable');
    }
}
