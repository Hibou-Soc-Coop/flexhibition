<?php

namespace App\Http\Requests;

use App\Models\Language;
use App\Helpers\LanguageHelper;
use Illuminate\Foundation\Http\FormRequest;

class StoreMuseumRequest extends FormRequest
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
        $validationRules['*.museum_logo.media_id'] = ['nullable', 'integer', 'exists:media,media_id'];
        $validationRules['*.museum_logo.media_file'] = ['nullable', 'image', "mimes:{$imageMimes}", "max:{$imageMaxSize}", "dimensions:max_width={$logoWidth},max_height={$logoHeight}"];
        $validationRules['*.museum_audio'] = ['nullable', 'array'];
        $validationRules['*.museum_audio.media_id'] = ['nullable', 'integer', 'exists:media,media_id'];
        $validationRules['*.museum_audio.media_file'] = ['nullable', 'file', "mimes:{$audioMimes}", "max:{$audioMaxSize}"];
        $validationRules['*.museum_gallery'] = ['nullable', 'array'];
        $validationRules['*.museum_gallery.*'] = ['nullable', 'array'];
        $validationRules['*.museum_gallery.*.media_id'] = ['nullable', 'integer', 'exists:media,media_id'];
        $validationRules['*.museum_gallery.*.media_file'] = ['nullable', 'image', "mimes:{$imageMimes}", "max:{$imageMaxSize}", "dimensions:max_width={$galleryWidth},max_height={$galleryHeight}"];

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
        $imageMaxSize = config('mediasettings.sizes.image.max');
        $galleryWidth = config('mediasettings.dimensions.gallery.width');
        $galleryHeight = config('mediasettings.dimensions.gallery.height');
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
            $messages["{$lang}.museum_logo.media_file"] = "Il file logo deve essere un immagine valido e non superare 2MB (Formats: jpeg, jpg, png, gif, Dimensioni massime: 1200 x 1536).";
            $messages["{$lang}.museum_audio.media_id.integer"] = "L'ID dell'audio in {$langName} deve essere un numero.";
            $messages["{$lang}.museum_audio.media_id.exists"] = "L'audio selezionato in {$langName} non esiste.";
            $messages["{$lang}.museum_audio.media_file"] = "Il file audio deve essere un file mp3 valido e non superare 4MB.";
            $messages["{$lang}.museum_gallery.*.media_id.integer"] = "L'ID dell'immagine della galleria in {$langName} deve essere un numero.";
            $messages["{$lang}.museum_gallery.*.media_id.exists"] = "L'immagine selezionata della galleria in {$langName} non esiste.";
            $messages["{$lang}.museum_gallery.*.media_file.image"] = "Il file della galleria in {$langName} deve essere un'immagine.";
            $messages["{$lang}.museum_gallery.*.media_file.max"] = "Il file della galleria in {$langName} non deve superare {$imageMaxSize} kilobyte.";
            $messages["{$lang}.museum_gallery.*.media_file.dimensions"] = "L'immagine della galleria in {$langName} deve avere larghezza max {$galleryWidth}px e altezza max {$galleryHeight}px.";
            $messages["{$lang}.museum_gallery.*.media_file.mimes"] = "Il file della galleria in {$langName} deve essere un'immagine di tipo valido.";
        }

        return $messages;
    }
}
