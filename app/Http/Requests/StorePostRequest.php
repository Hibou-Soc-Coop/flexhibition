<?php

namespace App\Http\Requests;

use App\Models\Language;
use App\Helpers\LanguageHelper;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
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
        $galleryWidth = config('mediasettings.dimensions.gallery.width');
        $galleryHeight = config('mediasettings.dimensions.gallery.height');
        $audioMaxSize = config('mediasettings.sizes.audio.max');
        $audioMimes = config('mediasettings.types.audio');

        $validationRules = [];

        // name validation (required for primary language)
        foreach ($languages as $language) {
            if ($language->code === $primaryLanguage->code) {
                $validationRules['name.' . $language->code] = ['required', 'string', 'max:200'];
            } else {
                $validationRules['name.' . $language->code] = ['nullable', 'string', 'max:200'];
            }
        }

        // description validation (optional for all languages)
        $validationRules['description.*'] = ['nullable', 'string', 'max:50000'];
        $validationRules['content.*'] = ['nullable', 'string', 'max:50000'];

        // Exhibition associations
        $validationRules['exhibition_id'] = ['nullable', 'integer', 'exists:exhibitions,id'];
        $validationRules['exhibition_id.*'] = ['integer', 'exists:exhibitions,id'];

        // Audio validation
        $validationRules['audio'] = ['nullable', 'array'];
        $validationRules['audio.id'] = ['nullable', 'integer', 'exists:media,id'];
        $validationRules['audio.file.*'] = ['nullable', 'file', "mimes:{$audioMimes}", "max:{$audioMaxSize}"];
        $validationRules['audio.title'] = ['nullable', 'array'];
        $validationRules['audio.title.*'] = ['nullable', 'string', 'max:100'];
        $validationRules['audio.description'] = ['nullable', 'array'];
        $validationRules['audio.description.*'] = ['nullable', 'string', 'max:2000'];
        $validationRules['audio.to_delete'] = ['nullable', 'boolean'];

        // Images validation
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
        $audioMaxSize = config('mediasettings.sizes.audio.max');
        $galleryWidth = config('mediasettings.dimensions.gallery.width');
        $galleryHeight = config('mediasettings.dimensions.gallery.height');
        $messages = [];

        foreach ($languages as $language) {
            $lang = $language->code;
            $langName = $language->name;
            $messages["name.{$lang}.required"] = "Il titolo del post in {$langName} è obbligatorio.";
            $messages["name.{$lang}.string"] = "Il titolo del post in {$langName} deve essere una stringa.";
            $messages["name.{$lang}.max"] = "Il titolo del post in {$langName} non può superare 200 caratteri.";
            $messages["description.{$lang}.string"] = "la descrizione del post in {$langName} deve essere una stringa.";
            $messages["description.{$lang}.max"] = "la descrizione del post in {$langName} non può superare 50000 caratteri.";
            $messages["content.{$lang}.string"] = "Il contenuto del post in {$langName} deve essere una stringa.";
            $messages["content.{$lang}.max"] = "Il contenuto del post in {$langName} non può superare 50000 caratteri.";
            $messages["audio.file.{$lang}"] = "Il file audio deve essere un file mp3 valido e non superare {$audioMaxSize}KB.";
            $messages["images.*.file.{$lang}.image"] = "Il file della galleria in {$langName} deve essere un'immagine.";
            $messages["images.*.file.{$lang}.max"] = "Il file della galleria in {$langName} non deve superare {$imageMaxSize} kilobyte.";
            $messages["images.*.file.{$lang}.dimensions"] = "L'immagine della galleria in {$langName} deve avere larghezza max {$galleryWidth}px e altezza max {$galleryHeight}px.";
            $messages["images.*.file.{$lang}.mimes"] = "Il file della galleria in {$langName} deve essere un'immagine di tipo valido.";
        }

        // Additional custom messages
        $messages['exhibition_id.exists'] = 'La collezione selezionata non esiste.';
        $messages['audio.id.exists'] = "L'audio selezionato non esiste.";
        $messages['images.*.id.exists'] = "L'immagine selezionata della galleria non esiste.";

        return $messages;
    }
}
