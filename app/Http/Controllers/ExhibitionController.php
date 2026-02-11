<?php

namespace App\Http\Controllers;

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
            $exhibition['museum_id'] = $exhibitionRecord->museum_id;
            $exhibition['museum_name'] = $exhibitionRecord->museum ?? $exhibitionRecord->museum->getTranslations('name');
            $exhibition['start_date'] = $exhibitionRecord->start_date?->format('Y-m-d');
            $exhibition['end_date'] = $exhibitionRecord->end_date?->format('Y-m-d');
            $exhibition['is_archived'] = $exhibitionRecord->is_archived;

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
                'start_date' => (!empty($data['start_date']) && $data['start_date'] !== '') ? $data['start_date'] : null,
                'end_date' => (!empty($data['end_date']) && $data['end_date'] !== '') ? $data['end_date'] : null,
                'is_archived' => $data['is_archived'] ?? false,
                'museum_id' => $data['museum_id'] ?? null,
            ]);

            // Gestione Audio (Multi-file per lingua)
            if ($request->hasFile('audio.file')) {
                foreach ($request->file('audio.file') as $langCode => $file) {
                    $exhibition->addMediaFromRequest("audio.file.{$langCode}")
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
                            $exhibition->addMediaFromRequest("{$baseKey}.{$langCode}")
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

            return redirect()->route('exhibitions.index')->with('success', 'Collezione creata con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Errore durante la creazione della collezione: ' . $e->getMessage()]);
        }
    }

    public function show(string $id)
    {
        $exhibitionRecord = Exhibition::with('media')->findOrFail($id);
        $museumRecord = Museum::find($exhibitionRecord->museum_id);

        $exhibitionName = $exhibitionRecord->getTranslations('name');
        $exhibitionDescription = $exhibitionRecord->getTranslations('description');

        $exhibitionAudio = $exhibitionRecord->getMedia('audio');
        $exhibitionImages = $exhibitionRecord->getMedia('images');

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
            'museum_name' => $museum,
        ];

        return Inertia::render('backend/Exhibitions/Show', [
            'exhibition' => $exhibitionData,
        ]);
    }

    public function edit($id)
    {
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $exhibitionRecord = Exhibition::with('media')->findOrFail($id);
        $museumRecord = Museum::find($exhibitionRecord->museum_id);

        $exhibitionName = $exhibitionRecord->getTranslations('name');
        $exhibitionDescription = $exhibitionRecord->getTranslations('description');

        $exhibitionAudio = $exhibitionRecord->getMedia('audio');
        $exhibitionImages = $exhibitionRecord->getMedia('images');

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
            'is_archived' => $exhibitionRecord->is_archived,
            'museum_name' => $museum,
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
                'start_date' => (!empty($data['start_date']) && $data['start_date'] !== '') ? $data['start_date'] : null,
                'end_date' => (!empty($data['end_date']) && $data['end_date'] !== '') ? $data['end_date'] : null,
                'is_archived' => $data['is_archived'] ?? false,
                'museum_id' => $data['museum_id'] ?? $exhibition->museum_id,
            ]);

            // Gestione Audio
            if (isset($data['audio'])) {
                $this->syncLocalizedMedia($exhibition, 'audio', $data['audio'], $request->file('audio.file'));
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
                        $exhibition->addMediaFromRequest("{$baseKey}.{$langCode}")
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
                ->route('exhibitions.index')
                ->with('success', 'Mostra aggiornata con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Errore durante l\'aggiornamento della mostra: ' . $e->getMessage()]);
        }
    }


    public function destroy($id)
    {
        $exhibition = Exhibition::findOrFail($id);
        $exhibition->delete();

        return redirect()->route('exhibitions.index')->with('success', 'Exhibition deleted successfully.');
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
