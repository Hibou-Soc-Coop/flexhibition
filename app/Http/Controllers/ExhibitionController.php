<?php

namespace App\Http\Controllers;

use App\Models\Exhibition;
use App\Models\Media;
use App\Models\Museum;
use App\Helpers\LanguageHelper;
use App\Http\Requests\StoreExhibitionRequest;
use App\Http\Requests\UpdateExhibitionRequest;
use App\Services\MediaService;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;


class ExhibitionController extends Controller
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

        $exhibitionRecords = Exhibition::with([ 'museum'])->get();

        $exhibitions = [];

        foreach ($exhibitionRecords as $exhibitionRecord) {
            $exhibition = [];
            $exhibition['id'] = $exhibitionRecord->id;
            $exhibition['name'] = $exhibitionRecord->getTranslations('name');
            $exhibition['description'] = $exhibitionRecord->getTranslations('description');
            $exhibition['museum_id'] = $exhibitionRecord->museum_id;
            $exhibition['museum_name'] = $exhibitionRecord->museum ? $exhibitionRecord->museum->getTranslations('name')[$primaryLanguageCode] ?? '' : '';
            $exhibition['start_date'] = $exhibitionRecord->start_date?->format('Y-m-d');
            $exhibition['end_date'] = $exhibitionRecord->end_date?->format('Y-m-d');
            $exhibition['is_archived'] = $exhibitionRecord->is_archived;
            $exhibition['audio']['url'] = $exhibitionRecord->audio ? $exhibitionRecord->audio->getTranslations('url') : [];
            $exhibition['audio']['title'] = $exhibitionRecord->audio ? $exhibitionRecord->audio->getTranslations('title') : [];
            $exhibition['audio']['description'] = $exhibitionRecord->audio ? $exhibitionRecord->audio->getTranslations('description') : [];

            $exhibition['images'] = $exhibitionRecord->images->map(function ($image) use ($primaryLanguageCode) {
                return [
                        'url' => $image->getTranslations('url'),
                        'title' => $image->getTranslations('title'),
                        'description' => $image->getTranslations('description'),
                ];
            })->toArray();

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
            $museumData['name'] = [
                $primaryLanguage->code => $museum->getTranslation('name', $primaryLanguage->code),
            ];
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
            // Create audio media if provided
             $audioId = isset($data['audio']['file'])
                ? $this->createMediaFromData($data['audio'], 'audio')?->id
                : null;

            // Create the exhibition
            $exhibition = Exhibition::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? [],
                'audio_id' => $audioId,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'is_archived' =>  false,
                'museum_id' => $data['museum_id'] ?? null,
            ]);

            // Handle images
            if (isset($data['images']) && !empty($data['images'])) {
                $imageIds = collect($data['images'])
                ->map(function($img) {
                        return $this->createMediaFromData($img, 'image');
                    })
                    ->pluck('id')
                    ->toArray();

                $exhibition->images()->attach($imageIds);
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
        $exhibitionRecord = Exhibition::findOrFail($id);
        $museumRecord = Museum::find($exhibitionRecord->museum_id);
        $exhibitionName = $exhibitionRecord->getTranslations('name');
        $exhibitionDescription = $exhibitionRecord->getTranslations('description');
        $exhibitionAudio = $exhibitionRecord->audio;
        $exhibitionImages = $exhibitionRecord->images;
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

    public function edit($id){
        $exhibitionRecord = Exhibition::findOrFail($id);
        $museumRecord = Museum::find($exhibitionRecord->museum_id);
        $exhibitionName = $exhibitionRecord->getTranslations('name');
        $exhibitionDescription = $exhibitionRecord->getTranslations('description');
        $exhibitionAudio = $exhibitionRecord->audio;
        $exhibitionImages = $exhibitionRecord->images;
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
            ];

        return Inertia::render('backend/Exhibitions/Edit', [
            'exhibition' => $exhibitionData,
        ]);

    }

    public function update(UpdateExhibitionRequest $request, string $id)
    {
        $data = $request->validated();
        $exhibition = Exhibition::findOrFail($id);
        DB::beginTransaction();
        try{
            $exhibition->update([
                'name' => $data['name'] ?? $exhibition->name,
                'description' => $data['description'] ?? $exhibition->description,
                'start_date' => $data['start_date'] ?? $exhibition->start_date,
                'end_date' => $data['end_date'] ?? $exhibition->end_date,
                'is_archived' => false,
                'museum_id' => $data['museum_id'] ?? $exhibition->museum_id
            ]);

            // Gestione Audio
            $audioId = $this->handleMediaUpdate(
                $data['audio'] ?? null,
                $exhibition->audio_id,
                'audio'
            );
            if ($audioId !== $exhibition->audio_id) {
                $exhibition->update(['audio_id' => $audioId]);
            }

            // Gestione Immagini Gallery
            if (isset($data['images'])) {
                $this->handleGalleryUpdate($exhibition, $data['images']);
            }

            DB::commit();

            return redirect()
                ->route('exhibitions.index')
                ->with('success', 'Mostra aggiornata con successo.');
        }catch(\Exception $e){
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
     * @param Exhibition $exhibition Istanza dell'esposizione
     * @param array $imagesData Dati delle immagini dal request
     */
    private function handleGalleryUpdate(Exhibition $exhibition, array $imagesData): void
    {
        $currentImageIds = $exhibition->images()->pluck('media.id')->toArray();
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
        $exhibition->images()->sync($updatedImageIds);

        // Elimina i media che non sono più associati
        $imagesToDelete = array_diff($currentImageIds, $updatedImageIds);
        foreach ($imagesToDelete as $imageId) {
            $this->mediaService->deleteMedia($imageId);
        }
    }
}



