<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property array $name
 * @property array|null $description
 * @property int|null $logo_id
 * @property int|null $audio_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Media|null $logo
 * @property-read Media|null $audio
 */
class Museum extends Model
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
        'logo_id',
        'audio_id',
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
        ];
    }

    /**
     * Get the logo media.
     */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_id');
    }

    /**
     * Get the audio media.
     */
    public function audio(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'audio_id');
    }

    /**
     * Get all images for this museum.
     */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'museum_images');
    }

    /**
     * Get all QR codes for this museum.
     */
    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    /**
     * Get all exhibitions for this museum.
     */
    public function exhibitions(): HasMany
    {
        return $this->hasMany(Exhibition::class);
    }
}
