<!-- HMediaUploader.vue -->
<script setup lang="ts">
import { type MediaData } from '@/types/flexhibition';
import { FileUp, Trash2 } from 'lucide-vue-next';
import { computed, onUnmounted, ref } from 'vue';

// ====== Props e constants ======
const props = defineProps<{
    mimetype: string; // “image/*”, “audio/*”, ecc.
    isReadonly?: boolean;
}>();

const model = defineModel<MediaData | null>();

// ====== Stato reattivo ======
const fileInput = ref<HTMLInputElement | null>(null);
const localPreview = ref<string | null>(null);
const showConfirm = ref(false);
const isPicking = ref(false);
const dragActive = ref(false);

const isImage = computed(() => props.mimetype.includes('image'));
const isAudio = computed(() => props.mimetype.includes('audio'));

const currentPreview = computed(() => localPreview.value || model.value?.url);

const currentFileName = computed(() => {
    if (model.value?.file) return model.value.file.name;
    if (model.value?.url) return 'File presente';
    return null;
});

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

/* ----------------------------------------------------------------
 *  MAIN HANDLERS
 * ---------------------------------------------------------------- */
async function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    /* preview */
    if (localPreview.value) {
        URL.revokeObjectURL(localPreview.value);
    }
    localPreview.value = URL.createObjectURL(file);

    model.value = {
        id: null,
        file: file,
        url: null,
    };
}

function onRemoveClick() {
    if (props.isReadonly) return;
    showConfirm.value = true;
}

function confirmRemove() {
    if (localPreview.value) {
        URL.revokeObjectURL(localPreview.value);
        localPreview.value = null;
    }
    showConfirm.value = false;
    if (fileInput.value) fileInput.value.value = '';

    if (model.value) {
        model.value = {
            id: null,
            file: null,
            url: null,
        };
    }
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
    if (localPreview.value) URL.revokeObjectURL(localPreview.value);
});
</script>

<template>
    <div
         class="group relative flex min-h-30 w-full flex-col items-center justify-center rounded-md border bg-gray-50 px-4 outline-none focus-within:ring-2 focus-within:ring-primary"
         @drop="onDrop"
         @dragover="onDragOver"
         @dragleave="onDragLeave"
         :class="{ 'ring-2 ring-primary': dragActive }">
        <!-- RIMUOVI -->
        <button
                v-if="currentPreview && !isReadonly"
                type="button"
                class="absolute top-1 right-1 z-10 flex items-center justify-center rounded-full bg-white/80 p-2 text-red-600 transition hover:bg-red-100 focus:ring-2 focus:ring-red-400 focus:outline-none"
                aria-label="Rimuovi file"
                @click="onRemoveClick">
            <Trash2 />
        </button>

        <!-- INPUT -->
        <input ref="fileInput" type="file" :accept="mimetype" class="sr-only" @change="onFileChange" />

        <!-- PREVIEW / PLACEHOLDER -->
        <div class="flex w-full flex-col items-center justify-center py-4">
            <template v-if="currentPreview">
                <img v-if="isImage" :src="currentPreview" alt="Anteprima immagine" class="max-h-48 max-w-full rounded object-contain" />
                <audio v-else-if="isAudio" :src="currentPreview" controls class="w-full" />
            </template>

            <template v-if="!currentPreview && !isReadonly">
                <button
                        type="button"
                        aria-label="Carica file"
                        @click="openPicker"
                        class="absolute inset-0 flex items-center justify-center bg-black/10 transition"
                        :class="isPicking ? 'pointer-events-auto opacity-100' : (
                            'pointer-events-none opacity-0 group-hover:pointer-events-auto group-hover:opacity-100'
                        )
                            ">
                    <FileUp />
                </button>

                <div
                     class="text-center text-muted-foreground transition-opacity duration-150 select-none"
                     style="pointer-events: none"
                     :class="isPicking ? 'opacity-0' : 'opacity-100 group-hover:opacity-0'">
                    <span v-if="isImage">Nessun file immagine caricato.<br />Clicca o trascina qui per caricare.</span>
                    <span v-else-if="isAudio">Nessun file audio caricato.<br />Clicca o trascina qui per caricare.</span>
                </div>
            </template>
        </div>

        <!-- INFO -->
        <div v-if="currentFileName" class="mt-2 text-xs text-gray-500">{{ currentFileName }}</div>

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
