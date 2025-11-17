# MediaService - Documentazione

Il `MediaService` è un servizio completo per la gestione dei media multilingue nell'applicazione Flexhibition.

## Caratteristiche

- ✅ Caricamento file per più lingue
- ✅ Supporto per immagini, video, audio, documenti e QR codes
- ✅ Gestione automatica dello storage
- ✅ Collegamento media con Museum, Exhibition, Post
- ✅ Validazione estensioni e dimensioni file
- ✅ Transazioni database per sicurezza
- ✅ Rollback automatico in caso di errore

## Installazione

Il servizio è già registrato in `AppServiceProvider` ed è disponibile tramite dependency injection o facade.

## Utilizzo Base

### 1. Tramite Dependency Injection (Raccomandato)

```php
use App\Services\MediaService;

class MuseumController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    public function store(Request $request)
    {
        // Usa $this->mediaService
    }
}
```

### 2. Tramite Facade

```php
use App\Facades\MediaService;

$media = MediaService::createMedia(/* ... */);
```

### 3. Tramite App Container

```php
$mediaService = app(MediaService::class);
```

## Metodi Principali

### Creare un Media con Upload File

```php
use App\Services\MediaService;

// Nel controller
public function store(Request $request, MediaService $mediaService)
{
    // File caricati dal form per ogni lingua
    $files = [
        'it' => $request->file('logo_it'),
        'en' => $request->file('logo_en'),
    ];

    // Titoli per ogni lingua
    $titles = [
        'it' => 'Logo del Museo',
        'en' => 'Museum Logo',
    ];

    // Descrizioni (opzionale)
    $descriptions = [
        'it' => 'Logo ufficiale del museo',
        'en' => 'Official museum logo',
    ];

    // Crea il media
    $media = $mediaService->createMedia(
        type: 'image',
        files: $files,
        titles: $titles,
        descriptions: $descriptions,
        disk: 'public', // opzionale, default: 'public'
        folder: 'museums/logos' // opzionale, usa cartella predefinita se null
    );

    return $media->id;
}
```

### Creare Media da URL Esistenti

Utile per seeders o quando i file sono già presenti:

```php
$media = $mediaService->createMediaFromUrls(
    type: 'image',
    urls: [
        'it' => '/sample-data/images/museo_logo.jpg',
        'en' => '/sample-data/images/museo_logo.jpg',
    ],
    titles: [
        'it' => 'Logo Museo',
        'en' => 'Museum Logo',
    ],
    descriptions: [
        'it' => 'Logo del museo',
        'en' => 'Museum logo',
    ]
);
```

### Aggiornare un Media

```php
// Aggiorna solo i titoli
$media = $mediaService->updateMedia(
    mediaId: 1,
    titles: [
        'it' => 'Nuovo Titolo',
        'en' => 'New Title',
    ]
);

// Aggiorna i file per alcune lingue
$media = $mediaService->updateMedia(
    mediaId: 1,
    files: [
        'it' => $request->file('new_logo_it'),
    ],
    titles: [
        'it' => 'Nuovo Logo',
        'en' => 'New Logo',
    ]
);
```

### Eliminare un Media

```php
// Elimina il media e tutti i file associati
$deleted = $mediaService->deleteMedia(mediaId: 1);
```

## Gestione Relazioni

### Collegare Media a Museum

```php
// Collega singolo media (immagine) al museo
$mediaService->attachToMuseum(museumId: 1, mediaId: 5);

// Scollega media dal museo
$mediaService->detachFromMuseum(museumId: 1, mediaId: 5);

// Sincronizza tutte le immagini del museo (sostituisce le esistenti)
$mediaService->syncMuseumImages(
    museumId: 1,
    mediaIds: [5, 6, 7, 8] // IDs delle immagini da associare
);
```

### Collegare Media a Exhibition

```php
$mediaService->attachToExhibition(exhibitionId: 1, mediaId: 5);
$mediaService->detachFromExhibition(exhibitionId: 1, mediaId: 5);
$mediaService->syncExhibitionImages(exhibitionId: 1, mediaIds: [5, 6, 7]);
```

### Collegare Media a Post

```php
$mediaService->attachToPost(postId: 1, mediaId: 5);
$mediaService->detachFromPost(postId: 1, mediaId: 5);
$mediaService->syncPostImages(postId: 1, mediaIds: [5, 6]);
```

## Validazione File

### Validare Tipo di File

```php
$file = $request->file('image');
$isValid = $mediaService->validateFileType($file, 'image');

if (!$isValid) {
    return back()->withErrors(['image' => 'Tipo di file non valido']);
}
```

### Ottenere Estensioni Consentite

```php
$allowedExtensions = $mediaService->getAllowedExtensions('image');
// Risultato: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']
```

### Ottenere Dimensione Massima

```php
$maxSize = $mediaService->getMaxFileSize('image');
// Risultato: 5120 (KB = 5MB)
```

## Formattazione per Frontend

