<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaHelper
{
    public static function syncGalleryFiles(Model $model, array $galleryData, string $collection = 'images'): void
    {
        $currentGallery = $model->getMedia($collection);
        $toDelete = $currentGallery->reject(fn (Media $m) => collect($galleryData)->pluck('id')->contains($m->id));
        $toDelete->each->delete();

        foreach ($galleryData as $index => $data) {
            if (isset($data['id'])) {
                $media = Media::find($data['id']);
                if ($media) {
                    $media->setCustomProperty('title', $data['title']);
                    $media->setCustomProperty('description', $data['description']);
                    $media->setCustomProperty('group_index', $index);
                    $media->save();
                }
            } elseif (! isset($data['id']) && isset($data['file']) && $data['file'] instanceof UploadedFile) {
                $model->addMediaFromRequest("{$collection}.{$index}.file")
                    ->withCustomProperties([
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'group_index' => $index,
                    ])
                    ->toMediaCollection($collection);
            }
        }
    }

    public static function syncAudioFiles(Model $model, array $audioData, string $collection = 'audio'): void
    {
        $currentAudio = $model->getMedia($collection);
        $toDelete = $currentAudio->reject(fn (Media $m) => collect($audioData)->pluck('id')->contains($m->id));
        $toDelete->each->delete();

        foreach ($audioData as $lang => $data) {
            if (! isset($data['id']) && isset($data['file']) && $data['file'] instanceof UploadedFile) {
                $model->addMediaFromRequest("{$collection}.{$lang}.file")
                    ->withCustomProperties(['lang' => $lang])
                    ->toMediaCollection($collection);
            }
        }
    }
}