<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Museum extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->registerMediaConversions(function (Media $media) {
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
     * Get all exhibitions for this museum.
     */
    public function exhibitions(): HasMany
    {
        return $this->hasMany(Exhibition::class);
    }
}
