<?php

namespace App\Http\Controllers;

use App\Models\Museum;
use App\Models\MuseumImage;
use App\Models\Content;
use App\Models\Media;
use App\Models\Language;
use App\Facades\Setting;
use App\Helpers\LanguageHelper;
use App\Services\MediaService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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
        $maxMuseums = env('MAX_MUSEUM_RECORDS');

        $museumRecords = Museum::with('logo')->get();

        $museums = [];

        foreach ($museumRecords as $museumRecord) {
            $museum = [];
            $museum['id'] = $museumRecord->id;
            $museum['contents'][$primaryLanguageCode] = [
                'name' => $museumRecord->getTranslation('name', $primaryLanguageCode),
                'description' => $museumRecord->getTranslation('description', $primaryLanguageCode),
            ];
            $museum['logo'][$primaryLanguageCode]['media_url'] = $museumRecord->logo ? $museumRecord->logo->media_url : null;
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
        $maxMuseums = Setting::get('max_museum_records');

        if ($museums >= $maxMuseums) {
            return redirect()->route('museums.index')->with('error', 'Non è possibile creare ulteriori musei.');
        }
        return Inertia::render('backend/Museums/Create', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $languages = Language::all();
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $imageMaxSize = config('mediasettings.sizes.image.max');
        $imageMimes = config('mediasettings.types.image');
        $logoWidth = config('mediasettings.dimensions.image.width');
        $logoHeight = config('mediasettings.dimensions.image.height');
        $galleryWidth = config('mediasettings.dimensions.gallery.width');
        $galleryHeight = config('mediasettings.dimensions.gallery.height');
        $audioMaxSize = config('mediasettings.sizes.audio.max');
        $audioMimes = config('mediasettings.types.audio');

        $validated = [];
        $validationRules = [];
        foreach ($languages as $language) {
            if ($language->language_code === $primaryLanguage->language_code) {
                $validationRules[$language->language_code . '.museum_name'] = ['required', 'string', 'max:100'];
            } else {
                $validationRules[$language->language_code . '.museum_name'] = ['nullable', 'string', 'max:100'];
            }
        }
        $validationRules['*.museum_description'] = ['nullable', 'string', 'max:2000'];
        $validationRules['*.museum_logo'] = ['nullable', 'array'];
        $validationRules['*.museum_logo.media_id'] = ['nullable', 'integer', 'exists:media,media_id'];
        $validationRules['*.museum_logo.media_file'] = ['nullable', 'image', "mimes:{$imageMimes}", "max:{$imageMaxSize}", "dimensions:max_width={$logoWidth},max_height={$logoHeight}"];
        $validationRules['*.museum_audio'] = ['nullable', 'array'];
        $validationRules['*.museum_audio.media_id'] = ['nullable', 'integer', 'exists:media,media_id'];
        $validationRules['*.museum_audio.media_file'] = ['nullable', 'file', "mimes:{$audioMimes}", "max:{$audioMaxSize}"];
        $validationRules['*.museum_gallery'] = ['nullable', 'array'];
        $validationRules['*.museum_gallery.*'] = ['nullable', 'array'];
        $validationRules['*.museum_gallery.*.media_id'] = ['nullable', 'integer', 'exists:media,media_id'];
        $validationRules['*.museum_gallery.*.media_file'] = ['nullable', 'image', "mimes:{$imageMimes}", "max:{$imageMaxSize}", "dimensions:max_width={$galleryWidth},max_height={$galleryHeight}"];

        $messages = [];
        foreach ($languages as $language) {
            $lang = $language->language_code;
            $langName = $language->language_name;
            $messages["{$lang}.museum_name.required"] = "Il nome del museo in {$langName} è obbligatorio.";
            $messages["{$lang}.museum_name.string"] = "Il nome del museo in {$langName} deve essere una stringa.";
            $messages["{$lang}.museum_name.max"] = "Il nome del museo in {$langName} non può superare 100 caratteri.";
            $messages["{$lang}.museum_description.string"] = "La descrizione del museo in {$langName} deve essere una stringa.";
            $messages["{$lang}.museum_description.max"] = "La descrizione del museo in {$langName} non può superare 10000 caratteri.";
            $messages["{$lang}.museum_logo.media_id.integer"] = "L'ID del logo in {$langName} deve essere un numero.";
            $messages["{$lang}.museum_logo.media_id.exists"] = "Il logo selezionato in {$langName} non esiste.";
            $messages["{$lang}.museum_logo.media_file.image"] = "Il file del logo in {$langName} deve essere un'immagine.";
            $messages["{$lang}.museum_logo.media_file"] = "Il file logo deve essere un immagine valido e non superare 2MB (Formats: jpeg, jpg, png, gif, Dimensioni massime: 1200 x 1536)..";
            $messages["{$lang}.museum_audio.media_id.integer"] = "L'ID dell'audio in {$langName} deve essere un numero.";
            $messages["{$lang}.museum_audio.media_id.exists"] = "L'audio selezionato in {$langName} non esiste.";
            $messages["{$lang}.museum_audio.media_file"] = "Il file audio  deve essere un file mp3 valido e non superare 4MB.";
            $messages["{$lang}.museum_gallery.*.media_id.integer"] = "L'ID dell'immagine della galleria in {$langName} deve essere un numero.";
            $messages["{$lang}.museum_gallery.*.media_id.exists"] = "L'immagine selezionata della galleria in {$langName} non esiste.";
            $messages["{$lang}.museum_gallery.*.media_file.image"] = "Il file della galleria in {$langName} deve essere un'immagine.";
            $messages["{$lang}.museum_gallery.*.media_file.max"] = "Il file della galleria in {$langName} non deve superare {$imageMaxSize} kilobyte.";
            $messages["{$lang}.museum_gallery.*.media_file.dimensions"] = "L'immagine della galleria in {$langName} deve avere larghezza max {$galleryWidth}px e altezza max {$galleryHeight}px.";
            $messages["{$lang}.museum_gallery.*.media_file.mimes"] = "Il file della galleria in {$langName} deve essere un'immagine di tipo valido: {$imageMimes}.";
        }

        $validated = $request->validate($validationRules, $messages);

        DB::beginTransaction();

        try {
            $primaryMuseumNameTexts = collect($validated)
                ->mapWithKeys(function (array $item, $key) {
                    return [$key => $item['museum_name'] ?? null];
                })
                ->all();
            $primaryMuseumName = Content::createWithTranslations($primaryMuseumNameTexts, $primaryLanguage);

            if (!empty($validated[$primaryLanguage->language_code]['museum_description'])) {
                $primaryMuseumDescriptionTexts = collect($validated)
                    ->mapWithKeys(function (array $item, $key) {
                        return [$key => $item['museum_description'] ?? null];
                    })
                    ->all();
                $primaryMuseumDescription = Content::createWithTranslations($primaryMuseumDescriptionTexts, $primaryLanguage);
            } else {
                $primaryMuseumDescription = null;
            }

            $logo = [];
            $audio = [];
            $galleryByFile = [];

            foreach ($validated as $lang => $data) {
                if (isset($data['museum_logo'])) {
                    if (isset($data['museum_logo']['media_file'])) {
                        $logo[$lang] = $this->createMediaData($data['museum_logo'], $data['museum_name'], 'logo');
                    }
                }
                if (isset($data['museum_audio'])) {
                    if (isset($data['museum_audio']['media_file'])) {
                        $audio[$lang] = $this->createMediaData($data['museum_audio'], $data['museum_name'], 'audio');
                    }
                }
                if (isset($data['museum_gallery'])) {
                    foreach ($data['museum_gallery'] as $image) {
                        $fileName = $image['media_file']->getClientOriginalName();
                        $galleryByFile[$fileName][$lang] = $this->createMediaData($image, $data['museum_name'], 'gallery');
                    }
                }
            }

            if (!empty($logo)) {
                $museumLogo = $this->mediaService->createMedia($logo);
            } else {
                $museumLogo = null;
            }
            if (!empty($audio)) {
                $museumAudio = $this->mediaService->createMedia($audio);
            } else {
                $museumAudio = null;
            }
            if (!empty($galleryByFile)) {
                $museumGallery = [];
                foreach ($galleryByFile as $fileData) {
                    $museumGallery[] = $this->mediaService->createMedia($fileData);
                }
            } else {
                $museumGallery = null;
            }

            $museum = Museum::create([
                'museum_name_id' => $primaryMuseumName->content_id,
                'museum_description_id' => $primaryMuseumDescription ? $primaryMuseumDescription->content_id : null,
                'museum_logo_id' => $museumLogo ? $museumLogo->media_id : null,
                'museum_audio_id' => $museumAudio ? $museumAudio->media_id : null,
            ]);

            if (!empty($museumGallery)) {
                foreach ($museumGallery as $museumGalleryFileId) {
                    MuseumImage::create([
                        'museum_id' => $museum->museum_id,
                        'media_id' => $museumGalleryFileId->media_id,
                    ]);
                }
            }


            DB::commit();

            return redirect()->route('museums.index')->with('success', 'Museo creato con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            $messages["{$lang}.museum_logo.exception"] = $e->getMessage();
            return redirect()->back()->withErrors($e->getMessage(), 'errorMessage');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $languages = Language::all();
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();

        $museumRecord = Museum::findOrFail($id);

        $contents = [];

        $museumNames = Content::findWithTranslations($museumRecord->museum_name_id);
        $museumDescriptions = $museumRecord->museum_description_id ? Content::findWithTranslations($museumRecord->museum_description_id) : null;
        $museumLogos = $museumRecord->museum_logo_id ? Media::findWithTranslations($museumRecord->museum_logo_id) : null;
        $museumAudios = $museumRecord->museum_audio_id ? Media::findWithTranslations($museumRecord->museum_audio_id) : null;

        $galleryRecords = MuseumImage::where('museum_id', $museumRecord->museum_id)->with('media')->get();

        foreach ($languages as $language) {
            $langCode = $language->language_code;

            $contents[$langCode] = []; // inizializza l'array

            if ($museumNames && isset($museumNames->texts[$langCode])) {
                $contents[$langCode]['museum_name'] = $museumNames->texts[$langCode];
            }
            if ($museumDescriptions && isset($museumDescriptions->texts[$langCode])) {
                $contents[$langCode]['museum_description'] = $museumDescriptions->texts[$langCode];
            }

            // Assegna logo
            if ($museumLogos && $museumLogos->media_contents[$langCode]['media_url']) {
                $contents[$langCode]['museum_logo'] = [
                    'media_id' => $museumLogos ? ($museumLogos->media_id ?? null) : null,
                    'media_file' => null,
                    'media_preview' => $museumLogos ? ($museumLogos->media_contents[$langCode]['media_url'] ?? null) : null,
                ];
            }

            // Assegna audio
            if ($museumAudios && $museumAudios->media_contents[$langCode]['media_url']) {
                $contents[$langCode]['museum_audio'] = [
                    'media_id' => $museumAudios ? ($museumAudios->media_id ?? null) : null,
                    'media_file' => null,
                    'media_preview' => $museumAudios ? ($museumAudios->media_contents[$langCode]['media_url'] ?? null) : null,
                ];
            }

            if ($langCode === $primaryLanguage->language_code) {
                $contents[$langCode]['museum_gallery'] = $galleryRecords->map(function ($galleryRecord) {
                    return [
                        'media_id' => $galleryRecord->media->media_id,
                        'media_file' => null,
                        'media_preview' => $galleryRecord->media->media_url,
                    ];
                });
            }

            if ($contents[$langCode] === []) {
                unset($contents[$langCode]);
            }
        }

        return Inertia::render('backend/Museums/Show', [
            'museum' => $museumRecord,
            'contents' => $contents,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $languages = Language::all();

        $museumRecord = Museum::findOrFail($id);

        $contents = [];

        $museumNames = Content::findWithTranslations($museumRecord->museum_name_id);
        $museumDescriptions = $museumRecord->museum_description_id ? Content::findWithTranslations($museumRecord->museum_description_id) : null;
        $museumLogos = $museumRecord->museum_logo_id ? Media::findWithTranslations($museumRecord->museum_logo_id) : null;
        $museumAudios = $museumRecord->museum_audio_id ? Media::findWithTranslations($museumRecord->museum_audio_id) : null;

        $galleryRecords = MuseumImage::where('museum_id', $museumRecord->museum_id)->with('media')->get();

        foreach ($languages as $language) {
            $langCode = $language->language_code;

            $contents[$langCode] = [
                'museum_name' => $museumNames ? ($museumNames->texts[$langCode] ?? null) : null,
                'museum_description' => $museumDescriptions ? ($museumDescriptions->texts[$langCode] ?? null) : null,
                'museum_logo' => [
                    'media_id' => $museumLogos ? ($museumLogos->media_id ?? null) : null,
                    'media_file' => null,
                    'media_preview' => $museumLogos ? ($museumLogos->media_contents[$langCode]['media_url'] ?? null) : null,
                ],
                'museum_audio' => [
                    'media_id' => $museumAudios ? ($museumAudios->media_id ?? null) : null,
                    'media_file' => null,
                    'media_preview' => $museumAudios ? ($museumAudios->media_contents[$langCode]['media_url'] ?? null) : null,
                ],
                'museum_gallery' => $galleryRecords->map(function ($galleryRecord) {
                    return [
                        'media_id' => $galleryRecord->media->media_id,
                        'media_file' => null,
                        'media_preview' => $galleryRecord->media->media_url,
                    ];
                }),
            ];
        }

        return Inertia::render('backend/Museums/Edit', [
            'museum' => $museumRecord,
            'contents' => $contents,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $languages = Language::all();
        $primaryLanguage = LanguageHelper::getPrimaryLanguage();
        $imageMaxSize = config('mediasettings.sizes.image.max');
        $imageMimes = config('mediasettings.types.image');
        $logoWidth = config('mediasettings.dimensions.image.width');
        $logoHeight = config('mediasettings.dimensions.image.height');
        $galleryWidth = config('mediasettings.dimensions.gallery.width');
        $galleryHeight = config('mediasettings.dimensions.gallery.height');
        $audioMaxSize = config('mediasettings.sizes.audio.max');
        $audioMimes = config('mediasettings.types.audio');

        // Validazione solo del museum_name
        $validationRules = [];
        foreach ($languages as $language) {
            if ($language->language_code === $primaryLanguage->language_code) {
                $validationRules[$language->language_code . '.museum_name'] = ['required', 'string', 'max:100'];
            } else {
                $validationRules[$language->language_code . '.museum_name'] = ['nullable', 'string', 'max:100'];
            }
        }
        $validationRules['*.museum_description'] = ['nullable', 'string', 'max:2000'];

        $validationRules['*.museum_logo'] = ['nullable', 'array'];
        $validationRules['*.museum_logo.media_id'] = ['nullable', 'numeric', 'exists:media,media_id'];
        $validationRules['*.museum_logo.media_to_delete'] = ['nullable', 'boolean'];
        $validationRules['*.museum_logo.media_file'] = ['nullable', 'image', "mimes:{$imageMimes}", "max: {$imageMaxSize}", "dimensions:max_width={$logoWidth},max_height={$logoHeight}"];


        $validationRules['*.museum_gallery'] = ['nullable', 'array'];
        $validationRules['*.museum_gallery.*'] = ['nullable', 'array'];
        $validationRules['*.museum_gallery.*.media_id'] = ['nullable', 'numeric', 'exists:media,media_id'];
        $validationRules['*.museum_gallery.*.media_file'] = ['nullable', 'image', "mimes:{$imageMimes}", "max: {$imageMaxSize}", "dimensions:max_width={$galleryWidth},max_height={$galleryHeight}"];
        $validationRules['*.museum_gallery.*.media_to_delete'] = ['nullable', 'boolean'];


        $validationRules['*.museum_audio.media_to_delete'] = ['nullable', 'boolean'];
        $validationRules['*.museum_audio'] = ['nullable', 'array'];
        $validationRules['*.museum_audio.media_id'] = ['nullable', 'integer', 'exists:media,media_id'];
        $validationRules['*.museum_audio.media_file'] = ['nullable', 'file', "mimes:{$audioMimes}", "max: {$audioMaxSize}"];

        $messages = [];
        foreach ($languages as $language) {
            $lang = $language->language_code;
            $langName = $language->language_name;
            $messages["{$lang}.museum_name.required"] = "Il nome del museo in {$langName} è obbligatorio.";
            $messages["{$lang}.museum_name.string"] = "Il nome del museo in {$langName} deve essere una stringa.";
            $messages["{$lang}.museum_name.max"] = "Il nome del museo in {$langName} non può superare 100 caratteri.";
            $messages["{$lang}.museum_description.string"] = "La descrizione del museo in {$langName} deve essere una stringa.";
            $messages["{$lang}.museum_description.max"] = "La descrizione del museo in {$langName} non può superare 10000 caratteri.";
            $messages["{$lang}.museum_logo.media_id.numeric"] = "L'ID del logo in {$langName} deve essere un numero.";
            $messages["{$lang}.museum_logo.media_id.exists"] = "Il logo selezionato in {$langName} non esiste.";
            $messages["{$lang}.museum_logo.media_file.image"] = "Il file del logo in {$langName} deve essere un'immagine valida.";
            $messages["{$lang}.museum_logo.media_file"] = "Il file del logo in {$langName} deve essere un'immagine valida e meno 2MB (Formats: jpeg, jpg, png, gif, Dimensioni massime: 1200 x 1536).";
            $messages["{$lang}.museum_logo.media_to_delete.boolean"] = "Il campo 'Elimina logo' in {$langName} deve essere vero o falso.";
            $messages["{$lang}.museum_audio.media_id.numeric"] = "L'ID dell'audio in {$langName} deve essere un numero.";
            $messages["{$lang}.museum_audio.media_id.exists"] = "L'audio selezionato in {$langName} non esiste.";
            $messages["{$lang}.museum_audio.media_file.audio"] = "Il file audio in {$langName} deve essere un file mp3 valido.";
            $messages["{$lang}.museum_audio.media_file"] = "Il file audio in {$langName} deve essere un file mp3 valido e meno 5MB.";
            $messages["{$lang}.museum_audio.media_to_delete.boolean"] = "Il campo 'Elimina audio' in {$langName} deve essere vero o falso.";
            $messages["{$lang}.museum_gallery.*.media_id.numeric"] = "L'ID dell'immagine della galleria in {$langName} deve essere un numero.";
            $messages["{$lang}.museum_gallery.*.media_id.exists"] = "L'immagine selezionata della galleria in {$langName} non esiste.";
            $messages["{$lang}.museum_gallery.*.media_file.image"] = "Il file della galleria in {$langName} deve essere un'immagine valida.";
            $messages["{$lang}.museum_gallery.*.media_file"] = "Il file della galleria in {$langName} deve essere un'immagine valida (Formats: jpeg, jpg, png, gif, Dimensioni massime: 1920 x 1080).";
            $messages["{$lang}.museum_gallery.*.media_to_delete.boolean"] = "Il campo 'Elimina immagine galleria' in {$langName} deve essere vero o falso.";
        }
        $validated = $request->validate($validationRules, $messages);

        $museum = Museum::findOrFail($id);

        DB::beginTransaction();

        try {
            // Aggiorna nome e descrizione
            $museumNameTexts = collect($validated)->mapWithKeys(fn($item, $key) => [$key => $item['museum_name'] ?? null])->all();
            Content::updateWithTranslations($museum->museum_name_id, $museumNameTexts, $primaryLanguage);

            if ($museum->museum_description_id) {
                $museumDescriptionTexts = collect($validated)->mapWithKeys(fn($item, $key) => [$key => $item['museum_description'] ?? null])->all();
                Content::updateWithTranslations($museum->museum_description_id, $museumDescriptionTexts, $primaryLanguage);
            } elseif (!empty($validated[$primaryLanguage->language_code]['museum_description'])) {
                $museumDescriptionTexts = collect($validated)->mapWithKeys(fn($item, $key) => [$key => $item['museum_description'] ?? null])->all();
                $newDescription = Content::createWithTranslations($museumDescriptionTexts, $primaryLanguage);
                $museum->museum_description_id = $newDescription->content_id;
            }

            $museum->museum_logo_id = $this->newHandleMediaUpdate(
                $this->extractMediaData($validated, 'museum_logo'),
                $primaryLanguage->language_code
            );

            $museum->museum_audio_id = $this->newHandleMediaUpdate(
                $this->extractMediaData($validated, 'museum_audio'),
                $primaryLanguage->language_code
            );

            $galleryData = [];
            if (!empty($validated[$primaryLanguage->language_code]['museum_gallery'])) {
                foreach ($validated[$primaryLanguage->language_code]['museum_gallery'] as $image) {
                    $item[$primaryLanguage->language_code] = $image;
                    $galleryData[] = $item;
                }
            }

            $gallery = [];
            foreach ($galleryData as $imageData) {
                $gallery[] = $this->newHandleMediaUpdate(
                    $imageData,
                    $primaryLanguage->language_code
                );
            }
            if (!empty($validated[$primaryLanguage->language_code]['museum_gallery'])) {
                foreach ($gallery as $mediaId) {
                    if ($mediaId) {
                        MuseumImage::updateOrCreate(
                            ['museum_id' => $museum->museum_id, 'media_id' => $mediaId],
                            ['media_id' => $mediaId]
                        );
                    }
                }
            }

            $museum->save();
            DB::commit();

            return redirect()->route('museums.show', $id)->with('success', 'Museo aggiornato con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore update museo: ' . $e->getMessage());
            return redirect()->back()->withErrors($e->getMessage(), 'errorMessage');
        } catch (PostTooLargeException $e) {
            DB::rollBack();
            Log::error('Errore update museo: ' . $e->getMessage());
            return redirect()->back()->withErrors(['errorMessage' => 'Il file caricato è troppo grande.'], 'errorMessage');
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $museum = Museum::findOrFail($id);

        DB::beginTransaction();

        try {
            $museum->delete();

            // Elimina i contenuti associati
            if ($museum->museum_name_id) {
                Content::destroy($museum->museum_name_id);
            }
            if ($museum->museum_description_id) {
                Content::destroy($museum->museum_description_id);
            }
            if ($museum->museum_logo_id) {
                Media::destroy($museum->museum_logo_id);
            }
            if ($museum->museum_audio_id) {
                Media::destroy($museum->museum_audio_id);
            }

            DB::commit();

            return redirect()->route('museums.index')->with('success', 'Museo eliminato con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Errore durante l\'eliminazione del museo: ' . $e->getMessage());
        }
    }

    private function extractMediaData($input, $field)
    {
        $mediaData = [];
        foreach ($input as $lang => $fields) {
            if (isset($fields[$field])) {
                $mediaData[$lang] = $fields[$field];
            }
        }
        return $mediaData;
    }

    private function createMediaData($data, $imageTitle, $mediaType)
    {
        return [
            'media_title' => $imageTitle . " $mediaType",
            'media_description' => $imageTitle . " $mediaType",
            'media_file' => $data['media_file'],
        ];
    }

    private function newHandleMediaUpdate($imageData, $primaryLanguageCode)
    {
        $mediaId = $imageData[$primaryLanguageCode]['media_id'] ?? null;

        $mediaData = [];
        foreach ($imageData as $lang => $data) {
            if ((isset($data['media_file']) && $data['media_file'] instanceof UploadedFile)) {
                $mediaData[$lang] = [
                    'media_title' => $data['media_file']->getClientOriginalName(),
                    'media_description' => $data['media_file']->getClientOriginalName(),
                    'media_file' => $data['media_file'],
                    'media_to_delete' => $data['media_to_delete'],
                ];
            } elseif (isset($data['media_to_delete']) && $data['media_to_delete']) {
                $mediaData[$lang] = [
                    'media_to_delete' => true,
                ];
            }
        }

        // Gestione eliminazione (solo se richiesto nella lingua primaria)
        if (isset($imageData[$primaryLanguageCode]['media_to_delete']) && $imageData[$primaryLanguageCode]['media_to_delete'] && $mediaId) {
            $this->mediaService->deleteMedia($mediaId);
            return null;
        }

        // Update o creazione
        if (!empty($mediaData)) {
            if ($mediaId) {
                $this->mediaService->updateMedia($mediaId, $mediaData);
                return $mediaId;
            } else {
                $media = $this->mediaService->createMedia($mediaData);
                return $media->media_id;
            }
        }

        return $mediaId;
    }
}
