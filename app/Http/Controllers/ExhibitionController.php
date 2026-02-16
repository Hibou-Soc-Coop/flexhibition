<?php

namespace App\Http\Controllers;

use App\Helpers\MediaHelper;
use App\Models\Exhibition;
use App\Models\Museum;
use App\Helpers\LanguageHelper;
use App\Http\Requests\StoreExhibitionRequest;
use App\Http\Requests\UpdateExhibitionRequest;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ExhibitionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $exhibitionRecords = Exhibition::with(['museum', 'media'])->get();

        $exhibitions = [];

        /** @var Exhibition $exhibitionRecord */
        foreach ($exhibitionRecords as $exhibitionRecord) {
            $exhibition = [];
            $exhibition['id'] = $exhibitionRecord->id;
            $exhibition['name'] = $exhibitionRecord->getTranslations('name');
            $exhibition['description'] = $exhibitionRecord->getTranslations('description');
            $exhibition['start_date'] = $exhibitionRecord->start_date?->format('Y-m-d');
            $exhibition['end_date'] = $exhibitionRecord->end_date?->format('Y-m-d');
            $exhibition['is_archived'] = $exhibitionRecord->is_archived;
            $exhibition['museum'] = [
                'id' => $exhibitionRecord->museum?->id,
                'name' => $exhibitionRecord->museum ? $exhibitionRecord->museum->getTranslations('name') : null,
            ];

            $exhibitions[] = $exhibition;
        }

        return Inertia::render('backend/Exhibitions/Index', [
            'exhibitions' => $exhibitions,
        ]);
    }

    public function create()
    {
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $museums = Museum::all();
        $museumsData = [];
        foreach ($museums as $museum) {
            $museumData = [];
            $museumData['id'] = $museum->id;
            $museumData['name'] = $museum->getTranslations('name');
            $museumsData[] = $museumData;
        }

        return Inertia::render('backend/Exhibitions/Create', [
            'museums' => $museumsData,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExhibitionRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            // Create the exhibition
            $exhibition = Exhibition::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? [],
                'start_date' => (! empty($data['start_date']) && $data['start_date'] !== '') ? $data['start_date'] : null,
                'end_date' => (! empty($data['end_date']) && $data['end_date'] !== '') ? $data['end_date'] : null,
                'is_archived' => $data['is_archived'] ?? false,
                'museum_id' => $data['museum_id'] ?? null,
            ]);

            // Gestione Audio (Multi-file per lingua) e.g. audio[it][file], audio[it][title]
            if (! empty($data['audio'])) {
                MediaHelper::syncAudioFiles($exhibition, $data['audio']);
            }

            // Gestione Immagini Gallery (Array di oggetti multi-file)
            if (! empty($data['images'])) {
                MediaHelper::syncGalleryFiles($exhibition, $data['images']);
            }

            DB::commit();

            return redirect()->route('exhibitions.index')->with('success', 'Collezione creata con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Errore durante la creazione della collezione: '.$e->getMessage()]);
        }
    }

    public function show(string $id)
    {
        $exhibitionRecord = Exhibition::with('media')->findOrFail($id);
        $museumRecord = Museum::find($exhibitionRecord->museum_id);

        $exhibitionName = $exhibitionRecord->getTranslations('name');
        $exhibitionDescription = $exhibitionRecord->getTranslations('description');

        $audio = $exhibitionRecord->getMedia('audio');
        $exhibitionAudio = $audio->map(fn ($media) => [
            $media->getCustomProperty('lang') => [
                'url' => $media->getUrl(),
            ]
        ])->collapse()->toArray();


        $images = $exhibitionRecord->getMedia('images');
        $exhibitionImages = $images->map(fn ($media) => [
            'url' => $media->getUrl(),
            'title' => $media->getCustomProperty('title'),
            'caption' => $media->getCustomProperty('caption'),
            'group_index' => $media->getCustomProperty('group_index'),
        ])->groupBy('group_index')->toArray();

        $exhibitionStartDate = $exhibitionRecord->start_date?->format('Y-m-d');
        $exhibitionEndDate = $exhibitionRecord->end_date?->format('Y-m-d');
        $museum = $exhibitionRecord->museum ? $museumRecord->getTranslations('name') : null;

        $exhibitionData = [
            'id' => $exhibitionRecord->id,
            'name' => $exhibitionName,
            'description' => $exhibitionDescription,
            'audio' => $exhibitionAudio,
            'images' => $exhibitionImages,
            'start_date' => $exhibitionStartDate,
            'end_date' => $exhibitionEndDate,
            'museum_id' => $museumRecord?->id,
        ];

        return Inertia::render('backend/Exhibitions/Show', [
            'exhibition' => $exhibitionData,
            'museum' => [
                'id' => $museumRecord?->id,
                'name' => $museumRecord?->getTranslations('name'),
            ],
        ]);
    }

    public function edit($id)
    {
        $exhibitionRecord = Exhibition::with('media')->findOrFail($id);

        $exhibitionName = $exhibitionRecord->getTranslations('name');
        $exhibitionDescription = $exhibitionRecord->getTranslations('description');

        $audio = $exhibitionRecord->getMedia('audio');
        $exhibitionAudio = $audio->map(fn ($media) => [
            $media->getCustomProperty('lang') => [
                'url' => $media->getUrl(),
            ]
        ])->collapse()->toArray();


        $images = $exhibitionRecord->getMedia('images');
        $exhibitionImages = $images->map(fn ($media) => [
            'url' => $media->getUrl(),
            'title' => $media->getCustomProperty('title'),
            'caption' => $media->getCustomProperty('caption'),
            'group_index' => $media->getCustomProperty('group_index'),
        ])->groupBy('group_index')->toArray();

        $exhibitionStartDate = $exhibitionRecord->start_date?->format('Y-m-d');
        $exhibitionEndDate = $exhibitionRecord->end_date?->format('Y-m-d');

        $exhibitionData = [
            'id' => $exhibitionRecord->id,
            'name' => $exhibitionName,
            'description' => $exhibitionDescription,
            'audio' => $exhibitionAudio,
            'images' => $exhibitionImages,
            'start_date' => $exhibitionStartDate,
            'end_date' => $exhibitionEndDate,
            'is_archived' => $exhibitionRecord->is_archived,
            'museum_id' => $exhibitionRecord->museum_id,
        ];

        $museums = Museum::all();
        $museumsData = [];
        foreach ($museums as $museum) {
            $museumData = [];
            $museumData['id'] = $museum->id;
            $museumData['name'] = $museum->getTranslations('name');
            $museumsData[] = $museumData;
        }

        return Inertia::render('backend/Exhibitions/Edit', [
            'exhibition' => $exhibitionData,
            'museums' => $museumsData,
        ]);
    }

    public function update(UpdateExhibitionRequest $request, string $id)
    {
        $data = $request->validated();
        $exhibition = Exhibition::findOrFail($id);

        DB::beginTransaction();
        try {
            $exhibition->update([
                'name' => $data['name'] ?? $exhibition->name,
                'description' => $data['description'] ?? $exhibition->description,
                'start_date' => (! empty($data['start_date']) && $data['start_date'] !== '') ? $data['start_date'] : null,
                'end_date' => (! empty($data['end_date']) && $data['end_date'] !== '') ? $data['end_date'] : null,
                'is_archived' => $data['is_archived'] ?? false,
                'museum_id' => $data['museum_id'] ?? $exhibition->museum_id,
            ]);

            // Gestione Audio
            if (isset($data['audio'])) {
                MediaHelper::syncAudioFiles($exhibition, $data['audio']);
            }

            // Gestione Immagini Gallery
            if (isset($data['images'])) {
                MediaHelper::syncGalleryFiles($exhibition, $data['images']);
            }

            DB::commit();

            return redirect()
                ->route('exhibitions.index')
                ->with('success', 'Mostra aggiornata con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Errore durante l\'aggiornamento della mostra: '.$e->getMessage()]);
        }
    }


    public function destroy($id)
    {
        $exhibition = Exhibition::findOrFail($id);
        $exhibition->delete();

        return redirect()->route('exhibitions.index')->with('success', 'Exhibition deleted successfully.');
    }
}
