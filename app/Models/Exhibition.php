<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int|null $museum_id
 * @property int|null $qr_code_id
 * @property array $name
 * @property array|null $description
 * @property array|null $credits
 * @property int|null $audio_id
 * @property \Carbon\CarbonInterface|null $start_date
 * @property \Carbon\CarbonInterface|null $end_date
 * @property bool $is_archived
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Museum|null $museum
 * @property-read QrCode|null $qrCode
 * @property-read Media|null $audio
 */
class Exhibition extends Model
{
    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'museum_id',
        'qr_code_id',
        'name',
        'description',
        'credits',
        'audio_id',
        'start_date',
        'end_date',
        'is_archived',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public $translatable = [
        'name',
        'description',
        'credits',
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
            'credits' => 'array',
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
     * Get the QR code for this exhibition.
     */
    public function qrCode(): BelongsTo
    {
        return $this->belongsTo(QrCode::class);
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
