<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Museum;
use App\Services\MediaService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class MuseumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creare prima i media per il nuovo museo
        $mediaService = app(MediaService::class);

        $sourcePath = resource_path('assets/logo-trasparente.png');

        // Create temp files for each language to avoid file locking/moving issues
        $tempPathIt = tempnam(sys_get_temp_dir(), 'logo_it');
        copy($sourcePath, $tempPathIt);
        $fileIt = new UploadedFile($tempPathIt, 'logo-trasparente.png', 'image/png', null, true);

        $tempPathEn = tempnam(sys_get_temp_dir(), 'logo_en');
        copy($sourcePath, $tempPathEn);
        $fileEn = new UploadedFile($tempPathEn, 'logo-trasparente.png', 'image/png', null, true);

        $logoMedia = $mediaService->createMedia(
            'image',
            ['it' => $fileIt, 'en' => $fileEn],
            ['it' => 'Logo Museo Nivola', 'en' => 'Nivola Museum Logo'],
            ['it' => 'Logo ufficiale del Museo Nivola', 'en' => 'Official logo of the Nivola Museum']
        );

        // Creare il nuovo museo con logo e audio
        $newMuseum = Museum::create([
            'name' => [
                'it' => 'Museo Nivola',
                'en' => 'Nivola Museum'
            ],
            'description' => [
                'it' => 'Il Museo conserva la più importante collezione al mondo delle opere di Costantino Nivola tra sculture e dipinti, più di 200 opere acquisite attraverso successive donazioni. La scelta iniziale, compiuta dalla vedova dell’artista Ruth Guggenheim, ha privilegiato l’opera scultorea di Nivola e particolarmente la fase finale del suo percorso, caratterizzata da un ritorno alla statuaria – con la serie delle Madri e delle Vedove – e ai materiali nobili della scultura tradizionale.',
                'en' => 'The Museum houses the world\'s most important collection of Costantino Nivola\'s works, including sculptures and paintings, more than 200 pieces acquired through subsequent donations. The initial selection, made by the artist\'s widow, Ruth Guggenheim, favored Nivola\'s sculptural work, particularly the final phase of his career, characterized by a return to statuary—with the Mothers and Widows series—and the noble materials of traditional sculpture.'
            ],
            'logo_id' => $logoMedia->id,
            'audio_id' => null
        ]);
    }
}
