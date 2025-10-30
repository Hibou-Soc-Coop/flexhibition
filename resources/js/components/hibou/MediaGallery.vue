<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Button from './Button.vue';
import mediaRoutes from '@/routes/media';

interface MediaItem {
    id: number;
    type: 'image' | 'video' | 'audio' | 'document' | 'qr';
    url: Record<string, string>;
    title: Record<string, string>;
    description?: Record<string, string>;
    created_at: string;
    updated_at: string;
}

interface Props {
    mediaItems: MediaItem[];
    primaryLanguage?: { code: string; name: string };
    languages?: Array<{ code: string; name: string }>;
    onUpload?: () => void;
}

const isNewMediaOpen = defineModel('isNewMediaOpen', {
    type: Boolean,
    default: false,
});

const props = withDefaults(defineProps<Props>(), {
    mediaItems: () => [],
    primaryLanguage: () => ({ code: 'it', name: 'Italiano' }),
    languages: () => [{ code: 'it', name: 'Italiano' }],
});

// State
const layout = ref<'grid' | 'list'>('grid');
const selectedType = ref<string>('all');
const sortBy = ref<'newest' | 'oldest' | 'name-asc' | 'name-desc'>('newest');
const searchQuery = ref('');
const selectedItems = ref<Set<number>>(new Set());
const isDialogDragging = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);
const selectedFiles = ref<File[]>([]);
const uploadProgress = ref<number>(0);

// Form per ogni file da caricare
interface UploadFormData {
    type: 'image' | 'video' | 'audio';
    file: File | null;
    title: Record<string, string>;
    description: Record<string, string>;
}

const currentUploadIndex = ref<number>(0);
const isUploading = ref<boolean>(false);

// Computed
const filteredAndSortedMedia = computed(() => {
    let result = [...props.mediaItems];

    // Filtra per tipo
    if (selectedType.value !== 'all') {
        result = result.filter((item) => item.type === selectedType.value);
    }

    // Filtra per ricerca
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter((item) => {
            const title = item.title[props.primaryLanguage.code] || '';
            const description = item.description?.[props.primaryLanguage.code] || '';
            return title.toLowerCase().includes(query) || description.toLowerCase().includes(query);
        });
    }

    // Ordina
    result.sort((a, b) => {
        switch (sortBy.value) {
            case 'newest':
                return new Date(b.created_at).getTime() - new Date(a.created_at).getTime();
            case 'oldest':
                return new Date(a.created_at).getTime() - new Date(b.created_at).getTime();
            case 'name-asc':
                return (a.title[props.primaryLanguage.code] || '').localeCompare(b.title[props.primaryLanguage.code] || '');
            case 'name-desc':
                return (b.title[props.primaryLanguage.code] || '').localeCompare(a.title[props.primaryLanguage.code] || '');
            default:
                return 0;
        }
    });

    return result;
});

const hasSelectedItems = computed(() => selectedItems.value.size > 0);
const allSelected = computed(
    () => filteredAndSortedMedia.value.length > 0 && filteredAndSortedMedia.value.every((item) => selectedItems.value.has(item.id)),
);

// Methods
const toggleSelection = (id: number) => {
    if (selectedItems.value.has(id)) {
        selectedItems.value.delete(id);
    } else {
        selectedItems.value.add(id);
    }
};

const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedItems.value.clear();
    } else {
        filteredAndSortedMedia.value.forEach((item) => selectedItems.value.add(item.id));
    }
};

const deleteSelected = () => {
    if (!hasSelectedItems.value) return;

    if (confirm(`Sei sicuro di voler eliminare ${selectedItems.value.size} elemento/i?`)) {
        const ids = Array.from(selectedItems.value);

        // Se c'è un solo elemento, usa la route destroy standard
        if (ids.length === 1) {
            router.delete(mediaRoutes.destroy(ids[0]), {
                onSuccess: () => {
                    selectedItems.value.clear();
                },
            });
        }
        // else {
        //     // Per eliminazioni multiple, dovrai implementare una route apposita
        //     router.post(
        //         mediaRoutes.bulkDelete,
        //         {
        //             ids: ids,
        //         },
        //         {
        //             onSuccess: () => {
        //                 selectedItems.value.clear();
        //             },
        //         },
        //     );
        // }
    }
};

const getMediaUrl = (media: MediaItem): string => {
    return media.url[props.primaryLanguage.code] || Object.values(media.url)[0] || '';
};

const getMediaTitle = (media: MediaItem): string => {
    return media.title[props.primaryLanguage.code] || Object.values(media.title)[0] || 'Senza titolo';
};

