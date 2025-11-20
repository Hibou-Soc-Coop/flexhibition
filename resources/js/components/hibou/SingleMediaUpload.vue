<!-- HMediaUploader.vue -->
<script setup lang="ts">
import { MAX_AUDIO_SIZE, MAX_IMAGE_HEIGHT, MAX_IMAGE_SIZE, MAX_IMAGE_WIDTH } from '@/constants/mediaSettings';
import { type MediaData } from '@/types/flexhibition';
import { FileUp, Trash2 } from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';

// ====== Props e constants ======
const props = defineProps<{
    accept: string; // “image/*”, “audio/*”, ecc.
    label?: string;
    isReadonly?: boolean;
}>();

const model = defineModel<MediaData | null>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: MediaData | null): void;
    (e: 'invalid', message: string): void;
}>();

// ====== Stato reattivo ======
const fileInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | undefined>(model.value?.media_preview);
const showConfirm = ref(false);
const isPicking = ref(false);
const dragActive = ref(false);
const fileName = ref<string | null>(null);
const error = ref<string | null>(null);

const isImage = computed(() => props.accept.includes('image'));
const isAudio = computed(() => props.accept.includes('audio'));

/* ----------------------------------------------------------------
 *  WATCHERS
 * ---------------------------------------------------------------- */
watch(
    () => model.value,
    (val) => {
        if (previewUrl.value && previewUrl.value !== val?.media_preview) {
            URL.revokeObjectURL(previewUrl.value);
        }
        previewUrl.value = val?.media_preview;
    },
);

/* ----------------------------------------------------------------
 *  HELPERS
 * ---------------------------------------------------------------- */
function openPicker() {
    if (props.isReadonly) return;
    isPicking.value = true;
    fileInput.value?.click();
    window.addEventListener('focus', onWindowFocus);
}

function onWindowFocus() {
    isPicking.value = false;
    window.removeEventListener('focus', onWindowFocus);
}

/** Ridimensiona l’immagine se eccede i limiti */
function resizeImage(file: File, maxWidth: number, maxHeight: number): Promise<File> {
    return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => {
            let { width, height } = img;
            if (width <= maxWidth && height <= maxHeight) {
                resolve(file); // già nei limiti
                return;
            }
            const ratio = Math.min(maxWidth / width, maxHeight / height);
            width = Math.round(width * ratio);
            height = Math.round(height * ratio);

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d')!.drawImage(img, 0, 0, width, height);

            canvas.toBlob(
                (blob) => {
                    if (blob) {
                        resolve(new File([blob], file.name, { type: file.type, lastModified: Date.now() }));
                    } else {
                        resolve(file); // fallback
                    }
                },
                file.type,
                0.92,
            ); // qualità 92 %
        };
        img.onerror = () => resolve(file);
        img.src = URL.createObjectURL(file);
    });
}

/* ----------------------------------------------------------------
 *  MAIN HANDLERS
 * ---------------------------------------------------------------- */
async function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    let finalFile = file;
    if (isImage.value) {
        finalFile = await resizeImage(file, MAX_IMAGE_WIDTH, MAX_IMAGE_HEIGHT);

        if (finalFile.size > MAX_IMAGE_SIZE) {
            const msg = `Il file supera il limite di ${(MAX_IMAGE_SIZE / 1024 / 1024).toFixed(1)} MB`;
            error.value = msg;
            emit('invalid', msg);
            return;
        }
    } else if (isAudio.value) {
        if (finalFile.size > MAX_AUDIO_SIZE) {
            const msg = `Il file supera il limite di ${(MAX_AUDIO_SIZE / 1024 / 1024).toFixed(1)} MB`;
            error.value = msg;
            emit('invalid', msg);
            return;
        }
    } else {
        emit('invalid', 'Formato non supportato');
        return;
    }

    /* preview */
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = URL.createObjectURL(finalFile);
    fileName.value = finalFile.name;
    error.value = null;

    model.value = {
        id: null,
        file: { it: finalFile },
        url: null,
        title: { it: finalFile.name },
        to_delete: false,
        media_preview: previewUrl.value,
    };
}