```php
$media = Media::find(1);
$formattedMedia = $mediaService->formatMediaForFrontend($media);

// Risultato:
// [
//     'id' => 1,
//     'type' => 'image',
//     'url' => ['it' => '/storage/...', 'en' => '/storage/...'],
//     'title' => ['it' => 'Titolo', 'en' => 'Title'],
//     'description' => ['it' => 'Desc', 'en' => 'Desc'],
// ]
```

## Esempio Completo: Store Museum con Media

```php
use App\Services\MediaService;
use App\Models\Museum;
use Illuminate\Support\Facades\DB;

class MuseumController extends Controller
{
    public function __construct(
        protected MediaService $mediaService
    ) {}

    public function store(Request $request)
    {
        // Validazione
        $validated = $request->validate([
            'name_it' => 'required|string',
            'name_en' => 'required|string',
            'description_it' => 'nullable|string',
            'description_en' => 'nullable|string',
            'logo_it' => 'required|image|max:5120',
            'logo_en' => 'required|image|max:5120',
            'audio_it' => 'nullable|file|mimes:mp3,wav|max:10240',
            'audio_en' => 'nullable|file|mimes:mp3,wav|max:10240',
            'images_it.*' => 'required|image|max:5120',
            'images_en.*' => 'required|image|max:5120',
        ]);

        DB::beginTransaction();

        try {
            // 1. Crea il logo
            $logo = $this->mediaService->createMedia(
                type: 'image',
                files: [
                    'it' => $request->file('logo_it'),
                    'en' => $request->file('logo_en'),
                ],
                titles: [
                    'it' => 'Logo - ' . $validated['name_it'],
                    'en' => 'Logo - ' . $validated['name_en'],
                ]
            );

            // 2. Crea l'audio (se presente)
            $audio = null;
            if ($request->hasFile('audio_it') && $request->hasFile('audio_en')) {
                $audio = $this->mediaService->createMedia(
                    type: 'audio',
                    files: [
                        'it' => $request->file('audio_it'),
                        'en' => $request->file('audio_en'),
                    ],
                    titles: [
                        'it' => 'Audio guida - ' . $validated['name_it'],
                        'en' => 'Audio guide - ' . $validated['name_en'],
                    ]
                );
            }

            // 3. Crea il museo
            $museum = Museum::create([
                'name' => [
                    'it' => $validated['name_it'],
                    'en' => $validated['name_en'],
                ],
                'description' => [
                    'it' => $validated['description_it'],
                    'en' => $validated['description_en'],
                ],
                'logo_id' => $logo->id,
                'audio_id' => $audio?->id,
            ]);

            // 4. Crea le immagini della galleria
            $imagesIt = $request->file('images_it') ?? [];
            $imagesEn = $request->file('images_en') ?? [];
            $imageIds = [];

            for ($i = 0; $i < count($imagesIt); $i++) {
                $imageMedia = $this->mediaService->createMedia(
                    type: 'image',
                    files: [
                        'it' => $imagesIt[$i],
                        'en' => $imagesEn[$i],
                    ],
                    titles: [
                        'it' => "Immagine " . ($i + 1) . " - " . $validated['name_it'],
                        'en' => "Image " . ($i + 1) . " - " . $validated['name_en'],
                    ]
                );

                $imageIds[] = $imageMedia->id;
            }

            // 5. Collega le immagini al museo
            $this->mediaService->syncMuseumImages($museum->id, $imageIds);

            DB::commit();

            return redirect()
                ->route('museums.show', $museum)
                ->with('success', 'Museo creato con successo!');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors(['error' => 'Errore durante la creazione del museo: ' . $e->getMessage()]);
        }
    }
}
```

## Tipi di Media Supportati

| Tipo | Valore | Estensioni Consentite | Max Size |
|------|--------|----------------------|----------|
| Immagine | `image` | jpg, jpeg, png, gif, webp, svg | 5MB |
| Video | `video` | mp4, avi, mov, wmv, flv, webm | 100MB |
| Audio | `audio` | mp3, wav, ogg, flac, m4a, aac | 10MB |
| Documento | `document` | pdf, doc, docx, xls, xlsx, ppt, pptx, txt | 10MB |
| QR Code | `qr` | png, jpg, jpeg, svg | 2MB |

## Cartelle di Storage Predefinite

- Immagini: `storage/app/public/media/images/`
- Video: `storage/app/public/media/videos/`
- Audio: `storage/app/public/media/audio/`
- Documenti: `storage/app/public/media/documents/`
- QR Codes: `storage/app/public/media/qr-codes/`

## Best Practices

1. **Usa sempre le transazioni** quando crei entità con media multipli
2. **Valida i file** prima di passarli al MediaService
3. **Gestisci gli errori** con try-catch per rollback automatico
4. **Usa dependency injection** invece della facade per testabilità
5. **Specifica cartelle personalizzate** per organizzare meglio i file

## Note Importanti

- I file vengono rinominati con UUID per evitare conflitti
- In caso di errore, i file caricati vengono automaticamente eliminati
- Le eliminazioni sono soft: rimuovono i file fisici e i record DB
- Le operazioni sono transazionali per garantire consistenza dei dati
