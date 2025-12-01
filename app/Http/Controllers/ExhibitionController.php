<?php

namespace App\Http\Controllers;

use App\Models\Exhibition;
use App\Models\Media;
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
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('backend/Exhibitions/Create', []);
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
            $exhibitionAudio = null;
            if (isset($data['audio']) && !empty($data['audio']['file'])) {
                $exhibitionAudio = $this->mediaService->createMedia(
                    type: 'audio',
                    files: $data['audio']['file'],
                    titles: $data['audio']['title'] ?? [],
                    descriptions: $data['audio']['description'] ?? null,
                    disk: 'public',
                    folder: 'media'
                );
            }

            // Create the exhibition
            $exhibition = Exhibition::create([
                'museum_id' => $data['museum_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? [],
                'credits' => $data['credits'] ?? [],
                'audio_id' => $exhibitionAudio?->id,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'is_archived' => $data['is_archived'] ?? false,
            ]);

            // Handle images
            if (isset($data['images']) && !empty($data['images'])) {
                $imageIds = [];
                foreach ($data['images'] as $imageData) {
                    if (isset($imageData['file']) && !empty($imageData['file'])) {
                        $imageMedia = $this->mediaService->createMedia(
                            type: 'image',
                            files: $imageData['file'],
                            titles: $imageData['title'] ?? [],
                            descriptions: $imageData['description'] ?? null,
                            disk: 'public',
                            folder: 'media'
                        );
                        $imageIds[] = $imageMedia->id;
                    }
                }

                if (!empty($imageIds)) {
                    $exhibition->images()->attach($imageIds);
                }
            }

            DB::commit();

            return redirect()->route('exhibitions.index')->with('success', 'Collezione creata con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Errore durante la creazione della collezione: ' . $e->getMessage()]);
        }
    }
    public function show($id)
    {
        $WxhibitionRecord = Exhibition::with(['audio', 'images', 'museum'])->findOrFail($id);
    }

    public function update($id)
    {
        //
    }

    public function destroy($id)
    {
        $exhibition = Exhibition::findOrFail($id);
        $exhibition->delete();



        return redirect()->route('exhibitions.index')->with('success', 'Exhibition deleted successfully.');
    }
}

