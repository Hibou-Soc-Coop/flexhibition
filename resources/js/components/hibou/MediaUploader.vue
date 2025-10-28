<!-- HMediaUploader.vue -->
<script setup lang='ts'>
import { ref, watch, computed, onUnmounted } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { byPrefixAndName } from '@awesome.me/kit-d33824e8fe/icons';
import { type ImageItem } from '@/types/flexhibition';

/* ----------------------------------------------------------------
 *  PROPS & EMITS
 * ---------------------------------------------------------------- */
const props = withDefaults(defineProps<{
  modelValue: ImageItem | null;          // v-model
  accept: string;                    // “image/*”, “audio/*”, ecc.
  label?: string;
  isReadonly?: boolean;

  /* Limiti (overrideabili) */
  maxImageFileSize?: number;
  maxAudioFileSize?: number;
  maxWidth?: number;
  maxHeight?: number;
}>(), {
  maxImageFileSize: 2 * 1024 * 1024,
  maxAudioFileSize: 5 * 1024 * 1024,
  maxWidth: 1920,
  maxHeight: 1080
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: ImageItem | null): void;
  (e: 'invalid', message: string): void;
}>();

/* ----------------------------------------------------------------
 *  STATE
 * ---------------------------------------------------------------- */
const fileInput = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | undefined>(props.modelValue?.media_preview);
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
watch(() => props.modelValue, val => {
  if (previewUrl.value && previewUrl.value !== val?.media_preview) {
    URL.revokeObjectURL(previewUrl.value);
  }
  previewUrl.value = val?.media_preview;
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

/** Ridimensiona l’immagine se eccede i limiti */
function resizeImage(file: File, maxW: number, maxH: number): Promise<File> {
  return new Promise(resolve => {
    const img = new Image();
    img.onload = () => {
      let { width, height } = img;
      if (width <= maxW && height <= maxH) {
        resolve(file);        // già nei limiti
        return;
      }
      const ratio = Math.min(maxW / width, maxH / height);
      width = Math.round(width * ratio);
      height = Math.round(height * ratio);

      const canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;
      canvas.getContext('2d')!.drawImage(img, 0, 0, width, height);

      canvas.toBlob(blob => {
        if (blob) {
          resolve(new File([blob], file.name, { type: file.type, lastModified: Date.now() }));
        } else {
          resolve(file);      // fallback
        }
      }, file.type, 0.92);    // qualità 92 %
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
    finalFile = await resizeImage(file, props.maxWidth, props.maxHeight);

    if (finalFile.size > props.maxImageFileSize) {
      const msg = `Il file supera il limite di ${(props.maxImageFileSize / 1024 / 1024).toFixed(1)} MB`;
      error.value = msg;
      emit('invalid', msg);
      return;
    }
  } else if (isAudio.value) {
    if (finalFile.size > props.maxAudioFileSize) {
      const msg = `Il file supera il limite di ${(props.maxAudioFileSize / 1024 / 1024).toFixed(1)} MB`;
      error.value = msg;
      emit('invalid', msg);
      return;
    }
  } else {
    emit('invalid', 'Formato non supportato');
    return;
  }

  /* preview */
  const url = URL.createObjectURL(finalFile);
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  previewUrl.value = url;
  fileName.value = finalFile.name;
  error.value = null;

  /* aggiorna v-model */
  emit('update:modelValue', {
    media_id: null,
    media_file: finalFile,
    media_caption: {},
    media_preview: url,
    media_to_delete: false
  });
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
       class="group relative flex flex-col justify-center items-center bg-gray-50 px-4 border rounded-md outline-none focus-within:ring-2 focus-within:ring-primary w-full min-h-[120px]"
       @drop="onDrop" @dragover="onDragOver" @dragleave="onDragLeave"
       :class="{ 'ring-2 ring-primary': dragActive }">
    <!-- RIMUOVI -->
    <button v-if="previewUrl && !isReadonly" type="button"
            class="absolute z-10 flex items-center justify-center p-2 text-red-600 transition rounded-full top-1 right-1 bg-white/80 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-400"
            aria-label="Rimuovi file" @click="onRemoveClick">
      <FontAwesomeIcon :icon="byPrefixAndName.fas['trash']" />
    </button>

    <!-- INPUT -->
    <input ref="fileInput" type="file" :accept="accept" class="sr-only" @change="onFileChange" />

    <!-- PREVIEW / PLACEHOLDER -->
    <div class="flex flex-col items-center justify-center w-full py-4">
      <template v-if="previewUrl">
        <img v-if="isImage" :src="previewUrl" alt="Anteprima immagine"
             class="object-contain max-w-full rounded max-h-48" />
        <audio v-else-if="isAudio" :src="previewUrl" controls class="w-full" />
      </template>

      <template v-if="!previewUrl && !isReadonly">
        <button type="button" aria-label="Carica file" @click="openPicker"
                class="absolute inset-0 flex items-center justify-center transition bg-black/10"
                :class="isPicking
                  ? 'opacity-100 pointer-events-auto'
                  : 'opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto'">
          <FontAwesomeIcon class="text-4xl" :icon="byPrefixAndName.fas['file-arrow-up']" />
        </button>

        <div class="text-center transition-opacity duration-150 select-none text-muted-foreground"
             style="pointer-events: none;"
             :class="isPicking ? 'opacity-0' : 'opacity-100 group-hover:opacity-0'">
          <span v-if="isImage">Nessuna logo caricata.<br />Clicca o trascina qui per caricare.</span>
          <span v-else-if="isAudio">Nessun audio caricato.<br />Clicca o trascina qui per caricare.</span>
        </div>
      </template>
    </div>

    <!-- INFO -->
    <div v-if="fileName" class="mt-2 text-xs text-gray-500">{{ fileName }}</div>
    <div v-if="error" class="mt-1 text-xs text-red-600">{{ error }}</div>

    <!-- DIALOG CONFERMA -->
    <div v-if="showConfirm" class="absolute inset-0 z-20 flex items-center justify-center bg-black/40">
      <div class="flex flex-col items-center gap-2 p-4 bg-white rounded shadow-lg">
        <span class="font-semibold text-red-600">Rimuovere il file?</span>
        <div class="flex gap-2 mt-2">
          <button type="button" class="btn btn-sm btn-destructive" @click="confirmRemove" autofocus>Conferma</button>
          <button type="button" class="btn btn-sm" @click="cancelRemove">Annulla</button>
        </div>
      </div>
    </div>
  </div>
</template>