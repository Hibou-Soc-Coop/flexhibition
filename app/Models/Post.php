<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property array $name
 * @property array|null $content
 * @property array|null $description
 * @property int|null $audio_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Media|null $audio
 */
class Post extends Model
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
        'content',
        'audio_id',
        'exhibition_id',
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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name' => 'array',
            'content' => 'array',
            'description' => 'array',
        ];
    }
      public function Exhibition(): BelongsTo
    {
        return $this->belongsTo(Exhibition::class, 'exhibition_id');
    }

    /**
     * Get the audio media.
     */
    public function audio(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'audio_id');
    }

    /**
     * Get all images for this post.
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'post_images');
    }

    /**
     * Get all exhibitions that include this post.
     */
    // public function exhibitions(): BelongsToMany
    // {
    //     return $this->belongsToMany(Exhibition::class, 'exhibition_posts');
    // }
}
