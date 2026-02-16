<!-- HMediaUploader.vue -->
<script setup lang="ts">
import { MediaDataLocalized, type Language } from '@/types/flexhibition';
import { usePage } from '@inertiajs/vue3';
import { FileUp, Trash2 } from 'lucide-vue-next';
import { computed, onUnmounted, ref } from 'vue';

// ====== Props e constants ======
const page = usePage();
const languages = computed(() => page.props.languages as Language[]);

const props = withDefaults(
    defineProps<{
        mimetype: string; // “image/*”, “audio/*”, ecc.
        isReadonly?: boolean;
        currentLang?: string;
    }>(),
    {
        currentLang: 'it',
    },
);

const model = defineModel<MediaDataLocalized>({
    default: () => ({}),
});

// ====== Stato reattivo ======
const fileInput = ref<HTMLInputElement | null>(null);
const localPreviews = ref<Record<string, string>>({});
const showConfirm = ref(false);
const isPicking = ref(false);
const dragActive = ref(false);
const error = ref<string | null>(null);

const isImage = computed(() => props.mimetype.includes('image'));
const isAudio = computed(() => props.mimetype.includes('audio'));

const currentPreview = computed(() => {
    if (localPreviews.value[props.currentLang]) {
        return localPreviews.value[props.currentLang];
    } else if (model.value?.[props.currentLang]?.url) {
        return model.value[props.currentLang].url;
    } else {
        return '';
    }
});

const currentFileName = computed(() => {
    if (model.value?.[props.currentLang]?.file) return model.value[props.currentLang].file?.name;
    if (model.value?.[props.currentLang]?.url) return 'File presente';
    return null;
});

/* ----------------------------------------------------------------
 *  WATCHERS
 * ---------------------------------------------------------------- */

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
    if (localPreviews.value[props.currentLang]) {
        URL.revokeObjectURL(localPreviews.value[props.currentLang]);
    }
    localPreviews.value[props.currentLang] = URL.createObjectURL(file);
    error.value = null;

    // Aggiornamento reattivo profondo e reset ID per nuovo file
    model.value = {
        ...model.value,
        [props.currentLang]: {
            ...model.value?.[props.currentLang],
            file: file,
            url: null,
            id: null, // Importante: resetta ID così il backend lo tratta come nuovo file
        },
    };
}

function onRemoveClick() {
    if (props.isReadonly) return;
    showConfirm.value = true;
}

function confirmRemove() {
    if (localPreviews.value[props.currentLang]) {
        URL.revokeObjectURL(localPreviews.value[props.currentLang]);
        delete localPreviews.value[props.currentLang];
    }
    error.value = null;
    showConfirm.value = false;
    if (fileInput.value) fileInput.value.value = '';

    // Svuota i valori mantenendo la chiave per informare il backend della rimozione
    model.value = {
        ...model.value,
        [props.currentLang]: {
            id: null,
            file: null,
            url: null,
        },
    };
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
    Object.values(localPreviews.value).forEach((url) => URL.revokeObjectURL(url));
});
</script>

<template>
    <div
        class="group relative flex min-h-30 w-full flex-col items-center justify-center rounded-md border bg-gray-50 px-4 outline-none focus-within:ring-2 focus-within:ring-primary"
        @drop="onDrop"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        :class="{ 'ring-2 ring-primary': dragActive }"
    >
        <!-- RIMUOVI -->
        <button
            v-if="currentPreview && !isReadonly"
            type="button"
            class="absolute top-1 right-1 z-10 flex items-center justify-center rounded-full bg-white/80 p-2 text-red-600 transition hover:bg-red-100 focus:ring-2 focus:ring-red-400 focus:outline-none"
            aria-label="Rimuovi file"
            @click="onRemoveClick"
        >
            <Trash2 />
        </button>

        <!-- INPUT -->
        <input
            ref="fileInput"
            type="file"
            :accept="mimetype"
            class="sr-only"
            @change="onFileChange"
        />

        <!-- PREVIEW / PLACEHOLDER -->
        <div class="flex w-full flex-col items-center justify-center py-4">
            <template v-if="currentPreview">
                <img
                    v-if="isImage"
                    :src="currentPreview"
                    alt="Anteprima immagine"
                    class="max-h-48 max-w-full rounded object-contain"
                />
                <audio
                    v-else-if="isAudio"
                    :src="currentPreview"
                    controls
                    class="w-full"
                />
            </template>

            <template v-if="!currentPreview && !isReadonly">
                <button
                    type="button"
                    aria-label="Carica file"
                    @click="openPicker"
                    class="absolute inset-0 flex items-center justify-center bg-black/10 transition"
                    :class="[
                        isPicking ?
                            'pointer-events-auto opacity-100'
                        :   'pointer-events-none opacity-0 group-hover:pointer-events-auto group-hover:opacity-100 focus-visible:pointer-events-auto focus-visible:opacity-100',
                    ]"
                >
                    <FileUp />
                </button>

                <div
                    class="text-center text-muted-foreground transition-opacity duration-150 select-none"
                    style="pointer-events: none"
                    :class="isPicking ? 'opacity-0' : 'opacity-100 group-hover:opacity-0'"
                >
                    <span v-if="isImage">
                        Nessuna immagine caricata per
                        {{
                            languages.find((lang) => lang.code === currentLang)?.name ||
                            currentLang
                        }}.<br />
                        Clicca o trascina qui per caricare.
                    </span>
                    <span v-else-if="isAudio"
                        >Nessun audio caricato per
                        {{
                            languages.find((lang) => lang.code === currentLang)?.name ||
                            currentLang
                        }}.<br />
                        Clicca o trascina qui per caricare.</span
                    >
                </div>
            </template>
        </div>

        <!-- INFO -->
        <div
            v-if="currentFileName"
            class="mt-2 text-xs text-gray-500"
        >
            {{ currentFileName }}
        </div>
        <div
            v-if="error"
            class="mt-1 text-xs text-red-600"
        >
            {{ error }}
        </div>

        <!-- DIALOG CONFERMA -->
        <div
            v-if="showConfirm"
            class="absolute inset-0 z-20 flex items-center justify-center bg-black/40"
        >
            <div class="flex flex-col items-center gap-2 rounded bg-white p-4 shadow-lg">
                <span class="font-semibold text-red-600"
                    >Rimuovere il file per {{ currentLang }}?</span
                >
                <div class="mt-2 flex gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-destructive"
                        @click="confirmRemove"
                        autofocus
                    >
                        Conferma
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm"
                        @click="cancelRemove"
                    >
                        Annulla
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
