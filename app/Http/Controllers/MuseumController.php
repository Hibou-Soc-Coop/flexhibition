<?php

namespace App\Http\Controllers;

use App\Models\Museum;
use App\Helpers\LanguageHelper;
use App\Http\Requests\StoreMuseumRequest;
use App\Http\Requests\UpdateMuseumRequest;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use \Spatie\MediaLibrary\MediaCollections\Models\Media;

class MuseumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $primaryLanguageCode = $primaryLanguage->code;
        $maxMuseums = 2;

        $museumRecords = Museum::with('logo')->get();

        $museums = [];

        foreach ($museumRecords as $museumRecord) {
            $museum = [];
            $museum['id'] = $museumRecord->id;
            $museum['name'] = $museumRecord->getTranslations('name');
            $museum['description'] = $museumRecord->getTranslations('description');
            $museum['logo']['url'] = $museumRecord->logo ? $museumRecord->logo->getTranslations('url') : null;
            $museum['logo']['title'] = $museumRecord->logo ? $museumRecord->logo->getTranslations('title') : null;
            $museum['logo']['description'] = $museumRecord->logo ? $museumRecord->logo->getTranslations('description') : null;
            $museum['audio']['url'] = $museumRecord->audio ? $museumRecord->audio->getTranslations('url') : null;
            $museum['audio']['title'] = $museumRecord->audio ? $museumRecord->audio->getTranslations('title') : null;
            $museum['audio']['description'] = $museumRecord->audio ? $museumRecord->audio->getTranslations('description') : null;

            $museum['images'] = $museumRecord->images->map(function ($image) use ($primaryLanguageCode) {
                return [
                    'media_url' => $image->media ? $image->media->media_url : null,
                    'title' => $image->media ? $image->media->title : null,
                    'description' => $image->media ? $image->media->description : null,
                ];
            })->toArray();

            $museums[] = $museum;
        }

        return Inertia::render('backend/Museums/Index', [
            'museums' => $museums,
            'maxMuseum' => $maxMuseums,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $museums = Museum::count();
        //$maxMuseums = Settings::get('max_museum_records');
        $maxMuseums = 2;

        if ($museums >= $maxMuseums) {
            return redirect()->route('museums.index')->with('error', 'Non è possibile creare ulteriori musei.');
        }
        return Inertia::render('backend/Museums/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMuseumRequest $request)
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            // Crea il museo
            $museum = Museum::create([
                'name' => $data['name'],
                'description' => $data['description'],
            ]);

            // Gestione Logo (Multi-file per lingua)
            if ($request->hasFile('logo.file')) {
                foreach ($request->file('logo.file') as $langCode => $file) {
                    $museum->addMediaFromRequest("logo.file.{$langCode}")
                        ->withCustomProperties([
                            'lang' => $langCode,
                            'title' => $data['logo']['title'][$langCode] ?? null,
                            'description' => $data['logo']['description'][$langCode] ?? null,
                        ])
                        ->toMediaCollection('logo');
                }
            }

            // Gestione Audio (Multi-file per lingua)
            if ($request->hasFile('audio.file')) {
                foreach ($request->file('audio.file') as $langCode => $file) {
                    $museum->addMediaFromRequest("audio.file.{$langCode}")
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
                            $museum->addMediaFromRequest("{$baseKey}.{$langCode}")
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

            return redirect()
                ->route('museums.index')
                ->with('success', 'Museo creato con successo.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Errore durante la creazione del museo: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $museumRecord = Museum::findOrFail($id);

        $museumId = $museumRecord->id;
        $museumName = $museumRecord->getTranslations('name');
        $museumDescription = $museumRecord->getTranslations('description');
        $museumLogo = $museumRecord->logo;
        $museumAudio = $museumRecord->audio;
        $museumImages = $museumRecord->images;

        $museumData = [
            'id' => $museumId,
            'name' => $museumName,
            'description' => $museumDescription,
            'logo' => $museumLogo,
            'audio' => $museumAudio,
            'images' => $museumImages,
        ];

        return Inertia::render('backend/Museums/Show', [
            'museum' => $museumData,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $museumRecord = Museum::findOrFail($id);

        $museumId = $museumRecord->id;
        $museumName = $museumRecord->getTranslations('name');
        $museumDescription = $museumRecord->getTranslations('description');
        $museumLogo = $museumRecord->logo;
        $museumAudio = $museumRecord->audio;
        $museumImages = $museumRecord->images;

        $museumData = [
            'id' => $museumId,
            'name' => $museumName,
            'description' => $museumDescription,
            'logo' => $museumLogo,
            'audio' => $museumAudio,
            'images' => $museumImages,
        ];

        return Inertia::render('backend/Museums/Edit', [
            'museum' => $museumData,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMuseumRequest $request, string $id)
    {
        $data = $request->validated();
        $museum = Museum::findOrFail($id);

        DB::beginTransaction();

        try {
            // Aggiorna i dati base del museo
            $museum->update([
                'name' => $data['name'] ?? $museum->name,
                'description' => $data['description'] ?? $museum->description,
            ]);

            // Gestione Logo
            if (isset($data['logo'])) {
                $this->syncLocalizedMedia($museum, 'logo', $data['logo'], $request->file('logo.file'));
            }

            // Gestione Audio
            if (isset($data['audio'])) {
                $this->syncLocalizedMedia($museum, 'audio', $data['audio'], $request->file('audio.file'));
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
                        $museum->addMediaFromRequest("{$baseKey}.{$langCode}")
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
                ->route('museums.index')
                ->with('success', 'Museo aggiornato con successo.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Errore durante l\'aggiornamento del museo: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $museum = Museum::findOrFail($id);
        $museum->delete();
        return redirect()->route('museums.index')->with('success', 'Museo eliminato con successo.');
    }

    /**
     * Sincronizza i media localizzati (Logo, Audio).
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
