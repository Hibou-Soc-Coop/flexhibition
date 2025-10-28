<?php

namespace App\Http\Requests;

use App\Models\Language;
use App\Helpers\LanguageHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMuseumRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
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
        $validationRules['*.museum_logo.media_file'] = ['nullable', 'image', "mimes:{$imageMimes}", "max:{$imageMaxSize}", "dimensions:max_width={$logoWidth},max_height={$logoHeight}"];

        $validationRules['*.museum_gallery'] = ['nullable', 'array'];
        $validationRules['*.museum_gallery.*'] = ['nullable', 'array'];
        $validationRules['*.museum_gallery.*.media_id'] = ['nullable', 'numeric', 'exists:media,media_id'];
        $validationRules['*.museum_gallery.*.media_file'] = ['nullable', 'image', "mimes:{$imageMimes}", "max:{$imageMaxSize}", "dimensions:max_width={$galleryWidth},max_height={$galleryHeight}"];
        $validationRules['*.museum_gallery.*.media_to_delete'] = ['nullable', 'boolean'];

        $validationRules['*.museum_audio.media_to_delete'] = ['nullable', 'boolean'];
        $validationRules['*.museum_audio'] = ['nullable', 'array'];
        $validationRules['*.museum_audio.media_id'] = ['nullable', 'integer', 'exists:media,media_id'];
        $validationRules['*.museum_audio.media_file'] = ['nullable', 'file', "mimes:{$audioMimes}", "max:{$audioMaxSize}"];

        return $validationRules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $languages = Language::all();
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

        return $messages;
    }
}
