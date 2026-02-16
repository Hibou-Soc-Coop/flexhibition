<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { MAX_FILES } from '@/constants/mediaSettings';
import { Language, MediaData } from '@/types/flexhibition';
import { usePage } from '@inertiajs/vue3';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { Edit, FileUp, Trash } from 'lucide-vue-next';
import { computed, ref } from 'vue';

// ====== Props e constants ======
const props = withDefaults(
    defineProps<{
        isReadonly?: boolean;
    }>(),
    {
        isReadonly: false,
    },
);

const parentImages = defineModel<MediaData[]>();

const images = computed(() => parentImages.value ?? []);

// ====== Stato reattivo ======
const dragActive = ref(false);
const isPicking = ref(false);
const errorMsg = ref('');
const fileInput = ref<HTMLInputElement | null>(null);

const isEditModalOpen = ref(false);
const editingImageIndex = ref<number>(-1);
const page = usePage();
const languages = computed(() => page.props.languages as Language[]);
const primaryLanguage = page.props.primaryLanguage as Language | null;
const primaryLanguageCode = primaryLanguage?.code || 'it';

// ====== Funzioni di utilità ======

// Apertura file picker
function triggerFileInput() {
    if (isReadonly.value || isAtFileLimit.value) return;
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
    if (isReadonly.value || isAtFileLimit.value || !e.dataTransfer) return;
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

        const previewUrl = URL.createObjectURL(file);

        images.value.push({
            id: null,
            file: file,
            url: null,
            title: { it: file.name },
            caption: { it: '' },
            media_preview: previewUrl,
        });
    }
    if (files.length > filesToAdd.length) {
        errorMsg.value = `Hai caricato troppi file. Massimo ${MAX_FILES} consentiti.`;
    }
}

// Rimozione immagine
function removeImage(idx: number) {
    images.value.splice(idx, 1);
    errorMsg.value = '';
}

function openEditCaptionModal(idx: number) {
    editingImageIndex.value = idx;
    const img = images.value[idx];
    const currentLang = primaryLanguageCode;

    if (!img.title || typeof img.title !== 'object') {
        img.title = typeof img.title === 'string' ? { [currentLang]: img.title } : {};
    }
    if (!img.caption || typeof img.caption !== 'object') {
        img.caption = typeof img.caption === 'string' ? { [currentLang]: img.caption } : {};
    }

    // Ensure all languages keys exist for reactivity
    languages.value.forEach((lang) => {
        if (img.title && typeof img.title === 'object' && !(lang.code in img.title)) {
            img.title[lang.code] = '';
        }
        if (img.caption && typeof img.caption === 'object' && !(lang.code in img.caption)) {
            img.caption[lang.code] = '';
        }
    });

    isEditModalOpen.value = true;
}