const getMediaIcon = (type: string): string => {
    const icons: Record<string, string> = {
        audio: '🎵',
        document: '📄',
        qr: '📱',
    };
    return icons[type] || '📄';
};

// Dialog Upload Functions
const openFilePicker = () => {
    fileInputRef.value?.click();
};

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        selectedFiles.value = Array.from(target.files);
    }
};

const handleDialogDragEnter = (e: DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    isDialogDragging.value = true;
};

const handleDialogDragLeave = (e: DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.target === e.currentTarget) {
        isDialogDragging.value = false;
    }
};

const handleDialogDragOver = (e: DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
};

const handleDialogDrop = (e: DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    isDialogDragging.value = false;

    const files = e.dataTransfer?.files;
    if (files && files.length > 0) {
        selectedFiles.value = Array.from(files);
    }
};

const removeFile = (index: number) => {
    selectedFiles.value.splice(index, 1);
};

const detectFileType = (file: File): 'image' | 'video' | 'audio' | 'document' => {
    if (file.type.startsWith('image/')) return 'image';
    if (file.type.startsWith('video/')) return 'video';
    if (file.type.startsWith('audio/')) return 'audio';
    return 'document';
};

const uploadFiles = async () => {
    if (selectedFiles.value.length === 0) return;
    
    isUploading.value = true;
    currentUploadIndex.value = 0;

    // Carica i file uno alla volta
    for (let i = 0; i < selectedFiles.value.length; i++) {
        currentUploadIndex.value = i;
        const file = selectedFiles.value[i];
        const fileType = detectFileType(file);

        // Prepara il titolo per tutte le lingue (usa il nome del file senza estensione)
        const title: Record<string, string> = {};
        const description: Record<string, string> = {};
        const fileName = file.name.replace(/\.[^/.]+$/, ''); // Rimuove l'estensione
        
        props.languages.forEach((lang) => {
            title[lang.code] = fileName;
            description[lang.code] = '';
        });

        // Crea il form Inertia
        const form = useForm({
            type: fileType,
            file: file,
            title: title,
            description: description,
        });

        // Upload con promise per aspettare il completamento
        await new Promise<void>((resolve, reject) => {
            form.post(mediaRoutes.store.url(), {
                forceFormData: true,
                onProgress: (progress) => {
                    if (progress && typeof progress.percentage === 'number') {
                        uploadProgress.value = progress.percentage;
                    }
                },
                onSuccess: () => {
                    resolve();
                },
                onError: (errors) => {
                    console.error('Errore upload:', errors);
                    reject(errors);
                },
            });
        });
    }

    // Reset e chiudi
    isUploading.value = false;
    selectedFiles.value = [];
    uploadProgress.value = 0;
    isNewMediaOpen.value = false;
    
    // Chiama la callback se fornita
    if (props.onUpload) {
        props.onUpload();
    }
};

// Resetta lo stato quando il dialog si chiude
watch(isNewMediaOpen, (newValue) => {
    if (!newValue && !isUploading.value) {
        selectedFiles.value = [];
        uploadProgress.value = 0;
        currentUploadIndex.value = 0;
    }
});

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};
</script>

