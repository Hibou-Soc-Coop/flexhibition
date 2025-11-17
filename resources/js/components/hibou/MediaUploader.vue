<script setup lang="ts">
import { ref, computed, onUnmounted } from 'vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { byPrefixAndName } from '@awesome.me/kit-b4e21ddbe6/icons'
import { ArtifactImage } from '@/types/hibou'
import Label from '../label/Label.vue'

// ======================
// Costanti configurabili
// ======================
const MAX_FILES = 5
const MAX_IMAGE_SIZE = 2 * 1024 * 1024 // 2MB
const MAX_IMAGE_WIDTH = 1920
const MAX_IMAGE_HEIGHT = 1080
const OUTPUT_MIME = 'image/jpeg' as const
const OUTPUT_QUALITY = 0.86

// ======================
// Props & v-model
// ======================
const props = defineProps<{ isReadonly?: boolean }>()
// v-model scrivibile con default: array vuoto
const model = defineModel<ArtifactImage[]>({ default: [] })
const images = model

// ======================
// Stato reattivo
// ======================
const dragActive = ref(false)
let dragCount = 0
const isPicking = ref(false)
const errors = ref<string[]>([])
const fileInput = ref<HTMLInputElement | null>(null)

// ======================
// Computed helpers
// ======================
const isReadonlyCondition = computed(() => !!props.isReadonly)
// Mostra solo immagini non marcate da cancellare
const activeImages = computed(() => images.value.filter(i => !i.to_delete))
const isAtFileLimit = computed(() => activeImages.value.length >= MAX_FILES)

// ======================
// Utilità
// ======================
function pushError(msg: string) {
  if (!errors.value.includes(msg)) errors.value.push(msg)
}
function clearErrors() { errors.value = [] }

function revokeIfBlob(url?: string) {
  if (url && url.startsWith('blob:')) {
    try { URL.revokeObjectURL(url) } catch { /* noop */ }
  }
}

function isHeicLike(file: File) {
  const t = (file.type || '').toLowerCase()
  if (t === 'image/heic' || t === 'image/heif') return true
  const name = (file.name || '').toLowerCase()
  return name.endsWith('.heic') || name.endsWith('.heif')
}

function signature(f: File) {
  return `${f.name}::${f.size}::${f.lastModified}`
}

// ======================
// File picker
// ======================
function triggerFileInput() {
  if (isReadonlyCondition.value || isAtFileLimit.value) return
  isPicking.value = true
  // Evita listener doppi
  window.removeEventListener('focus', onFilePickerClosed)
  window.addEventListener('focus', onFilePickerClosed)
  fileInput.value?.click()
}
function onFilePickerClosed() {
  isPicking.value = false
  window.removeEventListener('focus', onFilePickerClosed)
}

function onFileChange(e: Event) {
  const files = (e.target as HTMLInputElement)?.files
  if (!files) return
  handleFiles(Array.from(files))
  // reset input per poter ricaricare gli stessi file
  ;(e.target as HTMLInputElement).value = ''
}

// ======================
// Drag & drop
// ======================
function onDragEnter(e: DragEvent) {
  e.preventDefault()
  dragCount++
  dragActive.value = true
}
function onDragOver(e: DragEvent) {
  e.preventDefault()
}
function onDragLeave(e: DragEvent) {
  e.preventDefault()
  dragCount = Math.max(0, dragCount - 1)
  if (dragCount === 0) dragActive.value = false
}
function onDrop(e: DragEvent) {
  e.preventDefault()
  dragCount = 0
  dragActive.value = false
  if (isReadonlyCondition.value || isAtFileLimit.value || !e.dataTransfer) return
  handleFiles(Array.from(e.dataTransfer.files))
}

