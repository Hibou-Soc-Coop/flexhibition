# Schema Database con Spatie Translatable

## Panoramica

Schema database per gestione mostre museali con sistema di QR codes riutilizzabili.
Utilizza **Spatie/laravel-translatable** per la gestione delle traduzioni tramite campi JSON.

## Modifiche Principali

### ❌ Rimosso (rispetto allo schema originale)
- Tabella `translated_contents` 
- Tabella `translated_media`
- Tabella `museum_points` (sostituita da `qr_codes`)
- Campi `original_language_id` e `original_content`
- Gestione manuale delle traduzioni

### ✅ Aggiunto
- Campi JSON per tutti i contenuti traducibili
- Tabella `qr_codes` per gestire QR codes riutilizzabili
- Tabella `exhibition_images` per gestire multiple immagini per exhibition
- Spatie Translatable gestisce automaticamente le traduzioni

## Struttura delle Tabelle

### 1. **languages**
Lingue supportate dall'applicazione.
```
- id
- name (es: "Italiano", "English")
- code (es: "it", "en")
- timestamps
```

### 2. **contents**
Contenuti generici traducibili (per testi riutilizzabili).
```
- id
- content (JSON) → traducibile
- timestamps
```

### 3. **media**
File multimediali con metadati traducibili.
```
- id
- type (enum: image, video, audio, document, qr)
- url (JSON) → traducibile (per URL localizzati)
- title (JSON) → traducibile
- description (JSON) → traducibile, nullable
- timestamps
```

### 4. **museums**
Musei con informazioni traducibili.
```
- id
- name (JSON) → traducibile
- description (JSON) → traducibile, nullable
- logo_id (FK → media, nullable)
- audio_id (FK → media, nullable)
- timestamps
```

### 5. **qr_codes** 🆕
QR codes fisici generati e riutilizzabili. Creati al setup, poi associati a post/exhibitions.
L'URL è generato dinamicamente dal backend in base all'associazione.
```
- id
- name (nullable) → identificativo del QR code fisico (es: "QR-A1", "QR-Sala-2")
- type (enum: post, exhibition) → per filtrare nell'UI
- museum_id (FK → museums, nullable)
- qr_image_id (FK → media, nullable) → immagine PNG/SVG del QR
- timestamps
```

**Generazione URL dinamica**:
```php
// Nel Model o Controller:
if ($qrCode->post) {
    $url = route('posts.show', $qrCode->post);
} elseif ($qrCode->exhibition) {
    $url = route('exhibitions.show', $qrCode->exhibition);
}
```

**Flusso di utilizzo**:
1. Setup: Generare N QR codes fisici e associarli al museo
2. Cliente: Scegliere un QR code disponibile nell'UI (filtrato per tipo)
3. Associazione: Collegare il QR code a un post o exhibition tramite FK
4. L'immagine del QR viene generata con l'URL dinamico

### 6. **posts**
Contenuti/opere esposte.
```
- id
- title (JSON) → traducibile
- content (JSON) → traducibile, nullable
- audio_id (FK → media, nullable)
- qr_code_id (FK → qr_codes, nullable) → QR code associato
- timestamps
```

### 7. **exhibitions**
Mostre/esposizioni.
```
- id
- museum_id (FK → museums, nullable)
- qr_code_id (FK → qr_codes, nullable) → QR code associato
- name (JSON) → traducibile
- description (JSON) → traducibile, nullable
- credits (JSON) → traducibile, nullable
- audio_id (FK → media, nullable)
- start_date (nullable)
- end_date (nullable)
- is_archived (boolean, default: false)
- timestamps
```

### 8. **Tabelle Pivot**
Relazioni many-to-many tra entità e media.

#### `museum_images`
```
- museum_id (FK → museums) CASCADE DELETE
- media_id (FK → media) CASCADE DELETE
- timestamps
- PRIMARY KEY (museum_id, media_id)
```

#### `post_images`
```
- post_id (FK → posts) CASCADE DELETE
- media_id (FK → media) CASCADE DELETE
- timestamps
- PRIMARY KEY (post_id, media_id)
```

#### `exhibition_images` 🆕
```
- exhibition_id (FK → exhibitions) CASCADE DELETE
- media_id (FK → media) CASCADE DELETE
- timestamps
- PRIMARY KEY (exhibition_id, media_id)
```

#### `exhibition_posts`
```
- exhibition_id (FK → exhibitions) CASCADE DELETE
- post_id (FK → posts) CASCADE DELETE
- timestamps
- PRIMARY KEY (exhibition_id, post_id)
```

## Come Funziona Spatie Translatable

### Nel Model
Aggiungi il trait e definisci i campi traducibili:

```php
use Spatie\Translatable\HasTranslations;

class Post extends Model
{
    use HasTranslations;

    public $translatable = ['title', 'content'];
}
```

### Salvare Traduzioni
```php
// Impostare una traduzione
$post->setTranslation('title', 'en', 'English Title');
$post->setTranslation('title', 'it', 'Titolo Italiano');
$post->save();

// Oppure in massa
$post->title = [
    'en' => 'English Title',
    'it' => 'Titolo Italiano',
    'fr' => 'Titre Français'
];
$post->save();
```

### Recuperare Traduzioni
```php
// Traduzione nella lingua corrente dell'app
$title = $post->title;

// Traduzione specifica
$title = $post->getTranslation('title', 'it');

// Tutte le traduzioni
$translations = $post->getTranslations('title');
```

### Configurare la Lingua di Default

Nel file `config/app.php`:
```php
'locale' => 'it',
'fallback_locale' => 'it',
```

## Eseguire le Migrazioni

```bash
# Esegui le migrazioni
php artisan migrate

# Popola le lingue di base
php artisan db:seed --class=LanguageSeeder
```

## Prossimi Passi

1. ✅ Migrazioni create
2. ⏳ Creare i Model Eloquent con il trait `HasTranslations`
3. ⏳ Implementare i Controller
4. ⏳ Creare le Form Request per la validazione
5. ⏳ Implementare le API/Routes

## Note Importanti

- I campi JSON in SQLite sono supportati dalla versione 3.9.0+
- Spatie Translatable salva le traduzioni come JSON nel database
- Non è necessario fare join con tabelle di traduzione
- Le query sono più performanti (niente JOIN multipli)
- Facile aggiungere nuove lingue senza modificare lo schema

## Diagramma delle Relazioni Principali

```
museums (1) ──→ (N) qr_codes
museums (1) ──→ (N) museum_images ←── (N) media
museums (1) ──→ (N) exhibitions

qr_codes (1) ──→ (1) posts
qr_codes (1) ──→ (1) exhibitions

posts (1) ──→ (N) post_images ←── (N) media
posts (N) ←──→ (N) exhibitions (via exhibition_posts)

exhibitions (1) ──→ (N) exhibition_images ←── (N) media
```

## Vantaggi rispetto allo schema precedente

1. **Semplicità**: Niente tabelle di traduzione separate
2. **Performance**: Nessun JOIN necessario per le traduzioni
3. **Manutenibilità**: Meno codice, più leggibile
4. **Flessibilità**: Facile aggiungere/rimuovere lingue
5. **Type Safety**: Lavori sempre con lo stesso model
6. **QR Codes Riutilizzabili**: Sistema flessibile per gestire QR codes fisici che possono essere riassegnati
7. **Multiple Immagini**: Exhibitions possono avere gallerie di immagini
