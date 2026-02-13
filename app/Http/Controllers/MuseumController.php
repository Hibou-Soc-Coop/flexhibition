<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMuseumRequest;
use App\Http\Requests\UpdateMuseumRequest;
use App\Models\Museum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MuseumController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $maxMuseums = Settings::get('max_museum_records');
        $maxMuseums = 2;

        $museumRecords = Museum::with('media')->get();

        $museums = [];

        /** @var Museum $museumRecord */
        foreach ($museumRecords as $museumRecord) {
            $museum = [];
            $museum['id'] = $museumRecord->id;
            $museum['name'] = $museumRecord->getTranslations('name');
            $museum['description'] = $museumRecord->getTranslations('description');

            $logo = $museumRecord->getFirstMedia('logo');
            $museum['logo'] = [
                'url' => $logo->getUrl(),
                'title' => $logo->getCustomProperty('title'),
            ];

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
        // $maxMuseums = Settings::get('max_museum_records');
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
        //        dd($request->all());
        $data = $request->validated();

        DB::beginTransaction();

        try {
            // Crea il museo
            $museum = Museum::create([
                'name' => $data['name'],
                'description' => $data['description'],
            ]);

            // Gestione Logo
            $museum->addMediaFromRequest("logo.file")
                ->toMediaCollection('logo');

            // Gestione Audio (Multi-file per lingua) e.g. audio[it][file], audio[it][title]
            if (! empty($data['audio'])) {
                foreach ($data['audio'] as $langCode => $content) {
                    if ($request->hasFile("audio.{$langCode}.file")) {
                        $museum->addMediaFromRequest("audio.{$langCode}.file")
                            ->withCustomProperties([
                                'lang' => $langCode,
                            ])
                            ->toMediaCollection('audio');
                    }
                }
            }

            // Gestione Immagini Gallery (Array di oggetti multi-file)
            if (! empty($data['images'])) {
                foreach ($data['images'] as $index => $imageData) {
                    $baseKey = "images.{$index}.file";

                    if ($request->hasFile($baseKey)) {
                        $museum->addMediaFromRequest($baseKey)
                            ->withCustomProperties([
                                'title' => $imageData['title'],
                                'description' => $imageData['description'],
                                'group_index' => $index,
                            ])
                            ->toMediaCollection('images');
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
                ->with('error', 'Errore durante la creazione del museo: '.$e->getMessage());
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

        $logo = $museumRecord->getFirstMedia('logo');
        $museumLogo = [
            'url' => $logo->getUrl(),
        ];

        $audio = $museumRecord->getMedia('audio');
        $museumAudio = $audio->map(fn ($media) => [
            $media->getCustomProperty('lang') => [
                'url' => $media->getUrl(),
            ]
        ])->collapse()->toArray();

        $images = $museumRecord->getMedia('images');
        $museumImages = $images->map(fn ($media) => [
            'url' => $media->getUrl(),
            'title' => $media->getCustomProperty('title'),
            'caption' => $media->getCustomProperty('caption'),
            'group_index' => $media->getCustomProperty('group_index'),
        ])->groupBy('group_index')->toArray();

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

        $logo = $museumRecord->getFirstMedia('logo', 'thumb');
        $museumLogo = [
            'id' => $logo->id,
            'url' => $logo->getUrl(),
        ];

        $audio = $museumRecord->getMedia('audio');

        $museumAudio = $audio->map(fn ($media) => [
            $media->getCustomProperty('lang') => [
                'id' => $media->id,
                'url' => $media->getUrl(),
            ]
        ])->collapse()->toArray();

        $images = $museumRecord->getMedia('images');
        $museumImages = $images->map(fn ($media) => [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'title' => $media->getCustomProperty('title'),
            'caption' => $media->getCustomProperty('caption'),
            'group_index' => $media->getCustomProperty('group_index'),
        ])->groupBy('group_index')->toArray();

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
            if (isset($data['logo']) && $data['logo']['id'] === null) {
                if ($request->hasFile('logo.file')) {
                    $museum->clearMediaCollection('logo');
                    $museum->addMediaFromRequest('logo.file')
                        ->toMediaCollection('logo');
                }
            }

            // Gestione Audio
            if (isset($data['audio'])) {
                $this->syncAudioFiles($museum, $data['audio']);
            }

            // Gestione Immagini Gallery
            if (isset($data['images'])) {
                $this->syncGalleryFiles($museum, $data['images']);
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
                ->with('error', 'Errore durante l\'aggiornamento del museo: '.$e->getMessage());
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

    private function syncAudioFiles(Museum $museum, array $audioData)
    {
        $currentAudio = $museum->getMedia('audio');
        $toDelete = $currentAudio->reject(fn (Media $m) => collect($audioData)->pluck('id')->contains($m->id));
        $toDelete->each->delete();

        foreach ($audioData as $lang => $data) {
            if (! isset($data['id']) && isset($data['file']) && $data['file'] instanceof UploadedFile) {
                // Aggiungi nuovo file audio
                $museum->addMediaFromRequest("audio.{$lang}.file")
                    ->withCustomProperties(['lang' => $lang])
                    ->toMediaCollection('audio');
            }
        }
    }

    private function syncGalleryFiles(Museum $museum, array $galleryData)
    {
        $currentGallery = $museum->getMedia('images');
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
            } else if (! isset($data['id']) && isset($data['file']) && $data['file'] instanceof UploadedFile) {
                // Aggiungi nuovo file alla galleria
                $media = $museum->addMediaFromRequest("images.{$index}.file")
                    ->withCustomProperties([
                        'title' => $data['title'],
                        'description' => $data['description'],
                        'group_index' => $index,
                    ])
                    ->toMediaCollection('images');
            }
        }
    }
}
