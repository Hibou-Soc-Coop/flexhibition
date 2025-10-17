# Models Eloquent - Documentazione

## 📦 Models Creati

Tutti i model includono:
- ✅ Trait `HasTranslations` di Spatie (dove necessario)
- ✅ Relazioni Eloquent complete
- ✅ PHPDoc annotations
- ✅ Casts appropriati
- ✅ Metodi helper utili

---

## 1. **Language**

Model per la gestione delle lingue supportate.

```php
Language::create([
    'name' => 'Italiano',
    'code' => 'it',
]);
```

**Relazioni:**
- `media()` - HasMany → Media

---

## 2. **Content**

Model per contenuti generici traducibili.

```php
$content = Content::create([
    'content' => [
        'it' => 'Testo in italiano',
        'en' => 'English text',
    ],
]);

// Accesso traduzione
echo $content->content; // Lingua corrente app
echo $content->getTranslation('content', 'en'); // Specifica
```

**Campi Traducibili:**
- `content`

---

## 3. **Media**

Model per file multimediali (immagini, video, audio, documenti, QR codes).

```php
$media = Media::create([
    'type' => 'image',
    'url' => [
        'it' => 'https://example.com/image-it.jpg',
        'en' => 'https://example.com/image-en.jpg',
    ],
    'title' => [
        'it' => 'Titolo Immagine',
        'en' => 'Image Title',
    ],
    'description' => [
        'it' => 'Descrizione...',
        'en' => 'Description...',
    ],
]);
```

**Tipi Supportati:**
- `image`
- `video`
- `audio`
- `document`
- `qr`

**Campi Traducibili:**
- `url`
- `title`
- `description`

**Relazioni:**
- `museums()` - BelongsToMany → Museum (via museum_images)
- `posts()` - BelongsToMany → Post (via post_images)
- `exhibitions()` - BelongsToMany → Exhibition (via exhibition_images)

---

## 4. **Museum**

Model per musei.

```php
$museum = Museum::create([
    'name' => [
        'it' => 'Museo Nazionale',
        'en' => 'National Museum',
    ],
    'description' => [
        'it' => 'Descrizione del museo...',
        'en' => 'Museum description...',
    ],
    'logo_id' => 1,
    'audio_id' => 2,
]);

// Relazioni
$museum->images()->attach([1, 2, 3]);
$museum->qrCodes; // Tutti i QR codes del museo
```

**Campi Traducibili:**
- `name`
- `description`

**Relazioni:**
- `logo()` - BelongsTo → Media
- `audio()` - BelongsTo → Media
- `images()` - BelongsToMany → Media
- `qrCodes()` - HasMany → QrCode
- `exhibitions()` - HasMany → Exhibition

---

## 5. **QrCode** 🔑

Model per QR codes riutilizzabili.

```php
$qrCode = QrCode::create([
    'name' => 'QR-A1',
    'type' => 'post',
    'museum_id' => 1,
    'qr_image_id' => 5, // Media con type='qr'
]);

// URL dinamico (accessor)
echo $qrCode->url; // route('posts.show', $post) o route('exhibitions.show', $exhibition)

// Helper methods
if ($qrCode->isAvailable()) {
    // QR code non ancora assegnato
}

if ($qrCode->isAssigned()) {
    // QR code già associato a un post o exhibition
}
```

**Tipi:**
- `post`
- `exhibition`

**Relazioni:**
- `museum()` - BelongsTo → Museum
- `qrImage()` - BelongsTo → Media
- `post()` - HasOne → Post (inversa)
- `exhibition()` - HasOne → Exhibition (inversa)

**Accessor:**
- `url` - Genera dinamicamente l'URL in base all'associazione

**Metodi Helper:**
- `isAssigned()` - Verifica se il QR è associato
- `isAvailable()` - Verifica se il QR è disponibile

---

## 6. **Post**

Model per post/opere esposte.

```php
$post = Post::create([
    'title' => [
        'it' => 'Opera d\'Arte',
        'en' => 'Artwork',
    ],
    'content' => [
        'it' => 'Descrizione dell\'opera...',
        'en' => 'Artwork description...',
    ],
    'audio_id' => 3,
    'qr_code_id' => 1,
]);

// Associa immagini
$post->images()->attach([1, 2, 3]);

// Associa a exhibitions
$post->exhibitions()->attach([1, 2]);
```

**Campi Traducibili:**
- `title`
- `content`

**Relazioni:**
- `audio()` - BelongsTo → Media
- `qrCode()` - BelongsTo → QrCode
- `images()` - BelongsToMany → Media
- `exhibitions()` - BelongsToMany → Exhibition

---

## 7. **Exhibition**

Model per mostre/esposizioni.

