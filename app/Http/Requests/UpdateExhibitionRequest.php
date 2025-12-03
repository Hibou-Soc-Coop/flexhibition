<?php

namespace App\Http\Requests;

use App\Models\Language;
use App\Helpers\LanguageHelper;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExhibitionRequest extends FormRequest
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

        // Name validation (required for primary language)
        foreach ($languages as $language) {
            if ($language->code === $primaryLanguage->code) {
                $validationRules['name.' . $language->code] = ['nullable', 'string', 'max:100'];
            } else {
                $validationRules['name.' . $language->code] = ['nullable', 'string', 'max:100'];
            }
        }

        $validationRules['description.*'] = ['nullable', 'string', 'max:10000'];

        // Museum association
        $validationRules['museum_id'] = ['nullable', 'integer', 'exists:museums,id'];

        // Date validation
        $validationRules['start_date'] = ['nullable', 'date'];
        $validationRules['end_date'] = ['nullable', 'date', 'after_or_equal:start_date'];
        $validationRules['is_archived'] = ['nullable', 'boolean'];

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
            $messages["name.{$lang}.required"] = "Il nome della collezione in {$langName} è obbligatorio.";
            $messages["name.{$lang}.string"] = "Il nome della collezione in {$langName} deve essere una stringa.";
            $messages["name.{$lang}.max"] = "Il nome della collezione in {$langName} non può superare 100 caratteri.";
            $messages["description.{$lang}.string"] = "La descrizione della collezione in {$langName} deve essere una stringa.";
            $messages["description.{$lang}.max"] = "La descrizione della collezione in {$langName} non può superare 10000 caratteri.";
            $messages["audio.file.{$lang}"] = "Il file audio deve essere un file mp3 valido e non superare {$audioMaxSize}KB.";
            $messages["images.*.file.{$lang}.image"] = "Il file della galleria in {$langName} deve essere un'immagine.";
            $messages["images.*.file.{$lang}.max"] = "Il file della galleria in {$langName} non deve superare {$imageMaxSize} kilobyte.";
            $messages["images.*.file.{$lang}.dimensions"] = "L'immagine della galleria in {$langName} deve avere larghezza max {$galleryWidth}px e altezza max {$galleryHeight}px.";
            $messages["images.*.file.{$lang}.mimes"] = "Il file della galleria in {$langName} deve essere un'immagine di tipo valido.";
        }

        // Additional custom messages
        $messages['museum_id.exists'] = 'Il museo selezionato non esiste.';
        $messages['start_date.date'] = 'La data di inizio deve essere una data valida.';
        $messages['end_date.date'] = 'La data di fine deve essere una data valida.';
        $messages['end_date.after_or_equal'] = 'La data di fine deve essere uguale o successiva alla data di inizio.';
        $messages['audio.id.exists'] = "L'audio selezionato non esiste.";
        $messages['images.*.id.exists'] = "L'immagine selezionata della galleria non esiste.";

        return $messages;
    }
}