// ======================
// Validazione + inserimento
// ======================
async function handleFiles(files: File[]) {
  clearErrors()

  const availableSlots = Math.max(0, MAX_FILES - activeImages.value.length)
  const filesToAdd = files.slice(0, availableSlots)
  if (filesToAdd.length === 0) {
    pushError(`Hai già ${activeImages.value.length} immagini. Massimo ${MAX_FILES} consentite.`)
    if (files.length > 0) pushError(`Hai caricato troppi file. Massimo ${MAX_FILES} consentite.`)
    return
  }

  // Dedup semplice rispetto a quanto già presente (solo immagini non marcate)
  const existingSigs = new Set(
    activeImages.value
      .map(i => (i as any).image_file ? signature((i as any).image_file as File) : i.image_path)
  )
  const uniqueToAdd: File[] = []
  for (const f of filesToAdd) {
    const sig = signature(f)
    if (existingSigs.has(sig)) {
      pushError(`Il file "${f.name}" è già stato aggiunto e verrà ignorato.`)
      continue
    }
    uniqueToAdd.push(f)
  }

  for (const file of uniqueToAdd) {
    // Tipo file
    if (!file.type || !file.type.startsWith('image/')) {
      pushError(`"${file.name}" non è un file immagine.`)
      continue
    }
    if (isHeicLike(file)) {
      pushError(`Il formato HEIC/HEIF non è supportato. Converti "${file.name}" in JPEG/PNG/WebP.`)
      continue
    }
    // Peso
    if (file.size > MAX_IMAGE_SIZE) {
      pushError(`Il file "${file.name}" è troppo pesante (max ${(MAX_IMAGE_SIZE / (1024 * 1024)).toFixed(0)}MB).`)
      continue
    }

    try {
      const reencoded = await resizeAndReencode(file, {
        maxW: MAX_IMAGE_WIDTH,
        maxH: MAX_IMAGE_HEIGHT,
        mime: OUTPUT_MIME,
        quality: OUTPUT_QUALITY
      })
      const previewUrl = URL.createObjectURL(reencoded)
      images.value.push({
        image_id: null, // Placeholder: sarà impostato dal backend
        artifact_id: 0, // Placeholder: sarà impostato dal backend
        image_file: reencoded,
        image_path: previewUrl,
        to_delete: false
      } as ArtifactImage)
    } catch (err) {
      pushError(`Errore nel processamento di "${file.name}".`)
    }
  }

  if (files.length > filesToAdd.length) {
    pushError(`Hai caricato troppi file. Massimo ${MAX_FILES} consentite.`)
  }
}

// ======================
// Resize + re-encode JPEG con strip EXIF e orientazione
// ======================
type ReencodeOpts = { maxW: number; maxH: number; mime: string; quality: number }

function fit(w: number, h: number, maxW: number, maxH: number) {
  const s = Math.min(maxW / w, maxH / h, 1)
  return { w: Math.round(w * s), h: Math.round(h * s) }
}

async function loadWithOrientation(file: File): Promise<ImageBitmap | HTMLImageElement> {
  if ('createImageBitmap' in window) {
    try {
      // @ts-ignore: imageOrientation è supportata nei browser moderni
      return await createImageBitmap(file, { imageOrientation: 'from-image' })
    } catch {
      /* fallback sotto */
    }
  }
  const url = URL.createObjectURL(file)
  try {
    const img = await new Promise<HTMLImageElement>((resolve, reject) => {
      const im = new Image()
      im.onload = () => resolve(im)
      im.onerror = reject
      im.src = url
    })
    return img
  } finally {
    URL.revokeObjectURL(url)
  }
}

async function resizeAndReencode(file: File, opts: ReencodeOpts): Promise<File> {
  const bmp = await loadWithOrientation(file)
  // width/height compatibili per ImageBitmap e HTMLImageElement
  const srcW = (bmp as any).width as number
  const srcH = (bmp as any).height as number
  const { w, h } = fit(srcW, srcH, opts.maxW, opts.maxH)

  const canvas = document.createElement('canvas')
  canvas.width = w
  canvas.height = h
  const ctx = canvas.getContext('2d')
  if (!ctx) throw new Error('Canvas non disponibile')
  ctx.drawImage(bmp as any, 0, 0, w, h)

  const blob = await new Promise<Blob>((resolve, reject) => {
    canvas.toBlob(b => (b ? resolve(b) : reject(new Error('toBlob fallita'))), opts.mime, opts.quality)
  })

  // Nuovo nome coerente con JPEG
  const base = (file.name || 'image').replace(/\.(jpe?g|png|webp|gif|bmp|tiff?)$/i, '')
  const newName = `${base}.jpg`
  return new File([blob], newName, { type: opts.mime })
}

