<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Exhibition;
use App\Models\Media;
use App\Models\QrCode;
use App\Helpers\LanguageHelper;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
// use App\Http\Requests\UpdatePostRequest;
use App\Services\MediaService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PostController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $primaryLanguageCode = $primaryLanguage->code;

        $postRecords = Post::with(['exhibition'])->get();

        $posts = [];

        foreach ($postRecords as $postRecord) {
            $post = [];
            $post['id'] = $postRecord->id;
            $post['name'] = $postRecord->getTranslations('name');
            $post['description'] = $postRecord->getTranslations('description');
            $post['content'] = $postRecord->getTranslations('content');
            $post['audio']['url'] = $postRecord->audio ? $postRecord->audio->getTranslations('url') : [];
            $post['audio']['title'] = $postRecord->audio ? $postRecord->audio->getTranslations('title') : [];
            $post['audio']['description'] = $postRecord->audio ? $postRecord->audio->getTranslations('description') : [];

            $post['images'] = $postRecord->images->map(function ($image) {
                return [
                    'url' => $image->getTranslations('url'),
                    'title' => $image->getTranslations('title'),
                    'description' => $image->getTranslations('description'),
                ];
            })->toArray();
            $post['exhibition_id'] = $postRecord->exhibition_id;

            $posts[] = $post;
        }

        return Inertia::render('backend/Posts/Index', [
            'posts' => $posts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();

        $exhibitions = Exhibition::all();
        $exhibitionsData = [];
        foreach ($exhibitions as $exhibition) {
            $exhibitionData = [];
            $exhibitionData['id'] = $exhibition->id;
            $exhibitionData['name'] = [
                $primaryLanguage->code => $exhibition->getTranslation('name', $primaryLanguage->code),
            ];
            $exhibitionsData[] = $exhibitionData;
        }
        return Inertia::render('backend/Posts/Create', [
            'exhibitions' => $exhibitionsData,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            // Create audio media if provided
            $audioId = isset($data['audio']['file'])
                ? $this->createMediaFromData($data['audio'], 'audio')?->id
                : null;

            // Create the post
            $post = Post::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? [],
                'content' => $data['content'] ?? [],
                'audio_id' => $audioId,
                'exhibition_id' => $data['exhibition_id'] ?? null,
            ]);

            // Handle images
            if (isset($data['images']) && !empty($data['images'])) {
                $imageIds = collect($data['images'])
                    ->map(function($img) {
                        return $this->createMediaFromData($img, 'image');
                    })
                    ->pluck('id')
                    ->toArray();

                $post->images()->attach($imageIds);
            }

            DB::commit();

            return redirect()->route('posts.index')->with('success', 'Post creato con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Errore durante la creazione del post: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $postRecord = Post::findOrFail($id);
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $primaryLanguageCode = $primaryLanguage->code;
        $exhibitionRecord = Exhibition::find($postRecord->exhibition_id);
        $postData = [
            'id' => $postRecord->id,
            'name' => $postRecord->getTranslations('name'),
            'description' => $postRecord->getTranslations('description'),
            'content' => $postRecord->getTranslations('content'),
            'audio' => $postRecord->audio,
            'images' => $postRecord->images,
            'exhibition_name' => $postRecord->exhibition ? $exhibitionRecord->getTranslation('name', $primaryLanguageCode) : null,
        ];

        return Inertia::render('backend/Posts/Show', [
            'post' => $postData,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $primaryLanguageCode = $primaryLanguage->code;

        $postRecord = Post::with(['exhibition'])->findOrFail($id);

        $postData = [
            'id' => $postRecord->id,
            'name' => $postRecord->getTranslations('name'),
            'description' => $postRecord->getTranslations('description'),
            'content' => $postRecord->getTranslations('content'),
            'audio' => $postRecord->audio,
            'images' => $postRecord->images,
            'exhibition_id' => $postRecord->exhibition_id,
        ];

        $exhibitions = Exhibition::all();
        $exhibitionsData = [];
        foreach ($exhibitions as $exhibition) {
            $exhibitionData = [];
            $exhibitionData['id'] = $exhibition->id;
            $exhibitionData['name'] = [
                $primaryLanguage->code => $exhibition->getTranslation('name', $primaryLanguage->code),
            ];
            $exhibitionsData[] = $exhibitionData;
        }

        return Inertia::render('backend/Posts/Edit', [
            'post' => $postData,
            'exhibitions' => $exhibitionsData,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, string $id)
    {
        $data = $request->validated();
        $post = Post::findOrFail($id);
        DB::beginTransaction();
        try{
            $post->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? [],
                'content' => $data['content'] ?? [],
                'exhibition_id' => $data['exhibition_id'] ?? null,
            ]);
            $audioId = $this->handleMediaUpdate(
                $data['audio'] ?? null,
                $post->audio_id,
                'audio'
            );
            if ($audioId !== $post->audio_id) {
                $post->update(['audio_id' => $audioId]);
            }

            // Gestione Immagini Gallery
            if (isset($data['images'])) {
                $this->handleGalleryUpdate($post, $data['images']);
            }

            DB::commit();
            return redirect()->route('posts.index')->with('success', 'Post aggiornato con successo.');


        }catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Errore durante l\'aggiornamento del post: ' . $e->getMessage()]);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        DB::beginTransaction();

        try {
            // Delete associated media
            if ($post->audio_id) {
                $this->mediaService->deleteMedia($post->audio_id);
            }

            // Delete associated images
            foreach ($post->images as $image) {
                $this->mediaService->deleteMedia($image->id);
            }

            $post->delete();

            DB::commit();

            return redirect()->route('posts.index')->with('success', 'Post eliminato con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Errore durante l\'eliminazione del post: ' . $e->getMessage()]);
        }
    }
    public function showPosts($museumId, $exhibitionId, $language = 'it')
    {
        $allPostsRecord = Post::where('exhibition_id', $exhibitionId)->get();
        $posts = [];
        foreach ($allPostsRecord as $postRecord) {
            $post = [];
            $post['id'] = $postRecord->id;
            $post['name'] = $postRecord->getTranslations('name');
            $post['description'] = $postRecord->getTranslations('description');
            $post['content'] = $postRecord->getTranslations('content');
            $post['audio'] = $postRecord->audio ? collect($postRecord->audio->getTranslations('url'))->map(fn($url) => asset('storage' . $url)) : null;
            $post['images'] = $postRecord->images?->map(function ($image) {
                return collect($image->getTranslations('url'))->map(fn($url) => asset('storage' . $url));
            });
            $post['exhibition_id'] = $exhibitionId;
            $posts[] = $post;
        }
        return Inertia::render('frontend/Posts', ['posts' => $posts]);
    }
    public function showPostDetail($museumId, $collectionId, $postId, $language = 'it')
    {
        $postRecord = Post::findOrFail($postId);
        $post = [];
        $post['id'] = $postRecord->id;
        $post['name'] = $postRecord->getTranslations('name');
        $post['description'] = $postRecord->getTranslations('description');
        $post['content'] = $postRecord->getTranslations('content');
        $post['audio'] = $postRecord->audio ? collect($postRecord->audio->getTranslations('url'))->map(fn($url) => asset('storage' . $url)) : null;
        $post['images'] = $postRecord->images?->map(function ($image) {
            return collect($image->getTranslations('url'))->map(fn($url) => asset('storage' . $url));
        });
        $post['exhibition_id'] = $postRecord->exhibition_id;

        return Inertia::render('frontend/Post', ['post' => $post]);
    }

    /**
     * Create media from request data.
     */
    private function createMediaFromData(array $data, string $type): ?Media
    {
        if (empty($data['file'])) {
            return null;
        }

        return $this->mediaService->createMedia(
            $type,
            $data['file'],
            $data['title'],
            $data['description'] ?? null,
            'public',
            'media'
        );
    }

    /**
     * Handle media update (create, update, or delete).
     */
    private function handleMediaUpdate(?array $data, ?int $currentMediaId, string $type): ?int
    {
        if (!$data) {
            return $currentMediaId;
        }

        // If deletion is requested
        if (!empty($data['to_delete']) && $currentMediaId) {
            $this->mediaService->deleteMedia($currentMediaId);
            return null;
        }

        // If there's a new file to upload
        if (!empty($data['file'])) {
            // If media already exists, update it
            if ($currentMediaId && !empty($data['id']) && $data['id'] == $currentMediaId) {
                $this->mediaService->updateMedia(
                    $currentMediaId,
                    $data['file'],
                    $data['title'] ?? null,
                    $data['description'] ?? null,
                    'public',
                    'media'
                );
                return $currentMediaId;
            } else {
                // Create new media and delete old if exists
                if ($currentMediaId) {
                    $this->mediaService->deleteMedia($currentMediaId);
                }
                $newMedia = $this->createMediaFromData($data, $type);
                return $newMedia?->id;
            }
        }

        // If only name/description updates without new file
        if ($currentMediaId && (isset($data['title']) || isset($data['description']))) {
            $this->mediaService->updateMedia(
                $currentMediaId,
                null,
                $data['title'] ?? null,
                $data['description'] ?? null,
                'public',
                'media'
            );
        }

        return $currentMediaId;
    }

    /**
     * Handle gallery images update.
     */
    private function handleGalleryUpdate(Post $post, array $imagesData): void
    {
        $currentImageIds = $post->images()->pluck('media.id')->toArray();
        $updatedImageIds = [];

        foreach ($imagesData as $imageData) {
            // If image should be deleted
            if (!empty($imageData['to_delete']) && !empty($imageData['id'])) {
                $this->mediaService->deleteMedia($imageData['id']);
                continue;
            }

            // If there's an existing ID
            if (!empty($imageData['id'])) {
                // Update if there's a new file or metadata
                if (!empty($imageData['file']) || isset($imageData['name']) || isset($imageData['description'])) {
                    $this->mediaService->updateMedia(
                        $imageData['id'],
                        $imageData['file'] ?? null,
                        $imageData['name'] ?? null,
                        $imageData['description'] ?? null,
                        'public',
                        'media'
                    );
                }
                $updatedImageIds[] = $imageData['id'];
            } elseif (!empty($imageData['file'])) {
                // Create a new image
                $newImage = $this->createMediaFromData($imageData, 'image');
                if ($newImage) {
                    $updatedImageIds[] = $newImage->id;
                }
            }
        }

        // Sync images (removes those no longer present)
        $post->images()->sync($updatedImageIds);

        // Delete media that are no longer associated
        $imagesToDelete = array_diff($currentImageIds, $updatedImageIds);
        foreach ($imagesToDelete as $imageId) {
            $this->mediaService->deleteMedia($imageId);
        }
    }
}
