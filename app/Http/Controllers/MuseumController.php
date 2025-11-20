<?php

namespace App\Http\Controllers;

use App\Models\Museum;
use App\Models\MuseumImage;
use App\Helpers\LanguageHelper;
use App\Http\Requests\StoreMuseumRequest;
use App\Http\Requests\UpdateMuseumRequest;
use App\Services\MediaService;
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
        // dd($request);
        $data = $request->validated();

        $museumName = $data['name'];

        $museumLogo = null;
        $museumDescription = $data['description'];
        if (isset($data['logo']) && !empty($data['logo'])) {
            $museumLogo = $this->mediaService->createMedia(
                'image',
                $data['logo']['file'],
                $data['logo']['title'],
                $data['logo']['description'] ?? null,
                'public',
                'media'
            );
        }

        $museumAudio = null;
        if (isset($data['audio']) && !empty($data['audio'])) {
            $museumAudio = $this->mediaService->createMedia(
                'audio',
                $data['audio']['file'],
                $data['audio']['title'],
                $data['audio']['description'] ?? null,
                'public',
                'media'
            );
        }

        $museumImages = null;
        if (isset($data['images']) && !empty($data['images'])) {
            $museumImages = collect($data['images'])->map(function ($image) {
                return $this->mediaService->createMedia(
                    'image',
                    $image['file'],
                    $image['title'],
                    $image['description'] ?? null,
                    'public',
                    'media'
                );
            });
        }


        $museum = Museum::create([
            'name' => $museumName,
            'description' => $museumDescription,
            'logo_id' => $museumLogo?->id,
            'audio_id' => $museumAudio?->id,
        ]);

        if ($museumImages && $museumImages->isNotEmpty()) {
            $museum->images()->attach($museumImages->pluck('id')->toArray());
        }

        //return redirect()->route('museums.index')->with('success', 'Museo creato con successo.');
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

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMuseumRequest $request, string $id)
    {

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
}
