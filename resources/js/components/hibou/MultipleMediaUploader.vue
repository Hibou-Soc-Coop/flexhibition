<script setup lang="ts">
import { MAX_FILES, MAX_IMAGE_HEIGHT, MAX_IMAGE_SIZE, MAX_IMAGE_WIDTH } from '@/constants/mediaSettings';
import { type MediaData } from '@/types/flexhibition';
import { FileUp, Trash } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// ====== Props e constants ======
const props = defineProps<{
    language?: string;
    isReadonly?: boolean;
    primary?: boolean;
}>();

const parentImages = defineModel<MediaData[]>();

const images = computed(() => parentImages.value ?? []);

// ====== Stato reattivo ======
const dragActive = ref(false);
const isPicking = ref(false);
const errorMsg = ref('');
const fileInput = ref<HTMLInputElement | null>(null);

// ====== Funzioni di utilità ======

// Apertura file picker
function triggerFileInput() {
    if (isReadonlyCondition.value || isAtFileLimit.value) return;
    isPicking.value = true;
    fileInput.value?.click();
    window.addEventListener('focus', onFilePickerClosed);
}

// Quando il file picker si chiude
function onFilePickerClosed() {
    isPicking.value = false;
    window.removeEventListener('focus', onFilePickerClosed);
}

// Gestione drop/drag
function onDrop(e: DragEvent) {
    e.preventDefault();
    dragActive.value = false;
    if (isReadonlyCondition.value || isAtFileLimit.value || !e.dataTransfer) return;
    handleFiles(Array.from(e.dataTransfer.files));
}
function onDragOver(e: DragEvent) {
    e.preventDefault();
    dragActive.value = true;
}
function onDragLeave(e: DragEvent) {
    e.preventDefault();
    dragActive.value = false;
}

// Gestione file dal picker
function onFileChange(e: Event) {
    const files = (e.target as HTMLInputElement)?.files;
    if (!files) return;
    handleFiles(Array.from(files));
    // reset input per poter ricaricare gli stessi file
    (e.target as HTMLInputElement).value = '';
}

// Validazione e inserimento immagini
async function handleFiles(files: File[]) {
    errorMsg.value = '';
    const availableSlots = MAX_FILES - images.value.length;
    const filesToAdd = files.slice(0, availableSlots);

    if (filesToAdd.length === 0) {
        errorMsg.value = `Hai già ${images.value.length} immagini. Massimo ${MAX_FILES} consentiti.`;
        return;
    }

    for (const file of filesToAdd) {
        if (!file.type.startsWith('image/')) {
            errorMsg.value = 'Solo file immagine sono consentiti.';
            continue;
        }
        if (file.size > MAX_IMAGE_SIZE) {
            errorMsg.value = `Il file "${file.name}" è troppo pesante (max 2MB).`;
            continue;
        }

        try {
            const resizedFile = await resizeImageIfNeeded(file, MAX_IMAGE_WIDTH, MAX_IMAGE_HEIGHT);
            const previewUrl = URL.createObjectURL(resizedFile);
            images.value.push({
                id: null,
                file: { it: resizedFile },
                url: null,
                title: { it: resizedFile.name },
                to_delete: false,
                media_preview: previewUrl,
            });
        } catch (err) {
            errorMsg.value = `Errore nel ridimensionamento di "${file.name}".`;
        }
    }
    if (files.length > filesToAdd.length) {
        errorMsg.value = `Hai caricato troppi file. Massimo ${MAX_FILES} consentiti.`;
    }
}

// Funzione di supporto per ridimensionare solo se necessario
async function resizeImageIfNeeded(file: File, maxWidth: number, maxHeight: number): Promise<File> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = function (e) {
            const img = new Image();
            img.onload = function () {
                let { width, height } = img;
                if (width > maxWidth || height > maxHeight) {
                    const scale = Math.min(maxWidth / width, maxHeight / height);
                    width = Math.round(width * scale);
                    height = Math.round(height * scale);
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx?.drawImage(img, 0, 0, width, height);
                    canvas.toBlob((blob) => {
                        if (blob) {
                            resolve(new File([blob], file.name, { type: file.type }));
                        } else {
                            reject(new Error('Errore nel ridimensionamento'));
                        }
                    }, file.type);
                } else {
                    resolve(file);
                }
            };
            img.onerror = reject;
            img.src = e.target?.result as string;
        };
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

// Rimozione immagine
function removeImage(idx: number) {
    images.value.splice(idx, 1);
    errorMsg.value = '';
}

// ====== Computed helpers ======
const activeLanguage = computed(() => props.language ?? 'it');
const isReadonlyCondition = computed(() => !!props.isReadonly && !!props.primary);
const isAtFileLimit = computed(() => images.value.length >= MAX_FILES);
</script>

<style scoped>
.group:hover img {
    transform: scale(1.05);
    transition: transform 0.3s;
}
</style>

<template>
    <div class="mx-auto w-full">
        <!-- Dropzone / File Picker -->
        <div v-if="!isReadonlyCondition && primary" @drop="onDrop" @dragover="onDragOver" @dragleave="onDragLeave"
            @click="triggerFileInput" :class="[
                'group relative flex min-h-[120px] w-full flex-col items-center justify-center rounded-md border bg-gray-50 px-4 outline-none hover:bg-black/10',
                dragActive ? 'ring-2 ring-primary' : '',
                isAtFileLimit ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
            ]" tabindex="0">
            <input ref="fileInput" type="file" accept="image/*" multiple class="hidden" @change="onFileChange"
                :disabled="isAtFileLimit" />
            <!-- Icona animata mentre si sceglie -->
            <FileUp class="text-4xl transition-opacity group-hover:block" :class="isPicking ? 'block' : 'hidden'" />
            <!-- Messaggi testuali -->
            <div class="block group-hover:hidden" :class="isPicking ? 'hidden' : 'block'">
                <p class="text-center text-muted-foreground">
                    <span v-if="images.length === 0"> Nessuna immagine caricata. </span>
                    <span v-else-if="!isAtFileLimit">
                        {{ images.length }} immagine/i caricata/e<br />
                        Clicca o trascina qui per caricare.
                    </span>
                    <span v-else> Limite di {{ MAX_FILES }} immagini raggiunto </span>
                </p>
                <p v-if="!isAtFileLimit" class="text-center text-sm text-gray-500">(Massimo {{ MAX_FILES }} file)</p>
            </div>
        </div>

        <!-- Grid delle immagini -->
        <div class="my-4 grid grid-cols-5 gap-4">
            <div v-for="(img, idx) in images" :key="idx" class="flex bg-gray-100"
                :class="[!props.isReadonly ? 'group' : '']">
                <div class="relative aspect-square w-full overflow-hidden rounded-md border border-gray-300">
                    <img :src="img.url?.[activeLanguage] ? `/storage/${img.url?.[activeLanguage]}` : img.media_preview" alt="Preview"
                        class="h-full w-full object-cover" />
                    <button v-if="!props.isReadonly && props.language" @click.prevent="removeImage(idx)"
                        class="absolute top-1 right-1 z-10 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 p-2 text-red-800 opacity-0 transition-opacity group-hover:opacity-100 focus:ring-2 focus:ring-red-400 focus:outline-none">
                        <Trash />
                    </button>
                </div>
            </div>
        </div>
        <!-- Messaggio di errore -->
        <p v-if="errorMsg" class="mt-2 text-sm text-red-600">{{ errorMsg }}</p>
    </div>
</template>
