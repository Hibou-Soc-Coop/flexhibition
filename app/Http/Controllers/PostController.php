<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Exhibition;
use App\Helpers\LanguageHelper;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $primaryLanguageCode = $primaryLanguage->code;

        $postRecords = Post::with(['exhibition', 'media'])->get();

        $posts = [];

        /** @var Post $postRecord */
        foreach ($postRecords as $postRecord) {
            $post = [];
            $post['id'] = $postRecord->id;
            $post['name'] = $postRecord->getTranslations('name');
            $post['description'] = $postRecord->getTranslations('description');
            $post['content'] = $postRecord->getTranslations('content');
            $post['exhibition_id'] = $postRecord->exhibition_id;
            $post['exhibition_name'] = $postRecord->exhibition ? ($postRecord->exhibition->getTranslations('name')[$primaryLanguageCode] ?? '') : '';

            $posts[] = $post;
        }

        return Inertia::render('backend/Posts/Index', [
            'posts' => $posts,
        ]);
    }

    public function create()
    {
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $exhibitions = Exhibition::all();
        $exhibitionsData = [];
        foreach ($exhibitions as $exhibition) {
            $exhibitionData = [];
            $exhibitionData['id'] = $exhibition->id;
            $exhibitionData['name'] = $exhibition->getTranslations('name');
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
            // Create the post
            $post = Post::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? [],
                'content' => $data['content'] ?? [],
                'exhibition_id' => $data['exhibition_id'] ?? null,
            ]);

            // Gestione Audio (Multi-file per lingua)
            if ($request->hasFile('audio.file')) {
                foreach ($request->file('audio.file') as $langCode => $file) {
                    $post->addMediaFromRequest("audio.file.{$langCode}")
                        ->withCustomProperties([
                            'lang' => $langCode,
                            'title' => $data['audio']['title'][$langCode] ?? null,
                            'description' => $data['audio']['description'][$langCode] ?? null,
                        ])
                        ->toMediaCollection('audio');
                }
            }

            // Gestione Immagini Gallery (Array di oggetti multi-file)
            if (!empty($data['images'])) {
                foreach ($data['images'] as $index => $imageData) {
                    $baseKey = "images.{$index}.file";

                    if ($request->hasFile($baseKey)) {
                        foreach ($request->file($baseKey) as $langCode => $file) {
                            $post->addMediaFromRequest("{$baseKey}.{$langCode}")
                                ->withCustomProperties([
                                    'lang' => $langCode,
                                    'title' => $imageData['title'][$langCode] ?? null,
                                    'description' => $imageData['description'][$langCode] ?? null,
                                    'group_index' => $index
                                ])
                                ->toMediaCollection('images');
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->route('posts.index')->with('success', 'Post creato con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Errore durante la creazione del post: ' . $e->getMessage()]);
        }
    }

    public function show(string $id)
    {
        $postRecord = Post::with('media')->findOrFail($id);
        $exhibitionRecord = Exhibition::find($postRecord->exhibition_id);

        $postName = $postRecord->getTranslations('name');
        $postDescription = $postRecord->getTranslations('description');
        $postContent = $postRecord->getTranslations('content');

        $postAudio = $postRecord->getMedia('audio');
        $postImages = $postRecord->getMedia('images');

        $exhibition = $postRecord->exhibition ? $exhibitionRecord->getTranslations('name') : null;

        $postData = [
            'id' => $postRecord->id,
            'name' => $postName,
            'description' => $postDescription,
            'content' => $postContent,
            'audio' => $postAudio,
            'images' => $postImages,
            'exhibition_name' => $exhibition,
        ];

        return Inertia::render('backend/Posts/Show', [
            'post' => $postData,
        ]);
    }

    public function edit($id)
    {
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $postRecord = Post::with('media')->findOrFail($id);
        $exhibitionRecord = Exhibition::find($postRecord->exhibition_id);

        $postName = $postRecord->getTranslations('name');
        $postDescription = $postRecord->getTranslations('description');
        $postContent = $postRecord->getTranslations('content');

        $postAudio = $postRecord->getMedia('audio');
        $postImages = $postRecord->getMedia('images');

        $exhibition = $postRecord->exhibition ? $exhibitionRecord->getTranslations('name') : null;

        $postData = [
            'id' => $postRecord->id,
            'name' => $postName,
            'description' => $postDescription,
            'content' => $postContent,
            'audio' => $postAudio,
            'images' => $postImages,
            'exhibition_name' => $exhibition,
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

    public function update(UpdatePostRequest $request, string $id)
    {
        $data = $request->validated();
        $post = Post::findOrFail($id);

        DB::beginTransaction();
        try {
            $post->update([
                'name' => $data['name'] ?? $post->name,
                'description' => $data['description'] ?? $post->description,
                'content' => $data['content'] ?? $post->content,
                'exhibition_id' => $data['exhibition_id'] ?? $post->exhibition_id,
            ]);

            // Gestione Audio
            if (isset($data['audio'])) {
                $this->syncLocalizedMedia($post, 'audio', $data['audio'], $request->file('audio.file'));
            }

            // Gestione Immagini Gallery
            if (isset($data['images'])) {
                foreach ($data['images'] as $index => $imageData) {
                    // Gestione Cancellazione
                    if (!empty($imageData['to_delete']) && !empty($imageData['id'])) {
                        $media = Media::find($imageData['id']);
                        if ($media) {
                            $media->delete();
                        }
                        continue;
                    }

                    $baseKey = "images.{$index}.file";
                    $uploadedFiles = $request->file($baseKey) ?? [];

                    // Gestione Nuovi File / Sostituzioni per gruppo
                    foreach ($uploadedFiles as $langCode => $file) {
                        $post->addMediaFromRequest("{$baseKey}.{$langCode}")
                            ->withCustomProperties([
                                'lang' => $langCode,
                                'title' => $imageData['title'][$langCode] ?? null,
                                'description' => $imageData['description'][$langCode] ?? null,
                                'group_index' => $index // Manteniamo index per raggruppamento logico
                            ])
                            ->toMediaCollection('images');
                    }

                    // Aggiornamento metadati per file esistenti
                    if (!empty($imageData['id'])) {
                        $media = Media::find($imageData['id']);
                        if ($media) {
                            $lang = $media->getCustomProperty('lang');
                            if ($lang && isset($imageData['title'][$lang])) {
                                $media->setCustomProperty('title', $imageData['title'][$lang]);
                                $media->setCustomProperty('description', $imageData['description'][$lang]);
                                $media->save();
                            }
                        }
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('posts.index')
                ->with('success', 'Post aggiornato con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Errore durante l\'aggiornamento del post: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $post->delete();

        return redirect()->route('posts.index')->with('success', 'Post eliminato con successo.');
    }

    /**
     * Sincronizza i media localizzati (Audio).
     */
    private function syncLocalizedMedia($model, $collection, $data, $files)
    {
        // Cancellazione totale
        if (!empty($data['to_delete'])) {
            $model->clearMediaCollection($collection);
            return;
        }

        $files = $files ?? [];

        // 1. Gestione nuovi file (Sostituzione per lingua)
        foreach ($files as $lang => $file) {
            // Rimuovi media esistente per questa lingua
            $existing = $model->getMedia($collection, ['lang' => $lang])->first();
            if ($existing) {
                $existing->delete();
            }

            // Aggiungi nuovo
            $model->addMedia($file)
                ->withCustomProperties([
                    'lang' => $lang,
                    'title' => $data['title'][$lang] ?? null,
                    'description' => $data['description'][$lang] ?? null
                ])
                ->toMediaCollection($collection);
        }

        // 2. Aggiornamento metadati per lingue senza nuovo file
        if (isset($data['title'])) {
            foreach ($data['title'] as $lang => $title) {
                if (isset($files[$lang]))
                    continue; // Già gestito sopra

                $existing = $model->getMedia($collection, ['lang' => $lang])->first();
                if ($existing) {
                    $existing->setCustomProperty('title', $title);
                    $existing->setCustomProperty('description', $data['description'][$lang] ?? null);
                    $existing->save();
                }
            }
        }
    }
}