<template>
    <div>
        <!-- Barra superiore -->
        <div class="sticky top-0 z-10 bg-white py-4">
            <div class="flex flex-wrap items-center gap-4">
                <!-- Layout Toggle -->
                <div class="flex items-center gap-2">
                    <button
                        @click="layout = 'grid'"
                        :class="[
                            'rounded-lg p-2 transition-colors',
                            layout === 'grid' ? 'bg-blue-100 text-flex-select-600' : 'text-flex-select-400 hover:bg-gray-100',
                        ]"
                        title="Vista griglia"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"
                            />
                        </svg>
                    </button>
                    <button
                        @click="layout = 'list'"
                        :class="[
                            'rounded-lg p-2 transition-colors',
                            layout === 'list' ? 'bg-blue-100 text-flex-select-600' : 'text-flex-select-400 hover:bg-gray-100',
                        ]"
                        title="Vista lista"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>

                <!-- Filtro per tipo -->
                <select
                    v-model="selectedType"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-flex-select-400 focus:outline-none"
                >
                    <option value="all">Tutti i tipi</option>
                    <option value="image">Immagini</option>
                    <option value="video">Video</option>
                    <option value="audio">Audio</option>
                    <option value="document">Documenti</option>
                    <option value="qr">QR Code</option>
                </select>

                <!-- Ordinamento -->
                <select
                    v-model="sortBy"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-flex-select-400 focus:outline-none"
                >
                    <option value="newest">Più recenti</option>
                    <option value="oldest">Meno recenti</option>
                    <option value="name-asc">Nome A-Z</option>
                    <option value="name-desc">Nome Z-A</option>
                </select>

                <!-- Checkbox Seleziona tutto -->
                <label class="flex cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        :checked="allSelected"
                        @change="toggleSelectAll"
                        class="h-4 w-4 rounded border-gray-300 text-flex-select-400 focus:ring-flex-select-400"
                    />
                    <span class="text-sm text-gray-700">Seleziona tutto</span>
                </label>

                <!-- Bottone Elimina -->
                <button
                    @click="deleteSelected"
                    :disabled="!hasSelectedItems"
                    :class="[
                        'rounded-lg px-4 py-2 text-sm font-medium transition-colors',
                        hasSelectedItems ? 'bg-flex-delete-400 text-white hover:bg-flex-delete-600' : 'cursor-not-allowed bg-gray-200 text-gray-400',
                    ]"
                >
                    <span class="flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                        Elimina {{ selectedItems.size > 0 ? `(${selectedItems.size})` : '' }}
                    </span>
                </button>

                <!-- Campo di ricerca -->
                <div class="ml-auto max-w-md flex-1">
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cerca per titolo o descrizione..."
                            class="w-full rounded-lg border border-gray-300 py-2 pr-4 pl-10 text-sm focus:ring-2 focus:ring-flex-select-400 focus:outline-none"
                        />
                        <svg
                            class="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 transform text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:open="isNewMediaOpen">
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Carica nuovi file</DialogTitle>
                    <DialogDescription>Seleziona i file da caricare o trascinali qui</DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <!-- Barra di progresso durante l'upload -->
                    <div v-if="isUploading" class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900">
                                Caricamento file {{ currentUploadIndex + 1 }} di {{ selectedFiles.length }}...
                            </span>
                            <span class="text-gray-600">{{ Math.round(uploadProgress) }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                            <div 
                                class="h-full bg-flex-select-600 transition-all duration-300"
                                :style="{ width: `${uploadProgress}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Area di Drop/Click -->
                    <div
                        v-else
                        @click="openFilePicker"
                        @dragenter="handleDialogDragEnter"
                        @dragleave="handleDialogDragLeave"
                        @dragover="handleDialogDragOver"
                        @drop="handleDialogDrop"
                        :class="[
                            'relative flex min-h-[200px] cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed transition-all',
                            isDialogDragging
                                ? 'border-flex-select-600 bg-flex-select-50'
                                : 'border-gray-300 bg-gray-50 hover:border-flex-select-400 hover:bg-gray-100',
                        ]"
                    >
                        <input
                            ref="fileInputRef"
                            type="file"
                            multiple
                            accept="image/*,video/*,audio/*,.pdf,.doc,.docx"
                            @change="handleFileSelect"
                            class="hidden"
                        />

                        <svg class="mb-3 h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                            />
                        </svg>

                        <p class="mb-2 text-sm font-medium text-gray-900">
                            Clicca per selezionare o trascina i file qui
                        </p>
                        <p class="text-xs text-gray-500">
                            Immagini, video, audio o documenti (max 10MB per file)
                        </p>
                    </div>

                    <!-- Lista file selezionati -->
                    <div v-if="selectedFiles.length > 0" class="space-y-2">
                        <h4 class="text-sm font-medium text-gray-900">
                            File selezionati ({{ selectedFiles.length }})
                        </h4>
                        <div class="max-h-[300px] space-y-2 overflow-y-auto">
                            <div
                                v-for="(file, index) in selectedFiles"
                                :key="index"
                                class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3"
                            >
                                <!-- Icona tipo file -->
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 text-xl">
                                    {{ file.type.startsWith('image/') ? '🖼️' : file.type.startsWith('video/') ? '🎥' : file.type.startsWith('audio/') ? '🎵' : '📄' }}
                                </div>

                                <!-- Info file -->
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900">
                                        {{ file.name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ formatFileSize(file.size) }}
                                    </p>
                                </div>

                                <!-- Pulsante rimuovi -->
                                <button
                                    @click.stop="removeFile(index)"
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                    title="Rimuovi file"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <Button 
                        variant="outline" 
                        @click="isNewMediaOpen = false"
                        :disabled="isUploading"
                    >
                        Annulla
                    </Button>
                    <Button 
                        variant="primary" 
                        @click="uploadFiles"
                        :disabled="selectedFiles.length === 0 || isUploading"
                    >
                        <span v-if="isUploading">Caricamento...</span>
                        <span v-else>Carica {{ selectedFiles.length > 0 ? `(${selectedFiles.length})` : '' }}</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Griglia media -->
        <div class="relative rounded-lg border border-gray-200 bg-gray-50 p-6">
            <div v-if="filteredAndSortedMedia.length === 0" class="py-12 text-center">
                <svg class="mx-auto mb-4 h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                </svg>
                <h3 class="mb-2 text-lg font-medium text-gray-900">Nessun media trovato</h3>
                <p class="text-gray-600">
                    {{ searchQuery ? 'Prova a modificare i filtri di ricerca' : 'Inizia caricando il tuo primo file' }}
                </p>
            </div>

            <!-- Vista Griglia -->
            <div v-else-if="layout === 'grid'" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                <div
                    v-for="media in filteredAndSortedMedia"
                    :key="media.id"
                    @click="toggleSelection(media.id)"
                    :class="[
                        'group relative cursor-pointer overflow-hidden rounded-lg border-2 transition-all',
                        selectedItems.has(media.id) ? 'border-flex-select-400 shadow-select' : 'border-gray-200 hover:border-gray-300',
                    ]"
                >
                    <!-- Checkbox -->
                    <div class="absolute top-2 left-2 z-10">
                        <div
                            :class="[
                                'flex h-6 w-6 items-center justify-center rounded-md border-2 transition-all',
                                selectedItems.has(media.id) ?
                                    'border-flex-select-600 bg-flex-select-600'
                                :   'border-gray-300 bg-white group-hover:border-flex-select-400',
                            ]"
                        >
                            <svg v-if="selectedItems.has(media.id)" class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>

                    <!-- Anteprima media -->
                    <div class="flex aspect-square items-center justify-center overflow-hidden bg-gray-100">
                        <!-- Immagine -->
                        <img v-if="media.type === 'image'" :src="getMediaUrl(media)" :alt="getMediaTitle(media)" class="h-full w-full object-cover" />

                        <!-- Video -->
                        <video v-else-if="media.type === 'video'" :src="getMediaUrl(media)" class="h-full w-full object-cover" preload="metadata" />

                        <!-- Audio, Document, QR - Icone -->
                        <div v-else class="text-6xl">
                            {{ getMediaIcon(media.type) }}
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="bg-white p-2">
                        <p class="truncate text-xs font-medium text-gray-900">
                            {{ getMediaTitle(media) }}
                        </p>
                        <p class="text-xs text-gray-500 capitalize">
                            {{ media.type }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Vista Lista -->
            <div v-else class="space-y-2">
                <div
                    v-for="media in filteredAndSortedMedia"
                    :key="media.id"
                    @click="toggleSelection(media.id)"
                    :class="[
                        'flex cursor-pointer items-center gap-4 rounded-lg border-2 p-4 transition-all',
                        selectedItems.has(media.id) ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300',
                    ]"
                >
                    <!-- Checkbox -->
                    <div
                        :class="[
                            'flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-md border-2 transition-all',
                            selectedItems.has(media.id) ? 'border-blue-600 bg-blue-600' : 'border-gray-300 bg-white',
                        ]"
                    >
                        <svg v-if="selectedItems.has(media.id)" class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <!-- Anteprima -->
                    <div class="flex h-20 w-20 flex-shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100">
                        <img v-if="media.type === 'image'" :src="getMediaUrl(media)" :alt="getMediaTitle(media)" class="h-full w-full object-cover" />
                        <video v-else-if="media.type === 'video'" :src="getMediaUrl(media)" class="h-full w-full object-cover" preload="metadata" />
                        <div v-else class="text-4xl">
                            {{ getMediaIcon(media.type) }}
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate text-sm font-semibold text-gray-900">
                            {{ getMediaTitle(media) }}
                        </h3>
                        <p class="mt-1 text-xs text-gray-600 capitalize">
                            {{ media.type }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ new Date(media.created_at).toLocaleDateString('it-IT') }}
                        </p>
                    </div>

                    <!-- Badge tipo -->
                    <div class="flex-shrink-0">
                        <span
                            :class="[
                                'rounded-full px-3 py-1 text-xs font-medium',
                                media.type === 'image' ? 'bg-green-100 text-green-800'
                                : media.type === 'video' ? 'bg-purple-100 text-purple-800'
                                : media.type === 'audio' ? 'bg-blue-100 text-blue-800'
                                : media.type === 'document' ? 'bg-orange-100 text-orange-800'
                                : 'bg-gray-100 text-gray-800',
                            ]"
                        >
                            {{ media.type }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
