<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string|null $name
 * @property string $type
 * @property int|null $museum_id
 * @property int|null $qr_image_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Museum|null $museum
 * @property-read Media|null $qrImage
 * @property-read Post|null $post
 * @property-read Exhibition|null $exhibition
 * @property-read string|null $url
 */
class QrCode extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'museum_id',
        'qr_image_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => 'string',
        ];
    }

    /**
     * Get the museum that owns this QR code.
     */
    public function museum(): BelongsTo
    {
        return $this->belongsTo(Museum::class);
    }

    /**
     * Get the QR image media.
     */
    public function qrImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'qr_image_id');
    }

    /**
     * Get the post associated with this QR code.
     */
    public function post(): HasOne
    {
        return $this->hasOne(Post::class);
    }

    /**
     * Get the exhibition associated with this QR code.
     */
    public function exhibition(): HasOne
    {
        return $this->hasOne(Exhibition::class);
    }

    /**
     * Get the dynamic URL for this QR code.
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                try {
                    if ($this->post) {
                        return route('posts.show', $this->post);
                    }

                    if ($this->exhibition) {
                        return route('exhibitions.show', $this->exhibition);
                    }
                } catch (\Exception $e) {
                    // Routes not defined yet, return placeholder
                    if ($this->post) {
                        return url("/posts/{$this->post->id}");
                    }
                    if ($this->exhibition) {
                        return url("/exhibitions/{$this->exhibition->id}");
                    }
                }

                return null;
            }
        );
    }

    /**
     * Check if this QR code is assigned.
     */
    public function isAssigned(): bool
    {
        return $this->post()->exists() || $this->exhibition()->exists();
    }

    /**
     * Check if this QR code is available.
     */
    public function isAvailable(): bool
    {
        return !$this->isAssigned();
    }
}
