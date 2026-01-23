<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Museum;
use App\Helpers\LanguageHelper;
use App\Http\Requests\StoreMuseumRequest;
use App\Http\Requests\UpdateMuseumRequest;
use App\Services\MediaService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MuseumController extends Controller
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
        //$maxMuseums = Settings::get('max_museum_records');
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
            // Crea i media
            $logoId = isset($data['logo']['file'])
                ? $this->createMediaFromData($data['logo'], 'image')?->id
                : null;

            $audioId = isset($data['audio']['file'])
                ? $this->createMediaFromData($data['audio'], 'audio')?->id
                : null;

            // Crea il museo
            $museum = Museum::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'logo_id' => $logoId,
                'audio_id' => $audioId,
            ]);

            // Associa le immagini
            if (isset($data['images']) && !empty($data['images'])) {
                $imageIds = collect($data['images'])
                    ->map(function($img) {
                        return $this->createMediaFromData($img, 'image');
                    })
                    ->pluck('id')
                    ->toArray();

                $museum->images()->attach($imageIds);
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
            $logoId = $this->handleMediaUpdate(
                $data['logo'] ?? null,
                $museum->logo_id,
                'image'
            );
            if ($logoId !== $museum->logo_id) {
                $museum->update(['logo_id' => $logoId]);
            }

            // Gestione Audio
            $audioId = $this->handleMediaUpdate(
                $data['audio'] ?? null,
                $museum->audio_id,
                'audio'
            );
            if ($audioId !== $museum->audio_id) {
                $museum->update(['audio_id' => $audioId]);
            }

            // Gestione Immagini Gallery
            if (isset($data['images'])) {
                $this->handleGalleryUpdate($museum, $data['images']);
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

        // Elimina il logo
        if ($museum->logo_id) {
            $this->mediaService->deleteMedia($museum->logo_id);
        }

        // Elimina l'audio
        if ($museum->audio_id) {
            $this->mediaService->deleteMedia($museum->audio_id);
        }

        // Elimina tutte le immagini collegate
        $imageIds = $museum->images()->pluck('id')->toArray();
        foreach ($imageIds as $imageId) {
            $this->mediaService->deleteMedia($imageId);
        }

        // Elimina il museo
        $museum->delete();

        return redirect()->route('museums.index')->with('success', 'Museo eliminato con successo.');
    }

//    public function showMuseum($museumId, $language = 'it')
//     {
//         $museumRecord = Museum::findOrFail($museumId);

//         $museum = [];
//         $museum['id'] = $museumRecord->id;
//         $museum['name'] = $museumRecord->getTranslations('name');
//         $museum['description'] = $museumRecord->getTranslations('description');
//         $museum['logo'] = $museumRecord->logo ? $museumRecord->logo->getTranslations('url') : null;
//         $museum['audio'] = $museumRecord->audio ? $museumRecord->audio->getTranslations('url') : null;
//         $museum['images'] = $museumRecord->images->map(fn($image) => $image->media ? $image->media->getTranslations('url') : null)->toArray();

//         return Inertia::render('frontend/Museum', [
//             'museum' => $museum,
//             'language' => $language,
//         ]);
//     }


//     private function createMediaFromData(array $data, string $type): ?Media
//     {
//         if (empty($data['file'])) {
//             return null;
//         }

//         return $this->mediaService->createMedia(
//             $type,
//             $data['file'],
//             $data['title'],
//             $data['description'] ?? null,
//             'public',
//             'media'
//         );
//     }

    /**
     * Gestisce l'aggiornamento di un media (logo o audio).
     *
     * @param array|null $data Dati del media dal request
     * @param int|null $currentMediaId ID del media corrente
     * @param string $type Tipo di media ('image' o 'audio')
     * @return int|null ID del media aggiornato o null
     */
    private function handleMediaUpdate(?array $data, ?int $currentMediaId, string $type): ?int
    {
        if (!$data) {
            return $currentMediaId;
        }

        // Se è richiesta la cancellazione
        if (!empty($data['to_delete']) && $currentMediaId) {
            $this->mediaService->deleteMedia($currentMediaId);
            return null;
        }

        // Se c'è un nuovo file da caricare
        if (!empty($data['file'])) {
            // Se esiste già un media, aggiornalo
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
                // Crea un nuovo media e elimina il vecchio se esiste
                if ($currentMediaId) {
                    $this->mediaService->deleteMedia($currentMediaId);
                }
                $newMedia = $this->createMediaFromData($data, $type);
                return $newMedia?->id;
            }
        }

        // Se ci sono solo aggiornamenti di titolo/descrizione senza nuovo file
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
     * Gestisce l'aggiornamento delle immagini della gallery.
     *
     * @param Museum $museum Istanza del museo
     * @param array $imagesData Dati delle immagini dal request
     */
    private function handleGalleryUpdate(Museum $museum, array $imagesData): void
    {
        $currentImageIds = $museum->images()->pluck('media.id')->toArray();
        $updatedImageIds = [];

        foreach ($imagesData as $imageData) {
            // Se l'immagine deve essere eliminata
            if (!empty($imageData['to_delete']) && !empty($imageData['id'])) {
                $this->mediaService->deleteMedia($imageData['id']);
                continue;
            }

            // Se c'è un ID esistente
            if (!empty($imageData['id'])) {
                // Aggiorna se c'è un nuovo file o metadati
                if (!empty($imageData['file']) || isset($imageData['title']) || isset($imageData['description'])) {
                    $this->mediaService->updateMedia(
                        $imageData['id'],
                        $imageData['file'] ?? null,
                        $imageData['title'] ?? null,
                        $imageData['description'] ?? null,
                        'public',
                        'media'
                    );
                }
                $updatedImageIds[] = $imageData['id'];
            } elseif (!empty($imageData['file'])) {
                // Crea una nuova immagine
                $newImage = $this->createMediaFromData($imageData, 'image');
                if ($newImage) {
                    $updatedImageIds[] = $newImage->id;
                }
            }
        }

        // Sincronizza le immagini (rimuove quelle non più presenti)
        $museum->images()->sync($updatedImageIds);

        // Elimina i media che non sono più associati
        $imagesToDelete = array_diff($currentImageIds, $updatedImageIds);
        foreach ($imagesToDelete as $imageId) {
            $this->mediaService->deleteMedia($imageId);
        }
    }
}
