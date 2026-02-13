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
        $imageMaxSizeKb = config('media.sizes.image.max', 10240);
        $audioMaxSizeKb = config('media.sizes.audio.max', 4096);
        $audioMimes = config('media.types.audio');

        $validationRules = [];

        foreach ($languages as $language) {
            if ($language->code === $primaryLanguage->code) {
                $validationRules['name.'.$language->code] = ['required', 'string', 'max:100'];
            } else {
                $validationRules['name.'.$language->code] = ['nullable', 'string', 'max:100'];
            }
        }

        $validationRules['description.*'] = ['nullable', 'string', 'max:2000'];

        $validationRules['logo'] = ['nullable', 'array'];
        $validationRules['logo.id'] = ['nullable', 'integer', 'exists:media,id'];
        $validationRules['logo.file'] = ['nullable', 'image', "max:{$imageMaxSizeKb}"];

        $validationRules['audio'] = ['nullable', 'array'];
        $validationRules['audio.*'] = ['nullable', 'array'];
        $validationRules['audio.*.id'] = ['nullable', 'integer', 'exists:media,id'];
        $validationRules['audio.*.file'] = ['nullable', 'file', "mimes:{$audioMimes}", "max:{$audioMaxSizeKb}"];

        $validationRules['images'] = ['nullable', 'array'];
        $validationRules['images.*'] = ['nullable', 'array'];
        $validationRules['images.*.id'] = ['nullable', 'integer', 'exists:media,id'];
        $validationRules['images.*.file'] = ['nullable', 'image', "max:{$imageMaxSizeKb}"];
        $validationRules['images.*.title'] = ['nullable', 'array'];
        $validationRules['images.*.title.*'] = ['nullable', 'string', 'max:100'];
        $validationRules['images.*.caption'] = ['nullable', 'array'];
        $validationRules['images.*.caption.*'] = ['nullable', 'string', 'max:2000'];

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

        $imageMaxSizeKb = config('media.sizes.image.max', 10240);
        $audioMaxSizeKb = config('media.sizes.audio.max', 4096);

        // Conversione approssimativa in MB per i messaggi
        $imageMaxMB = round($imageMaxSizeKb / 1024);
        $audioMaxMB = round($audioMaxSizeKb / 1024);

        $messages = [];

        // Messaggi localizzati (dentro il ciclo)
        foreach ($languages as $language) {
            $lang = $language->code;
            $langName = $language->name;

            $messages["name.{$lang}.required"] = "Il nome del museo in {$langName} è obbligatorio.";
            $messages["name.{$lang}.string"] = "Il nome del museo in {$langName} deve essere una stringa.";
            $messages["name.{$lang}.max"] = "Il nome del museo in {$langName} non può superare 100 caratteri.";

            $messages["description.{$lang}.string"] = "La descrizione del museo in {$langName} deve essere una stringa.";
            $messages["description.{$lang}.max"] = "La descrizione del museo in {$langName} non può superare 2000 caratteri.";
        }

        // Messaggi generali (fuori dal ciclo)
        $messages["logo.file.image"] = "Il file del logo deve essere un'immagine.";
        $messages["logo.file.max"] = "Il logo non può superare i {$imageMaxMB}MB.";

        $messages["audio"] = "Il campo audio deve essere valido.";
        $messages["audio.*"] = "Formato audio non valido.";
        $messages["audio.*.id.exists"] = "Il file audio specificato non esiste.";
        $messages["audio.*.file.mimes"] = "Il file audio deve essere di tipo: mp3, wav, aac.";
        $messages["audio.*.file.max"] = "Il file audio non può superare i {$audioMaxMB}MB.";

        $messages["images"] = "Il campo della galleria deve essere valido.";
        $messages["images.*"] = "Ogni elemento della galleria deve essere valido.";
        $messages["images.*.id.exists"] = "Il file dell'immagine specificato non esiste.";
        $messages["images.*.file.image"] = "Ogni file della galleria deve essere un'immagine.";
        $messages["images.*.file.max"] = "Le immagini della galleria non possono superare i {$imageMaxMB}MB.";

        foreach ($languages as $language) {
            $lang = $language->code;
            $langName = $language->name;

            $messages["images.*.title.{$lang}.string"] = "Il titolo dell'immagine in {$langName} deve essere una stringa.";
            $messages["images.*.title.{$lang}.max"] = "Il titolo dell'immagine in {$langName} non può superare 100 caratteri.";

            $messages["images.*.caption.{$lang}.string"] = "La didascalia dell'immagine in {$langName} deve essere una stringa.";
            $messages["images.*.caption.{$lang}.max"] = "La didascalia dell'immagine in {$langName} non può superare 2000 caratteri.";
        }

        return $messages;
    }
}
