<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Language;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Crea un nuovo media con file caricati per ogni lingua.
     *
     * @param string $type Tipo di media: 'image', 'video', 'audio', 'document', 'qr'
     * @param array $files Array di file caricati per lingua ['it' => UploadedFile, 'en' => UploadedFile]
     * @param array $titles Array di titoli per lingua ['it' => 'Titolo', 'en' => 'Title']
     * @param array|null $descriptions Array di descrizioni per lingua ['it' => 'Desc', 'en' => 'Desc']
     * @param string $disk Nome del disk di storage (default: 'public')
     * @param string|null $folder Cartella di destinazione nel disk
     * @return Media
     * @throws \Exception
     */
    public function createMedia(
        string $type,
        array $files,
        array $titles,
        ?array $descriptions = null,
        string $disk = 'public',
        ?string $folder = null
    ): Media {
        DB::beginTransaction();

        try {
            // Validazione del tipo
            $validTypes = ['image', 'video', 'audio', 'document', 'qr'];
            if (!in_array($type, $validTypes)) {
                throw new \InvalidArgumentException("Tipo media non valido. Tipi consentiti: " . implode(', ', $validTypes));
            }

            $urls = [];
            $uploadedPaths = [];

            // Carica i file per ogni lingua
            foreach ($files as $languageCode => $file) {
                if (!$file instanceof UploadedFile) {
                    throw new \InvalidArgumentException("Il file per la lingua '{$languageCode}' non è un UploadedFile valido.");
                }

                $path = $this->storeFile($file, $type, $disk, $folder);
                $urls[$languageCode] = Storage::disk($disk)->url($path);
                $uploadedPaths[$languageCode] = $path;
            }

            // Crea il record media
            $media = Media::create([
                'type' => $type,
                'url' => $urls,
                'title' => $titles,
                'description' => $descriptions ?? [],
            ]);

            DB::commit();

            return $media;
        } catch (\Exception $e) {
            DB::rollBack();

            // Elimina i file caricati in caso di errore
            foreach ($uploadedPaths ?? [] as $path) {
                Storage::disk($disk)->delete($path);
            }

            throw $e;
        }
    }

    /**
     * Crea un media da URL esistenti (senza caricamento file).
     *
     * @param string $type Tipo di media
     * @param array $urls Array di URL per lingua ['it' => '/path/to/file', 'en' => '/path/to/file']
     * @param array $titles Array di titoli per lingua
     * @param array|null $descriptions Array di descrizioni per lingua
     * @return Media
     */
    public function createMediaFromUrls(
        string $type,
        array $urls,
        array $titles,
        ?array $descriptions = null
    ): Media {
        return Media::create([
            'type' => $type,
            'url' => $urls,
            'title' => $titles,
            'description' => $descriptions ?? [],
        ]);
    }

    /**
     * Aggiorna un media esistente.
     *
     * @param int $mediaId ID del media da aggiornare
     * @param array|null $files Nuovi file da caricare (opzionale)
     * @param array|null $titles Nuovi titoli (opzionale)
     * @param array|null $descriptions Nuove descrizioni (opzionale)
     * @param string $disk Nome del disk di storage
     * @param string|null $folder Cartella di destinazione
     * @return Media
     * @throws \Exception
     */
    public function updateMedia(
        int $mediaId,
        ?array $files = null,
        ?array $titles = null,
        ?array $descriptions = null,
        string $disk = 'public',
        ?string $folder = null
    ): Media {
        DB::beginTransaction();

        try {
            $media = Media::findOrFail($mediaId);
            $currentUrls = $media->getTranslations('url');
            $oldPaths = [];
            $newPaths = [];

            // Se ci sono nuovi file, caricali
            if ($files) {
                foreach ($files as $languageCode => $file) {
                    if ($file instanceof UploadedFile) {
                        // Salva il vecchio path per eliminarlo dopo
                        if (isset($currentUrls[$languageCode])) {
                            $oldPaths[$languageCode] = $this->getPathFromUrl($currentUrls[$languageCode], $disk);
                        }

                        // Carica il nuovo file
                        $path = $this->storeFile($file, $media->type, $disk, $folder);
                        $currentUrls[$languageCode] = Storage::disk($disk)->url($path);
                        $newPaths[$languageCode] = $path;
                    }
                }
            }

            // Aggiorna i dati
            $updateData = [];
            if ($files) {
                $updateData['url'] = $currentUrls;
            }
            if ($titles !== null) {
                $updateData['title'] = $titles;
            }
            if ($descriptions !== null) {
                $updateData['description'] = $descriptions;
            }

            if (!empty($updateData)) {
                $media->update($updateData);
            }

            // Elimina i vecchi file solo dopo l'aggiornamento riuscito
            foreach ($oldPaths as $oldPath) {
                if ($oldPath) {
                    Storage::disk($disk)->delete($oldPath);
                }
            }

            DB::commit();

            return $media->fresh();
        } catch (\Exception $e) {
            DB::rollBack();

            // Elimina i nuovi file caricati in caso di errore
            foreach ($newPaths ?? [] as $path) {
                Storage::disk($disk)->delete($path);
            }

            throw $e;
        }
    }

    /**
     * Elimina un media e i suoi file associati.
     *
     * @param int $mediaId ID del media da eliminare
     * @param string $disk Nome del disk di storage
     * @return bool
     * @throws \Exception
     */
    public function deleteMedia(int $mediaId, string $disk = 'public'): bool
    {
        DB::beginTransaction();

        try {
            $media = Media::findOrFail($mediaId);
            $urls = $media->getTranslations('url');

            // Elimina i file fisici
            foreach ($urls as $url) {
                $path = $this->getPathFromUrl($url, $disk);
                if ($path) {
                    Storage::disk($disk)->delete($path);
                }
            }

            // Elimina il record dal database
            $deleted = $media->delete();

            DB::commit();

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Collega un media a un museo (tabella pivot museum_images).
     *
     * @param int $museumId ID del museo
     * @param int $mediaId ID del media
     * @return void
     */
    public function attachToMuseum(int $museumId, int $mediaId): void
    {
        DB::table('museum_images')->insert([
            'museum_id' => $museumId,
            'media_id' => $mediaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Scollega un media da un museo.
     *
     * @param int $museumId ID del museo
     * @param int $mediaId ID del media
     * @return int Numero di record eliminati
     */
    public function detachFromMuseum(int $museumId, int $mediaId): int
    {
        return DB::table('museum_images')
            ->where('museum_id', $museumId)
            ->where('media_id', $mediaId)
            ->delete();
    }

    /**
     * Collega un media a un'esibizione (tabella pivot exhibition_images).
     *
     * @param int $exhibitionId ID dell'esibizione
     * @param int $mediaId ID del media
     * @return void
     */
    public function attachToExhibition(int $exhibitionId, int $mediaId): void
    {
        DB::table('exhibition_images')->insert([
            'exhibition_id' => $exhibitionId,
            'media_id' => $mediaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Scollega un media da un'esibizione.
     *
     * @param int $exhibitionId ID dell'esibizione
     * @param int $mediaId ID del media
     * @return int Numero di record eliminati
     */
    public function detachFromExhibition(int $exhibitionId, int $mediaId): int
    {
        return DB::table('exhibition_images')
            ->where('exhibition_id', $exhibitionId)
            ->where('media_id', $mediaId)
            ->delete();
    }

    /**
     * Collega un media a un post (tabella pivot post_images).
     *
     * @param int $postId ID del post
     * @param int $mediaId ID del media
     * @return void
     */
    public function attachToPost(int $postId, int $mediaId): void
    {
        DB::table('post_images')->insert([
            'post_id' => $postId,
            'media_id' => $mediaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Scollega un media da un post.
     *
     * @param int $postId ID del post
     * @param int $mediaId ID del media
     * @return int Numero di record eliminati
     */
    public function detachFromPost(int $postId, int $mediaId): int
    {
        return DB::table('post_images')
            ->where('post_id', $postId)
            ->where('media_id', $mediaId)
            ->delete();
    }

    /**
     * Sincronizza le immagini di un museo (rimuove vecchie e aggiunge nuove).
     *
     * @param int $museumId ID del museo
     * @param array $mediaIds Array di ID dei media da associare
     * @return void
     */
    public function syncMuseumImages(int $museumId, array $mediaIds): void
    {
        DB::transaction(function () use ($museumId, $mediaIds) {
            // Rimuovi tutte le associazioni esistenti
            DB::table('museum_images')->where('museum_id', $museumId)->delete();

            // Aggiungi le nuove associazioni
            foreach ($mediaIds as $mediaId) {
                $this->attachToMuseum($museumId, $mediaId);
            }
        });
    }

    /**
     * Sincronizza le immagini di un'esibizione.
     *
     * @param int $exhibitionId ID dell'esibizione
     * @param array $mediaIds Array di ID dei media da associare
     * @return void
     */
    public function syncExhibitionImages(int $exhibitionId, array $mediaIds): void
    {
        DB::transaction(function () use ($exhibitionId, $mediaIds) {
            DB::table('exhibition_images')->where('exhibition_id', $exhibitionId)->delete();

            foreach ($mediaIds as $mediaId) {
                $this->attachToExhibition($exhibitionId, $mediaId);
            }
        });
    }

    /**
     * Sincronizza le immagini di un post.
     *
     * @param int $postId ID del post
     * @param array $mediaIds Array di ID dei media da associare
     * @return void
     */
    public function syncPostImages(int $postId, array $mediaIds): void
    {
        DB::transaction(function () use ($postId, $mediaIds) {
            DB::table('post_images')->where('post_id', $postId)->delete();

            foreach ($mediaIds as $mediaId) {
                $this->attachToPost($postId, $mediaId);
            }
        });
    }

    /**
     * Ottiene tutti i media di un tipo specifico.
     *
     * @param string $type Tipo di media
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMediaByType(string $type)
    {
        return Media::where('type', $type)->get();
    }

    /**
     * Formatta i dati di un media per il frontend.
     *
     * @param Media|null $media
     * @return array
     */
    public function formatMediaForFrontend(?Media $media): array
    {
        if (!$media) {
            return [
                'url' => [],
                'title' => [],
                'description' => [],
            ];
        }

        return [
            'id' => $media->id,
            'type' => $media->type,
            'url' => $media->getTranslations('url'),
            'title' => $media->getTranslations('title'),
            'description' => $media->getTranslations('description'),
        ];
    }

    /**
     * Salva un file caricato nello storage.
     *
     * @param UploadedFile $file File da salvare
     * @param string $type Tipo di media
     * @param string $disk Nome del disk
     * @param string|null $folder Cartella personalizzata
     * @return string Path del file salvato
     */
    protected function storeFile(
        UploadedFile $file,
        string $type,
        string $disk = 'public',
        ?string $folder = null
    ): string {
        // Determina la cartella di destinazione
        $destinationFolder = $folder ?? $this->getDefaultFolder($type);

        // Genera un nome file unico
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;

        // Salva il file
        return $file->storeAs($destinationFolder, $filename, $disk);
    }

    /**
     * Ottiene la cartella predefinita per un tipo di media.
     *
     * @param string $type Tipo di media
     * @return string
     */
    protected function getDefaultFolder(string $type): string
    {
        return match ($type) {
            'image' => 'media/images',
            'video' => 'media/videos',
            'audio' => 'media/audio',
            'document' => 'media/documents',
            'qr' => 'media/qr-codes',
            default => 'media/other',
        };
    }

    /**
     * Estrae il path relativo da un URL completo.
     *
     * @param string $url URL completo
     * @param string $disk Nome del disk
     * @return string|null
     */
    protected function getPathFromUrl(string $url, string $disk = 'public'): ?string
    {
        $diskUrl = Storage::disk($disk)->url('');
        
        // Rimuovi l'URL base del disk per ottenere il path relativo
        if (Str::startsWith($url, $diskUrl)) {
            return Str::after($url, $diskUrl);
        }

        // Se l'URL non contiene il base URL del disk, prova a estrarre il path
        // assumendo che sia nel formato /storage/path/to/file
        if (Str::startsWith($url, '/storage/')) {
            return Str::after($url, '/storage/');
        }

        return null;
    }

    /**
     * Valida le estensioni dei file per tipo di media.
     *
     * @param UploadedFile $file
     * @param string $type
     * @return bool
     */
    public function validateFileType(UploadedFile $file, string $type): bool
    {
        $allowedExtensions = $this->getAllowedExtensions($type);
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($extension, $allowedExtensions);
    }

    /**
     * Ottiene le estensioni consentite per un tipo di media.
     *
     * @param string $type
     * @return array
     */
    public function getAllowedExtensions(string $type): array
    {
        return match ($type) {
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
            'audio' => ['mp3', 'wav', 'ogg', 'flac', 'm4a', 'aac'],
            'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
            'qr' => ['png', 'jpg', 'jpeg', 'svg'],
            default => [],
        };
    }

    /**
     * Ottiene la dimensione massima consentita per un tipo di media (in KB).
     *
     * @param string $type
     * @return int
     */
    public function getMaxFileSize(string $type): int
    {
        return match ($type) {
            'image' => 5120, // 5MB
            'video' => 102400, // 100MB
            'audio' => 10240, // 10MB
            'document' => 10240, // 10MB
            'qr' => 2048, // 2MB
            default => 5120,
        };
    }
}
