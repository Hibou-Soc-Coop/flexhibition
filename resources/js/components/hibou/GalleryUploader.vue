<script setup lang="ts">
import { ref, watch, computed, type Ref } from 'vue'
// import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
// import { byPrefixAndName } from '@awesome.me/kit-d33824e8fe/icons'
import { type ImageItem } from '@/types/flexhibition'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger, DialogClose } from '@/components/ui/dialog'
// import TinyMCEDidascalia from './TinyMCEDidascalia.vue'

// ====== Props e constants ======
const parentImages = defineModel<ImageItem[]>()

const images = computed(() => parentImages.value ?? [])

const props = defineProps<{
    language?: string,
    isReadonly?: boolean,
    showCaption?: boolean,
    primary?: boolean,
}>()

const MAX_FILES = 5
const MAX_IMAGE_SIZE = 2 * 1024 * 1024 // 2MB
const MAX_IMAGE_WIDTH = 1920
const MAX_IMAGE_HEIGHT = 1080

// ====== Stato reattivo ======
const dragActive = ref(false)
const isPicking = ref(false)
const errorMsg = ref('')
const fileInput = ref<HTMLInputElement | null>(null)

// ====== Funzioni di utilità ======

// Apertura file picker
function triggerFileInput() {
    if (isReadonlyCondition.value || isAtFileLimit.value) return
    isPicking.value = true
    fileInput.value?.click()
    window.addEventListener('focus', onFilePickerClosed)
}

// Quando il file picker si chiude
function onFilePickerClosed() {
    isPicking.value = false
    window.removeEventListener('focus', onFilePickerClosed)
}

// Gestione drop/drag
function onDrop(e: DragEvent) {
    e.preventDefault()
    dragActive.value = false
    if (isReadonlyCondition.value || isAtFileLimit.value || !e.dataTransfer) return
    handleFiles(Array.from(e.dataTransfer.files))
}
function onDragOver(e: DragEvent) {
    e.preventDefault()
    dragActive.value = true
}
function onDragLeave(e: DragEvent) {
    e.preventDefault()
    dragActive.value = false
}

// Gestione file dal picker
function onFileChange(e: Event) {
    const files = (e.target as HTMLInputElement)?.files
    if (!files) return
    handleFiles(Array.from(files));
    // reset input per poter ricaricare gli stessi file
    (e.target as HTMLInputElement).value = ''
}

// Validazione e inserimento immagini
async function handleFiles(files: File[]) {
    errorMsg.value = ''
    const availableSlots = MAX_FILES - images.value.length
    const filesToAdd = files.slice(0, availableSlots)

    if (filesToAdd.length === 0) {
        errorMsg.value = `Hai già ${images.value.length} immagini. Massimo ${MAX_FILES} consentiti.`
        return
    }

    for (const file of filesToAdd) {
        if (!file.type.startsWith('image/')) {
            errorMsg.value = 'Solo file immagine sono consentiti.'
            continue
        }
        if (file.size > MAX_IMAGE_SIZE) {
            errorMsg.value = `Il file "${file.name}" è troppo pesante (max 2MB).`
            continue
        }

        try {
            const resizedFile = await resizeImageIfNeeded(file, MAX_IMAGE_WIDTH, MAX_IMAGE_HEIGHT)
            const previewUrl = URL.createObjectURL(resizedFile)
            images.value.push({
                media_id: null,
                media_file: resizedFile,
                media_preview: previewUrl,
                media_to_delete: false,
                media_caption: {}
            })
        } catch (err) {
            errorMsg.value = `Errore nel ridimensionamento di "${file.name}".`
        }
    }
    if (files.length > filesToAdd.length) {
        errorMsg.value = `Hai caricato troppi file. Massimo ${MAX_FILES} consentiti.`
    }
}

