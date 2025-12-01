<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class Exhibition extends Model
{
    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'audio_id',
        'start_date',
        'end_date',
        'is_archived',
        'museum_id',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'name',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_archived' => 'boolean',
        ];
    }

    /**
     * Get the museum that owns this exhibition.
     */
    public function museum(): BelongsTo
    {
        return $this->belongsTo(Museum::class);
    }

    /**
     * Get the audio media.
     */
    public function audio(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'audio_id');
    }

    /**
     * Get all images for this exhibition.
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'exhibition_images');
    }

    /**
     * Get all posts in this exhibition.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'exhibition_posts');
    }

    /**
     * Check if the exhibition is currently active.
     */
    public function isActive(): bool
    {
        if ($this->is_archived) {
            return false;
        }

        $now = now();

        if ($this->start_date && $now->lessThan($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->greaterThan($this->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Scope a query to only include active exhibitions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Scope a query to only include archived exhibitions.
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }
}
