<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Exhibition extends Model
{
    use HasTranslations;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'museum_id',
        'name',
        'description',
        'credits',
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
            'start_date' => 'date',
            'end_date' => 'date',
            'is_archived' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->registerMediaConversions(function (Media $media) {
            $this->addMediaConversion('thumb')
                ->width(config('media.dimensions.thumbnail.width'))
                ->height(config('media.dimensions.thumbnail.height'))
                ->sharpen(10)
                ->nonQueued();
            $this->addMediaConversion(name: 'full')
                ->width(config('media.dimensions.full.width'))
                ->height(config('media.dimensions.full.height'))
                ->sharpen(10)
                ->nonQueued();
        });
        $this->addMediaCollection('audio');
        $this->addMediaCollection('qrcode');
    }

    /**
     * Get the museum that owns this exhibition.
     */
    public function museum(): BelongsTo
    {
        return $this->belongsTo(Museum::class);
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