// ====== Computed helpers ======
const isReadonly = computed(() => !!props.isReadonly);
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
        <div
            v-if="!isReadonly"
            @drop="onDrop"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @click="triggerFileInput"
            :class="[
                'group relative flex min-h-30 w-full flex-col items-center justify-center rounded-md border bg-gray-50 px-4 outline-none hover:bg-black/10',
                dragActive ? 'ring-2 ring-primary' : '',
                isAtFileLimit ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
            ]"
            tabindex="0"
        >
            <input
                ref="fileInput"
                type="file"
                accept="image/*"
                multiple
                class="hidden"
                @change="onFileChange"
                :disabled="isAtFileLimit"
            />
            <!-- Icona animata mentre si sceglie -->
            <FileUp
                class="text-4xl transition-opacity group-hover:block"
                :class="isPicking ? 'block' : 'hidden'"
            />
            <!-- Messaggi testuali -->
            <div
                class="block group-hover:hidden"
                :class="isPicking ? 'hidden' : 'block'"
            >
                <p class="text-center text-muted-foreground">
                    <span v-if="images.length === 0"> Nessuna immagine caricata. </span>
                    <span v-else-if="!isAtFileLimit">
                        {{ images.length }} immagine/i caricata/e<br />
                        Clicca o trascina qui per caricare.
                    </span>
                    <span v-else> Limite di {{ MAX_FILES }} immagini raggiunto </span>
                </p>
                <p
                    v-if="!isAtFileLimit"
                    class="text-center text-sm text-gray-500"
                >
                    (Massimo {{ MAX_FILES }} file)
                </p>
            </div>
        </div>

        <!-- Grid delle immagini -->
        <div class="my-4 grid grid-cols-5 gap-4">
            <div
                v-for="(img, idx) in images"
                :key="idx"
                class="flex bg-gray-100"
                :class="[!props.isReadonly ? 'group' : '']"
            >
                <div
                    v-if="img.url"
                    class="relative aspect-square w-full overflow-hidden rounded-md border border-gray-300"
                >
                    <img
                        :src="img.url"
                        alt="Preview"
                        class="h-full w-full object-cover"
                    />
                    <button
                        v-if="!props.isReadonly"
                        @click.prevent="openEditCaptionModal(idx)"
                        class="absolute top-1 right-12 z-10 flex items-center justify-center rounded-full bg-gray-100 p-2 text-gray-800 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-gray-200 focus:ring-2 focus:ring-gray-400 focus:outline-none"
                    >
                        <Edit />
                    </button>
                    <button
                        v-if="!props.isReadonly"
                        @click.prevent="removeImage(idx)"
                        class="absolute top-1 right-1 z-10 flex items-center justify-center rounded-full bg-gray-100 p-2 text-red-800 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-gray-200 focus:ring-2 focus:ring-red-400 focus:outline-none"
                    >
                        <Trash />
                    </button>
                </div>
            </div>
        </div>
        <!-- Messaggio di errore -->
        <p
            v-if="errorMsg"
            class="mt-2 text-sm text-red-600"
        >
            {{ errorMsg }}
        </p>
    </div>
    <!-- Modal di editing didascalia -->
    <Dialog v-model:open="isEditModalOpen">
        <DialogContent class="max-h-[85vh] max-w-[80vw] min-w-[60vw] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Modifica Dettagli Immagine</DialogTitle>
                <DialogDescription>
                    Inserisci titolo e didascalia per ogni lingua supportata.
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="
                    editingImageIndex !== null &&
                    editingImageIndex > -1 &&
                    images[editingImageIndex]
                "
                class="space-y-6 py-4"
            >
                <Tabs
                    default-value="it"
                    :unmount-on-hide="false"
                    class="grid w-full grid-cols-[15%_auto] gap-8"
                    orientation="vertical"
                >
                    <TabsList
                        class="grid h-fit w-full grid-cols-1 gap-2 border-r border-gray-200 pr-4 dark:border-gray-700"
                    >
                        <TabsTrigger
                            v-for="language in languages"
                            :key="language.code"
                            :value="language.code"
                            class="w-full justify-start dark:text-gray-300 dark:data-[state=active]:bg-gray-700"
                        >
                            {{ language.name }}
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent
                        class="mt-1"
                        v-for="language in languages"
                        :key="language.code"
                        :value="language.code"
                    >
                        <Label class="mb-2 block font-semibold dark:text-gray-200"
                            >Titolo ({{ language.name }})</Label
                        >
                        <Input
                            class="mb-4 dark:bg-gray-700 dark:text-white"
                            v-model="
                                (images[editingImageIndex].title as Record<string, string>)[
                                    language.code
                                ]
                            "
                        />
                        <Label class="mb-2 block font-semibold dark:text-gray-200"
                            >Didascalia ({{ language.name }})</Label
                        >
                        <div class="mb-4 rounded-md bg-white text-black dark:bg-gray-700">
                            <QuillEditor
                                v-model:content="
                                    (images[editingImageIndex].caption as Record<string, string>)[
                                        language.code
                                    ]
                                "
                                content-type="html"
                            />
                        </div>
                    </TabsContent>
                </Tabs>
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="secondary"
                    @click="isEditModalOpen = false"
                >
                    Chiudi
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
