<?php

namespace App\Http\Controllers;

use App\Helpers\MediaHelper;
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

        $postRecords = Post::with(['exhibition'])->get();

        $posts = [];

        /** @var Post $postRecord */
        foreach ($postRecords as $postRecord) {
            $post = [];
            $post['id'] = $postRecord->id;
            $post['name'] = $postRecord->getTranslations('name');
            $post['description'] = $postRecord->getTranslations('description');
            $post['exhibition_id'] = $postRecord->exhibition?->id ?? null;
            $post['exhibition_name'] = $postRecord->exhibition?->getTranslations('name') ?? '';
            $post['images'] = $postRecord->getMedia('images')->map(function (Media $media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getUrl('thumb'),
                ];
            })->toArray();

            $posts[] = $post;
        }

        return Inertia::render('backend/Posts/Index', [
            'posts' => $posts,
        ]);
    }

    public function create()
    {
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

            // Gestione Audio (Multi-file per lingua) e.g. audio[it][file], audio[it][title]
            if (! empty($data['audio'])) {
                MediaHelper::syncAudioFiles($post, $data['audio']);
            }

            // Gestione Immagini Gallery (Array di oggetti multi-file)
            if (! empty($data['images'])) {
                MediaHelper::syncGalleryFiles($post, $data['images']);
            }

            DB::commit();

            return redirect()->route('posts.index')->with('success', 'Post creato con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Errore durante la creazione del post: '.$e->getMessage()]);
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

        $exhibition = [
            'id' => $exhibitionRecord?->id ?? null,
            'name' => $exhibitionRecord?->getTranslations('name') ?? null,
        ];

        $postData = [
            'id' => $postRecord->id,
            'name' => $postName,
            'description' => $postDescription,
            'content' => $postContent,
            'audio' => $postAudio,
            'images' => $postImages,
            'exhibition_id' => $postRecord->exhibition_id,
        ];

        return Inertia::render('backend/Posts/Show', [
            'post' => $postData,
            'exhibition' => $exhibition,
        ]);
    }

    public function edit($id)
    {
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $postRecord = Post::findOrFail($id);
        $exhibitionRecord = Exhibition::find($postRecord->exhibition_id);

        $postName = $postRecord->getTranslations('name');
        $postDescription = $postRecord->getTranslations('description');
        $postContent = $postRecord->getTranslations('content');

        $postAudio = $postRecord->getMedia('audio');
        $postImages = $postRecord->getMedia('images');

        $postData = [
            'id' => $postRecord->id,
            'name' => $postName,
            'description' => $postDescription,
            'content' => $postContent,
            'audio' => $postAudio,
            'images' => $postImages,
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
                MediaHelper::syncAudioFiles($post, $data['audio']);
            }

            // Gestione Immagini Gallery
            if (isset($data['images'])) {
                MediaHelper::syncGalleryFiles($post, $data['images']);
            }

            DB::commit();

            return redirect()
                ->route('posts.index')
                ->with('success', 'Post aggiornato con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Errore durante l\'aggiornamento del post: '.$e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $post = Post::findOrFail($id);
        $post->delete();

        return redirect()->route('posts.index')->with('success', 'Post eliminato con successo.');
    }
}
