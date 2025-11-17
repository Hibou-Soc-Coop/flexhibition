<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Museum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MuseumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creare prima i media per il nuovo museo
        $logoMedia = Media::create([
            'type' => 'image',
            'url' => [
                'it' => '/sample-data/images/museo_logo.jpg',
                'en' => '/sample-data/images/museo_logo.jpg'
            ],
            'title' => [
                'it' => 'Logo Museo di Storia Naturale',
                'en' => 'Natural History Museum Logo'
            ],
            'description' => [
                'it' => 'Logo ufficiale del Museo di Storia Naturale di Milano',
                'en' => 'Official logo of the Natural History Museum of Milan'
            ]
        ]);

        $audioMedia = Media::create([
            'type' => 'audio',
            'url' => [
                'it' => '/sample-data/audio/164689__deleted_user_2104797__phone_voice_cartoon.wav',
                'en' => '/sample-data/audio/164689__deleted_user_2104797__phone_voice_cartoon.wav'
            ],
            'title' => [
                'it' => 'Audio guida Museo di Storia Naturale',
                'en' => 'Natural History Museum Audio Guide'
            ],
            'description' => [
                'it' => 'Introduzione audio al Museo di Storia Naturale di Milano',
                'en' => 'Audio introduction to the Natural History Museum of Milan'
            ]
        ]);

        // Creare le 4 immagini del museo
        $imageMediaIds = [];
        for ($i = 1; $i <= 4; $i++) {
            $imageMedia = Media::create([
                'type' => 'image',
                'url' => [
                    'it' => "/sample-data/images/museo_image_{$i}.jpg",
                    'en' => "/sample-data/images/museo_image_{$i}.jpg"
                ],
                'title' => [
                    'it' => "Immagine {$i} - Museo di Storia Naturale",
                    'en' => "Image {$i} - Natural History Museum"
                ],
                'description' => [
                    'it' => "Fotografia delle collezioni del Museo di Storia Naturale - Immagine {$i}",
                    'en' => "Photography of the Natural History Museum collections - Image {$i}"
                ]
            ]);
            $imageMediaIds[] = $imageMedia->id;
        }

        // Creare il nuovo museo con logo e audio
        $newMuseum = Museum::create([
            'name' => [
                'it' => 'Museo di Storia Naturale',
                'en' => 'Natural History Museum'
            ],
            'description' => [
                'it' => 'Il Museo di Storia Naturale di Milano conserva una delle più importanti collezioni naturalistiche d\'Europa.',
                'en' => 'The Natural History Museum of Milan houses one of the most important naturalistic collections in Europe.'
            ],
            'logo_id' => $logoMedia->id,
            'audio_id' => $audioMedia->id
        ]);

        // Collegare le immagini al museo tramite la tabella pivot
        foreach ($imageMediaIds as $imageId) {
            DB::table('museum_images')->insert([
                'museum_id' => $newMuseum->id,
                'media_id' => $imageId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