```php
$exhibition = Exhibition::create([
    'museum_id' => 1,
    'qr_code_id' => 2,
    'name' => [
        'it' => 'Mostra d\'Arte Contemporanea',
        'en' => 'Contemporary Art Exhibition',
    ],
    'description' => [
        'it' => 'Descrizione mostra...',
        'en' => 'Exhibition description...',
    ],
    'credits' => [
        'it' => 'Curatore: Mario Rossi',
        'en' => 'Curator: Mario Rossi',
    ],
    'audio_id' => 4,
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31',
    'is_archived' => false,
]);

// Associa immagini
$exhibition->images()->attach([5, 6, 7]);

// Associa post
$exhibition->posts()->attach([1, 2, 3]);

// Helper methods
if ($exhibition->isActive()) {
    // Mostra attiva (non archiviata e nelle date)
}

// Scopes
Exhibition::active()->get(); // Solo mostre attive
Exhibition::archived()->get(); // Solo mostre archiviate
```

**Campi Traducibili:**
- `name`
- `description`
- `credits`

**Relazioni:**
- `museum()` - BelongsTo → Museum
- `qrCode()` - BelongsTo → QrCode
- `audio()` - BelongsTo → Media
- `images()` - BelongsToMany → Media
- `posts()` - BelongsToMany → Post

**Metodi Helper:**
- `isActive()` - Verifica se la mostra è attiva

**Scopes:**
- `active()` - Filtra mostre attive
- `archived()` - Filtra mostre archiviate

---

## 🌍 Utilizzo delle Traduzioni

### Impostare la Lingua

```php
// Nel middleware o controller
app()->setLocale('it');

// O tramite config
config(['app.locale' => 'en']);
```

### Salvare Traduzioni

```php
// Metodo 1: Array completo
$post->title = [
    'it' => 'Titolo Italiano',
    'en' => 'English Title',
    'fr' => 'Titre Français',
];
$post->save();

// Metodo 2: Singola traduzione
$post->setTranslation('title', 'it', 'Titolo Italiano');
$post->setTranslation('title', 'en', 'English Title');
$post->save();
```

### Recuperare Traduzioni

```php
// Lingua corrente
echo $post->title; // Usa app()->getLocale()

// Lingua specifica
echo $post->getTranslation('title', 'it');
echo $post->getTranslation('title', 'en');

// Tutte le traduzioni
$translations = $post->getTranslations('title');
// ['it' => 'Titolo', 'en' => 'Title', 'fr' => 'Titre']
```

### Fallback

```php
// Se la traduzione non esiste, usa il fallback
config(['app.fallback_locale' => 'en']);

// Se 'fr' non esiste, userà 'en'
echo $post->getTranslation('title', 'fr');
```

---

## 🔍 Query Esempi

### Filtrare Exhibition Attive

```php
$activeExhibitions = Exhibition::active()
    ->with(['museum', 'posts', 'images'])
    ->get();
```

### Trovare QR Codes Disponibili

```php
$availableQrCodes = QrCode::whereDoesntHave('post')
    ->whereDoesntHave('exhibition')
    ->where('type', 'post')
    ->where('museum_id', 1)
    ->get();

// Oppure usando il metodo helper
$qrCodes = QrCode::where('museum_id', 1)
    ->get()
    ->filter(fn($qr) => $qr->isAvailable());
```

### Post con Tutte le Relazioni

```php
$post = Post::with([
    'audio',
    'qrCode.museum',
    'images',
    'exhibitions.museum'
])->find(1);
```

### Museum con Statistiche

```php
$museum = Museum::withCount([
    'qrCodes',
    'exhibitions',
    'qrCodes as available_qr_codes_count' => function ($query) {
        $query->whereDoesntHave('post')
              ->whereDoesntHave('exhibition');
    }
])->find(1);

echo "QR Codes totali: {$museum->qr_codes_count}";
echo "QR Codes disponibili: {$museum->available_qr_codes_count}";
```

---

## ⚡ Performance Tips

### Eager Loading

```php
// ❌ N+1 Query Problem
foreach ($posts as $post) {
    echo $post->qrCode->name; // Query per ogni post
}

// ✅ Eager Loading
$posts = Post::with('qrCode')->get();
foreach ($posts as $post) {
    echo $post->qrCode->name; // Una sola query
}
```

### Lazy Eager Loading

```php
$posts = Post::all();

// Carica le relazioni dopo
$posts->load('qrCode', 'images', 'audio');
```

---

## 🧪 Testing Esempi

```php
// Test QR Code URL Generation
$post = Post::factory()->create();
$qrCode = QrCode::factory()->create();
$post->qr_code_id = $qrCode->id;
$post->save();

$this->assertEquals(
    route('posts.show', $post),
    $qrCode->fresh()->url
);

// Test Exhibition Active Status
$exhibition = Exhibition::factory()->create([
    'start_date' => now()->subDay(),
    'end_date' => now()->addDay(),
    'is_archived' => false,
]);

$this->assertTrue($exhibition->isActive());
```

---

## 📝 Note Finali

1. **Traduzioni**: Tutti i campi JSON sono gestiti automaticamente da Spatie Translatable
2. **QR Codes**: L'URL è generato dinamicamente, non salvato nel DB
3. **Media Type**: Il tipo 'qr' è per le immagini PNG/SVG dei QR codes generati
4. **Exhibition Status**: Usa `isActive()` o lo scope `active()` per filtrare mostre attive
5. **Cascade Delete**: Le tabelle pivot eliminano automaticamente i record quando cancelli parent/child

