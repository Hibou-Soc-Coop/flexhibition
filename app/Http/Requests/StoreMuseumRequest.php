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
            if ($language->code === $primaryLanguage->code) {
                $validationRules['name.' . $language->code ] = ['required', 'string', 'max:100'];
            } else {
                $validationRules['name.' . $language->code ] = ['nullable', 'string', 'max:100'];
            }
        }

        $validationRules['description.*'] = ['nullable', 'string', 'max:2000'];

        $validationRules['logo'] = ['nullable', 'array'];
        $validationRules['logo.id'] = ['nullable', 'integer', 'exists:media,id'];
        $validationRules['logo.file.*'] = ['nullable', 'image', "mimes:{$imageMimes}", "max:{$imageMaxSize}", "dimensions:max_width={$logoWidth},max_height={$logoHeight}"];
        $validationRules['logo.title'] = ['nullable', 'array'];
        $validationRules['logo.title.*'] = ['nullable', 'string', 'max:100'];
        $validationRules['logo.description'] = ['nullable', 'array'];
        $validationRules['logo.description.*'] = ['nullable', 'string', 'max:2000'];
        $validationRules['logo.to_delete'] = ['nullable', 'boolean'];

        $validationRules['audio'] = ['nullable', 'array'];
        $validationRules['audio.id'] = ['nullable', 'integer', 'exists:media,id'];
        $validationRules['audio.file.*'] = ['nullable', 'file', "mimes:{$audioMimes}", "max:{$audioMaxSize}"];
        $validationRules['audio.title'] = ['nullable', 'array'];
        $validationRules['audio.title.*'] = ['nullable', 'string', 'max:100'];
        $validationRules['audio.description'] = ['nullable', 'array'];
        $validationRules['audio.description.*'] = ['nullable', 'string', 'max:2000'];
        $validationRules['audio.to_delete'] = ['nullable', 'boolean'];

        $validationRules['images'] = ['nullable', 'array'];
        $validationRules['images.*.id'] = ['nullable', 'integer', 'exists:media,id'];
        $validationRules['images.*.file'] = ['nullable', 'array'];
        $validationRules['images.*.file.*'] = ['nullable', 'image', "mimes:{$imageMimes}", "max:{$imageMaxSize}", "dimensions:max_width={$galleryWidth},max_height={$galleryHeight}"];
        $validationRules['images.*.title'] = ['nullable', 'array'];
        $validationRules['images.*.title.*'] = ['nullable', 'string', 'max:100'];
        $validationRules['images.*.description'] = ['nullable', 'array'];
        $validationRules['images.*.description.*'] = ['nullable', 'string', 'max:2000'];
        $validationRules['images.*.to_delete'] = ['nullable', 'boolean'];

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
            $lang = $language->code;
            $langName = $language->name;
            $messages["name.{$lang}.required"] = "Il nome del museo in {$langName} è obbligatorio.";
            $messages["name.{$lang}.string"] = "Il nome del museo in {$langName} deve essere una stringa.";
            $messages["name.{$lang}.max"] = "Il nome del museo in {$langName} non può superare 100 caratteri.";
            $messages["description.{$lang}.string"] = "La descrizione del museo in {$langName} deve essere una stringa.";
            $messages["description.{$lang}.max"] = "La descrizione del museo in {$langName} non può superare 10000 caratteri.";
            $messages["logo.{$lang}.id.integer"] = "L'ID del logo in {$langName} deve essere un numero.";
            $messages["logo.{$lang}.id.exists"] = "Il logo selezionato in {$langName} non esiste.";
            $messages["logo.file.{$lang}.image"] = "Il file del logo in {$langName} deve essere un'immagine.";
            $messages["logo.file.{$lang}"] = "Il file logo deve essere un immagine valido e non superare 2MB (Formats: jpeg, jpg, png, gif, Dimensioni massime: 1200 x 1536).";
            $messages["audio.id.{$lang}.integer"] = "L'ID dell'audio in {$langName} deve essere un numero.";
            $messages["audio.id.exists"] = "L'audio selezionato non esiste.";
            $messages["audio.file.{$lang}"] = "Il file audio deve essere un file mp3 valido e non superare 4MB.";
            $messages["images.*.id.integer"] = "L'ID dell'immagine della galleria deve essere un numero.";
            $messages["images.*.id.exists"] = "L'immagine selezionata della galleria non esiste.";
            $messages["images.*.file.{$lang}.image"] = "Il file della galleria in {$langName} deve essere un'immagine.";
            $messages["images.*.file.{$lang}.max"] = "Il file della galleria in {$langName} non deve superare {$imageMaxSize} kilobyte.";
            $messages["images.*.file.{$lang}.dimensions"] = "L'immagine della galleria in {$langName} deve avere larghezza max {$galleryWidth}px e altezza max {$galleryHeight}px.";
            $messages["images.*.file.{$lang}.mimes"] = "Il file della galleria in {$langName} deve essere un'immagine di tipo valido.";
        }

        return $messages;
    }
}
