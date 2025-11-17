<?php

namespace App\Http\Controllers;

use App\Facades\Settings;
use App\Models\Museum;
use App\Models\MuseumImage;
use App\Models\Content;
use App\Models\Media;
use App\Models\Language;
use App\Helpers\LanguageHelper;
use App\Http\Requests\StoreMuseumRequest;
use App\Http\Requests\UpdateMuseumRequest;
use Inertia\Inertia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Exceptions\PostTooLargeException;


class MuseumController extends Controller
{
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
        dd($data);
        //$museum = Museum::create($data);

        //return redirect()->route('museums.index')->with('success', 'Museo creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $museumRecord = Museum::findOrFail($id);

        $museumName = $museumRecord->getTranslations('name');
        $museumDescription = $museumRecord->getTranslations('description');
        $museumLogo = $museumRecord->logo;
        $museumAudio = $museumRecord->audio;
        $museumImages = $museumRecord->images;

        $museumData = [
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

    }
}
