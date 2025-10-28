<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Media;
use Illuminate\Support\Str;

class MediaSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		// 10 immagini da Unsplash
		$imageUrls = [
			'https://images.unsplash.com/photo-1506744038136-46273834b3fb',
			'https://images.unsplash.com/photo-1465101046530-73398c7f28ca',
			'https://images.unsplash.com/photo-1519125323398-675f0ddb6308',
			'https://images.unsplash.com/photo-1529626455594-4ff0802cfb7e',
			'https://images.unsplash.com/photo-1519985176271-adb1088fa94c',
			'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429',
			'https://images.unsplash.com/photo-1465101178521-c1a9136a3b99',
			'https://images.unsplash.com/photo-1517816743773-6e0fd518b4a6',
			'https://images.unsplash.com/photo-1454023492550-5696f8ff10e1',
			'https://images.unsplash.com/photo-1465101046530-73398c7f28ca',
		];

		foreach ($imageUrls as $i => $url) {
			Media::create([
				'type' => 'image',
				'url' => ['it' => $url],
				'title' => ['it' => 'Immagine ' . ($i+1)],
				'description' => ['it' => 'Immagine di esempio da Unsplash'],
			]);
		}

		// 3 audio da file di esempio (es. samplelib.com)
		$audioUrls = [
			'https://www.samplelib.com/mp3/sample-3s.mp3',
			'https://www.samplelib.com/mp3/sample-6s.mp3',
			'https://www.samplelib.com/mp3/sample-9s.mp3',
		];
		foreach ($audioUrls as $i => $url) {
			Media::create([
				'type' => 'audio',
				'url' => ['it' => $url],
				'title' => ['it' => 'Audio ' . ($i+1)],
				'description' => ['it' => 'Audio di esempio'],
			]);
		}

		// 10 QR code generati random
		for ($i = 1; $i <= 10; $i++) {
			Media::create([
				'type' => 'qr',
				'url' => ['it' => Str::random(16)],
				'title' => ['it' => 'QR Code ' . $i],
				'description' => ['it' => 'Codice QR generato casualmente'],
			]);
		}
	}
}