// ======================
// Rimozione immagine (soft-delete per persistite)
// ======================
function removeImage(activeIdx: number) {
  const img = activeImages.value[activeIdx]
  if (!img) return
  const fullIdx = images.value.indexOf(img)
  if (fullIdx === -1) return

  // libera l'URL di preview se blob
  revokeIfBlob(images.value[fullIdx].image_path)

  if (images.value[fullIdx].image_id && images.value[fullIdx].image_id > 0) {
    // soft-delete: mantieni nell'array ma non mostrarla
    images.value[fullIdx].to_delete = true
  } else {
    // nuova non persistita: rimuovi proprio
    images.value.splice(fullIdx, 1)
  }
  clearErrors()
}

// ======================
// Cleanup on unmount
// ======================
onUnmounted(() => {
  images.value.forEach(i => revokeIfBlob(i.image_path))
  window.removeEventListener('focus', onFilePickerClosed)
})
</script>

<style scoped>
.group:hover img {
  transform: scale(1.05);
  transition: transform .3s;
}
</style>

<template>
  <Label class="block mb-1 font-semibold">Immagini (max {{ MAX_FILES }})</Label>

  <div class="gap-4 grid grid-cols-3 mx-auto mb-6">
    <!-- Dropzone / File Picker -->
    <div
      v-if="!isReadonlyCondition"
      role="button"
      :aria-disabled="isAtFileLimit"
      tabindex="0"
      @keydown.enter.prevent="triggerFileInput"
      @keydown.space.prevent="triggerFileInput"
      @drop="onDrop"
      @dragenter="onDragEnter"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @click="triggerFileInput"
      :class="[
        'relative group flex flex-col items-center justify-center bg-gray-50 border rounded-md min-h-[120px] w-full px-4 outline-none hover:bg-black/10',
        dragActive ? 'ring-2 ring-primary' : '',
        isAtFileLimit ? 'cursor-not-allowed' : 'cursor-pointer'
      ]"
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
      <FontAwesomeIcon
        class="group-hover:block text-4xl transition-opacity"
        :class="isPicking ? 'block' : 'hidden'"
        :icon="byPrefixAndName.fas['file-arrow-up']"
      />
      <!-- Messaggi testuali -->
      <div class="block" :class="isPicking ? 'hidden' : 'block'">
        <p class="text-muted-foreground text-center">
          <span v-if="activeImages.length === 0">
            Carica la prima immagine.
          </span>
          <span v-else-if="!isAtFileLimit">
            {{ activeImages.length }} immagine/i caricata/e<br>
            Clicca o trascina qui per caricare altre immagini.
          </span>
          <span v-else class="text-red-600">
            Limite di {{ MAX_FILES }} immagini raggiunto
          </span>
        </p>
        <p v-if="!isAtFileLimit" class="text-gray-500 text-sm text-center">
          (Massimo {{ MAX_FILES }} file, max {{ (MAX_IMAGE_SIZE / (1024 * 1024)).toFixed(0) }}MB, fino a {{ MAX_IMAGE_WIDTH }}×{{ MAX_IMAGE_HEIGHT }}, output JPEG)
        </p>
      </div>
    </div>

    <!-- Grid delle immagini -->
    <div class="gap-4 grid grid-cols-2 sm:grid-cols-4 col-span-2">
      <div
        v-for="(img, idx) in activeImages"
        :key="img.image_id || img.image_path || idx"
        class="flex bg-gray-100"
        :class="[!props.isReadonly ? 'group' : '', 'h-36']"
      >
        <!-- Immagine + pulsante elimina -->
        <div class="relative rounded-lg w-full overflow-hidden">
          <img :src="img.image_path" alt="Preview" class="w-full h-full object-cover" />
          <button
            v-if="!props.isReadonly"
            @click.prevent="removeImage(idx)"
            class="top-1 right-1 z-10 absolute flex justify-center items-center bg-white/80 opacity-0 group-hover:opacity-100 p-2 rounded-full focus:outline-none focus:ring-2 focus:ring-red-400 text-red-600 transition-opacity"
          >
            <FontAwesomeIcon :icon="byPrefixAndName.fas['trash']" />
          </button>
        </div>
      </div>
    </div>

    <!-- Messaggi di errore -->
    <ul v-if="errors.length" class="col-span-1 mt-2" aria-live="polite">
      <li class="bg-red-100 mb-4 p-2 rounded text-red-700 text-sm" v-for="(error, idx) in errors" :key="idx">{{ error }}</li>
    </ul>
  </div>
</template>
