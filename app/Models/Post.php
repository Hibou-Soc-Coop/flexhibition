<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
class Post extends Model implements HasMedia
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
        'content',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'name',
        'description',
        'content',
    ];

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

    public function getAudio(string $lang = null)
    {
        if (! $lang) {
            return $this->getMedia('audio')->first(fn (Media $media) => $media->getCustomProperty('lang') === app()->getLocale());
        } else {
            return $this->getMedia('audio')->first(fn (Media $media) => $media->getCustomProperty('lang') === $lang);
        }
    }

    public function exhibition(): BelongsTo
    {
        return $this->belongsTo(Exhibition::class);
    }
}
