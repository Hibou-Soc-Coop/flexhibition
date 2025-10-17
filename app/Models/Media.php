<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property string $type
 * @property array $url
 * @property array $title
 * @property array|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Media extends Model
{
    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'url',
        'title',
        'description',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'url',
        'title',
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
            'url' => 'array',
            'title' => 'array',
            'description' => 'array',
        ];
    }

    /**
     * Get all museums that use this media as logo.
     */
    public function museumsAsLogo(): BelongsToMany
    {
        return $this->belongsToMany(Museum::class, 'museum_images');
    }

    /**
     * Get all museums associated with this media.
     */
    public function museums(): BelongsToMany
    {
        return $this->belongsToMany(Museum::class, 'museum_images');
    }

    /**
     * Get all posts associated with this media.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_images');
    }

    /**
     * Get all exhibitions associated with this media.
     */
    public function exhibitions(): BelongsToMany
    {
        return $this->belongsToMany(Exhibition::class, 'exhibition_images');
    }
}
