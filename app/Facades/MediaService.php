<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\Media createMedia(string $type, array $files, array $titles, ?array $descriptions = null, string $disk = 'public', ?string $folder = null)
 * @method static \App\Models\Media createMediaFromUrls(string $type, array $urls, array $titles, ?array $descriptions = null)
 * @method static \App\Models\Media updateMedia(int $mediaId, ?array $files = null, ?array $titles = null, ?array $descriptions = null, string $disk = 'public', ?string $folder = null)
 * @method static bool deleteMedia(int $mediaId, string $disk = 'public')
 * @method static void attachToMuseum(int $museumId, int $mediaId)
 * @method static int detachFromMuseum(int $museumId, int $mediaId)
 * @method static void attachToExhibition(int $exhibitionId, int $mediaId)
 * @method static int detachFromExhibition(int $exhibitionId, int $mediaId)
 * @method static void attachToPost(int $postId, int $mediaId)
 * @method static int detachFromPost(int $postId, int $mediaId)
 * @method static void syncMuseumImages(int $museumId, array $mediaIds)
 * @method static void syncExhibitionImages(int $exhibitionId, array $mediaIds)
 * @method static void syncPostImages(int $postId, array $mediaIds)
 * @method static \Illuminate\Database\Eloquent\Collection getMediaByType(string $type)
 * @method static array formatMediaForFrontend(?\App\Models\Media $media)
 * @method static bool validateFileType(\Illuminate\Http\UploadedFile $file, string $type)
 * @method static array getAllowedExtensions(string $type)
 * @method static int getMaxFileSize(string $type)
 *
 * @see \App\Services\MediaService
 */
class MediaService extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \App\Services\MediaService::class;
    }
}