// Funzione di supporto per ridimensionare solo se necessario
async function resizeImageIfNeeded(file: File, maxWidth: number, maxHeight: number): Promise<File> {

    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
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
                    canvas.toBlob(blob => {
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
    images.value.splice(idx, 1)
    errorMsg.value = ''
}

// ====== Computed helpers ======
const isReadonlyCondition = computed(() => !!props.isReadonly && !!props.primary)
const isAtFileLimit = computed(() => images.value.length >= MAX_FILES)
const previewGridClass = computed(() =>
    !props.showCaption ? 'grid-cols-2 sm:grid-cols-3' : 'grid-cols-1'
)

</script>

<style scoped>
.group:hover img {
    transform: scale(1.05);
    transition: transform .3s;
}
</style>

<template>
    <div class="mx-auto w-full">
        <!-- Dropzone / File Picker -->
        <div v-if="!isReadonlyCondition && primary" @drop="onDrop" @dragover="onDragOver" @dragleave="onDragLeave"
            @click="triggerFileInput" :class="[
                'relative group flex flex-col items-center justify-center bg-gray-50 border rounded-md min-h-[120px] w-full px-4 outline-none hover:bg-black/10',
                dragActive ? 'ring-2 ring-primary' : '',
                isAtFileLimit ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
            ]" tabindex="0">
            <input ref="fileInput" type="file" accept="image/*" multiple class="hidden" @change="onFileChange"
                :disabled="isAtFileLimit" />
            <!-- Icona animata mentre si sceglie -->
            <!-- <FontAwesomeIcon class="group-hover:block text-4xl transition-opacity"
                :class="isPicking ? 'block' : 'hidden'" :icon="byPrefixAndName.fas['file-arrow-up']" /> -->
            <!-- Messaggi testuali -->
            <div class="group-hover:hidden block" :class="isPicking ? 'hidden' : 'block'">
                <p class="text-muted-foreground text-center">
                    <span v-if="images.length === 0">
                        Nessuna immagine caricata.
                    </span>
                    <span v-else-if="!isAtFileLimit">
                        {{ images.length }} immagine/i caricata/e<br>
                        Clicca o trascina qui per caricare.
                    </span>
                    <span v-else>
                        Limite di {{ MAX_FILES }} immagini raggiunto
                    </span>
                </p>
                <p v-if="!isAtFileLimit" class="text-gray-500 text-sm text-center">
                    (Massimo {{ MAX_FILES }} file)
                </p>
            </div>
        </div>

        <!-- Grid delle immagini -->
        <div class="gap-4 grid my-4" :class="previewGridClass">
            <div v-for="(img, idx) in images" :key="idx" class="flex bg-gray-100"
                :class="[!props.isReadonly ? 'group' : '', props.showCaption ? 'h-48' : 'h-36']">
                <!-- Immagine + pulsante elimina -->
                <div class="relative rounded-lg overflow-hidden" :class="!props.showCaption ? 'w-full ' : 'w-1/2 m-4'">
                    <img :src="img.media_preview" alt="Preview" class="w-full h-full object-cover" />
                    <button v-if="!props.isReadonly && props.primary" @click.prevent="removeImage(idx)"
                        class="top-1 right-1 z-10 absolute flex justify-center items-center bg-white/80 opacity-0 group-hover:opacity-100 p-2 rounded-full focus:outline-none focus:ring-2 focus:ring-red-400 text-red-600 transition-opacity">
                        <!-- <FontAwesomeIcon :icon="byPrefixAndName.fas['trash']" /> -->
                    </button>
                </div>
                <!-- Didascalia -->
                <div v-if="props.showCaption" class="gap-y-4 grid my-4 mr-4 w-1/2"
                    :class="[props.isReadonly ? 'grid-rows-1' : 'grid-rows-[auto_min-content]']">
                    <div v-html="img.media_caption[language || 'ita']"
                        class="bg-white p-2 border rounded w-full text-sm resize-none" rows="8" />
                    <Dialog v-if="!props.isReadonly">
                        <DialogTrigger class="justify-self-end bg-yellow-500 p-2 rounded-md w-auto text-white text-sm">
                            Modifica didascalia
                        </DialogTrigger>
                        <DialogContent class="min-w-[48rem]">
                            <DialogHeader>
                                <DialogTitle>Modifica didascalia</DialogTitle>
                            </DialogHeader>
                            <TinyMCEDidascalia v-if="!props.isReadonly" :id="'caption-' + idx"
                                placeholder="Aggiungi didascalia" v-model="img.media_caption[language || 'ita']" />
                            <DialogFooter>
                                <DialogClose>Salva</DialogClose>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </div>
        <!-- Messaggio di errore -->
        <p v-if="errorMsg" class="mt-2 text-red-600 text-sm">{{ errorMsg }}</p>
    </div>
</template>