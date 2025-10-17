<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property array $title
 * @property array|null $content
 * @property int|null $audio_id
 * @property int|null $qr_code_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Media|null $audio
 * @property-read QrCode|null $qrCode
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
        'title',
        'content',
        'audio_id',
        'qr_code_id',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'title',
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
            'title' => 'array',
            'content' => 'array',
        ];
    }

    /**
     * Get the audio media.
     */
    public function audio(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'audio_id');
    }

    /**
     * Get the QR code for this post.
     */
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
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
    public function exhibitions(): BelongsToMany
    {
        return $this->belongsToMany(Exhibition::class, 'exhibition_posts');
    }
}
