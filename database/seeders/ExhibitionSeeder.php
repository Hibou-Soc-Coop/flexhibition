<?php

namespace Database\Seeders;

use App\Models\Exhibition;
use App\Models\Media;
use App\Models\Museum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExhibitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creare prima i media per il nuovo museo
        $audioMedia = Media::create([
            'type' => 'audio',
            'url' => [
                'it' => '/sample-data/audio/164689__deleted_user_2104797__phone_voice_cartoon.wav',
                'en' => '/sample-data/audio/164689__deleted_user_2104797__phone_voice_cartoon.wav'
            ],
            'title' => [
                'it' => 'Audio guida Museo Nivola',
                'en' => 'Nivola Museum Audio Guide'
            ],
            'description' => [
                'it' => 'Introduzione audio alla mostra Nivola',
                'en' => 'Audio introduction to the Nivola Exhibition'
            ]
        ]);

        // Creare le 4 immagini del museo
        $imageMediaIds = [];
            $imageMedia = Media::create([
                'type' => 'image',
                'url' => [
                    'it' => "/assets/exhibition_image_1.jpeg",
                    'en' => "/assets/exhibition_image_1.jpeg"
                ],
                'title' => [
                    'it' => "Immagine 1 - Mostra Nivola",
                    'en' => "Image 1 - Nivola Exhibition"
                ],
                'description' => [
                    'it' => "Fotografia delle collezioni del Museo Nivola - Immagine 1",
                    'en' => "Photography of the Nivola Museum collections - Image 1"
                ]
            ]);
            $imageMediaIds[] = $imageMedia->id;


        // Creare il nuovo museo con logo e audio
        $newExhibition = Exhibition::create([
            'name' => [
                'it' => 'Collezione 1 - Nivola',
                'en' => 'Nivola - Collection 1'
            ],
            'description' => [
                'it' => 'Il Museo conserva la più importante collezione al mondo delle opere di Costantino Nivola tra sculture e dipinti, più di 200 opere acquisite attraverso successive donazioni. La scelta iniziale, compiuta dalla vedova dell’artista Ruth Guggenheim, ha privilegiato l’opera scultorea di Nivola e particolarmente la fase finale del suo percorso, caratterizzata da un ritorno alla statuaria – con la serie delle Madri e delle Vedove – e ai materiali nobili della scultura tradizionale.',
                'en' => 'The Museum houses the world\'s most important collection of Costantino Nivola\'s works, including sculptures and paintings, more than 200 pieces acquired through subsequent donations. The initial selection, made by the artist\'s widow, Ruth Guggenheim, favored Nivola\'s sculptural work, particularly the final phase of his career, characterized by a return to statuary—with the Mothers and Widows series—and the noble materials of traditional sculpture.'
            ],
            'start_date' => '2024-01-01',
            'end_date' => '2030-01-01',
            'audio_id' => $audioMedia->id,
            'is_archived' => false,
            'museum_id' => 1
        ]);

        // Collegare le immagini al museo tramite la tabella pivot
        foreach ($imageMediaIds as $imageId) {
            DB::table('exhibition_images')->insert([
                'exhibition_id' => $newExhibition->id,
                'media_id' => $imageId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
