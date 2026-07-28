<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KnowledgeArticle extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'category',
        'tags', 'author_id', 'is_published', 'published_at', 'view_count',
    ];

    protected $casts = [
        'tags'         => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public static array $categories = [
        'crop-disease'      => 'Crop Disease',
        'livestock-disease' => 'Livestock Disease',
        'pest-management'   => 'Pest Management',
        'soil-health'       => 'Soil Health',
        'farming-practices' => 'Farming Practices',
        'market-prices'     => 'Market & Prices',
        'general'           => 'General',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public static function generateSlug(string $title): string
    {
        $slug = Str::slug($title);
        $count = static::where('slug', 'like', $slug . '%')->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }

    public function getCategoryLabelAttribute(): string
    {
        return static::$categories[$this->category] ?? ucfirst($this->category);
    }
}