function onRemoveClick() {
    if (props.isReadonly) return;
    showConfirm.value = true;
}

function confirmRemove() {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = undefined;
    fileName.value = null;
    error.value = null;
    showConfirm.value = false;
    if (fileInput.value) fileInput.value.value = '';
    emit('update:modelValue', null);
}

function cancelRemove() {
    showConfirm.value = false;
}

function onDrop(e: DragEvent) {
    if (props.isReadonly) return;
    e.preventDefault();
    dragActive.value = false;
    if (e.dataTransfer?.files?.length) {
        onFileChange({ target: { files: e.dataTransfer.files } } as unknown as Event);
    }
}

function onDragOver(e: DragEvent) {
    if (props.isReadonly) return;
    e.preventDefault();
    dragActive.value = true;
}

function onDragLeave(e: DragEvent) {
    if (props.isReadonly) return;
    e.preventDefault();
    dragActive.value = false;
}

onUnmounted(() => {
    window.removeEventListener('focus', onWindowFocus);
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
});
</script>

<template>
    <div
        class="group relative flex min-h-[120px] w-full flex-col items-center justify-center rounded-md border bg-gray-50 px-4 outline-none focus-within:ring-2 focus-within:ring-primary"
        @drop="onDrop"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        :class="{ 'ring-2 ring-primary': dragActive }"
    >
        <!-- RIMUOVI -->
        <button
            v-if="previewUrl && !isReadonly"
            type="button"
            class="absolute top-1 right-1 z-10 flex items-center justify-center rounded-full bg-white/80 p-2 text-red-600 transition hover:bg-red-100 focus:ring-2 focus:ring-red-400 focus:outline-none"
            aria-label="Rimuovi file"
            @click="onRemoveClick"
        >
            <Trash2 />
        </button>

        <!-- INPUT -->
        <input ref="fileInput" type="file" :accept="accept" class="sr-only" @change="onFileChange" />

        <!-- PREVIEW / PLACEHOLDER -->
        <div class="flex w-full flex-col items-center justify-center py-4">
            <template v-if="previewUrl">
                <img v-if="isImage" :src="previewUrl" alt="Anteprima immagine" class="max-h-48 max-w-full rounded object-contain" />
                <audio v-else-if="isAudio" :src="previewUrl" controls class="w-full" />
            </template>

            <template v-if="!previewUrl && !isReadonly">
                <button
                    type="button"
                    aria-label="Carica file"
                    @click="openPicker"
                    class="absolute inset-0 flex items-center justify-center bg-black/10 transition"
                    :class="
                        isPicking ? 'pointer-events-auto opacity-100' : (
                            'pointer-events-none opacity-0 group-hover:pointer-events-auto group-hover:opacity-100'
                        )
                    "
                >
                    <FileUp />
                </button>

                <div
                    class="text-center text-muted-foreground transition-opacity duration-150 select-none"
                    style="pointer-events: none"
                    :class="isPicking ? 'opacity-0' : 'opacity-100 group-hover:opacity-0'"
                >
                    <span v-if="isImage">Nessun logo caricato.<br />Clicca o trascina qui per caricare.</span>
                    <span v-else-if="isAudio">Nessun audio caricato.<br />Clicca o trascina qui per caricare.</span>
                </div>
            </template>
        </div>

        <!-- INFO -->
        <div v-if="fileName" class="mt-2 text-xs text-gray-500">{{ fileName }}</div>
        <div v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</div>

        <!-- DIALOG CONFERMA -->
        <div v-if="showConfirm" class="absolute inset-0 z-20 flex items-center justify-center bg-black/40">
            <div class="flex flex-col items-center gap-2 rounded bg-white p-4 shadow-lg">
                <span class="font-semibold text-red-600">Rimuovere il file?</span>
                <div class="mt-2 flex gap-2">
                    <button type="button" class="btn btn-sm btn-destructive" @click="confirmRemove" autofocus>Conferma</button>
                    <button type="button" class="btn btn-sm" @click="cancelRemove">Annulla</button>
                </div>
            </div>
        </div>
    </div>
</template>
