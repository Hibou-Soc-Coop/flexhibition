<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useMediaControls } from '@vueuse/core'

import PlayIcon from '@assets/player.svg?url'
import PauseIcon from '@assets/pausa.svg?url'

const props = defineProps<{ src: string }>()
const emit = defineEmits(['close'])

const audioRef = ref<HTMLAudioElement | null>(null)
const { playing, currentTime, duration, volume } = useMediaControls(audioRef, { src: props.src })

const RADIUS = 52
const CIRCUM = 2 * Math.PI * RADIUS

const percent = computed(() => duration.value ? currentTime.value / duration.value : 0)
const dashOffset = computed(() => CIRCUM * (1 - percent.value))

const formattedTime = computed(() => {
    const rem = Math.max(0, duration.value - currentTime.value)
    const mm = String(Math.floor(rem / 60)).padStart(2, '0')
    const ss = String(Math.floor(rem % 60)).padStart(2, '0')
    return `${mm}:${ss}`
})

function handleSeek(e: MouseEvent) {
    const svg = e.currentTarget as SVGSVGElement
    const { left, top, width, height } = svg.getBoundingClientRect()
    const x = e.clientX - left - width / 2
    const y = e.clientY - top - height / 2
    // angolo rispetto all’asse X positivo
    const raw = Math.atan2(y, x)
    // sposto lo zero in alto e aggancio in [0,2π)
    let ang = raw + Math.PI / 2
    if (ang < 0) ang += 2 * Math.PI
    const seekPct = ang / (2 * Math.PI)
    currentTime.value = Math.min(Math.max(seekPct * duration.value, 0), duration.value)
}

function onEnded() {
    playing.value = false
    currentTime.value = 0
}

const isReady = ref(false)

function onCanPlay() {
    isReady.value = true
}

onMounted(() => {
    volume.value = 0.5
    currentTime.value = 0
    audioRef.value?.addEventListener('ended', onEnded)
    audioRef.value?.addEventListener('canplay', onCanPlay)
    audioRef.value?.load()
    document.body.style.overflow = 'hidden';
})
onUnmounted(() => {
    audioRef.value?.removeEventListener('ended', onEnded)
    audioRef.value?.removeEventListener('canplay', onCanPlay)
    document.body.style.overflow = '';
})
</script>

<template>
    <div class="my-30 bg-transparent p-6">
        <div class="flex flex-col items-center">
            <div class="relative w-64 h-64">
                <svg
                     class="absolute inset-0 w-full h-full cursor-pointer"
                     viewBox="0 0 120 120"
                     @click="handleSeek">
                    <!-- ruoto di -90° in modo che lo “zero” visivo sia in alto -->
                    <g transform="rotate(-90 60 60)">
                        <circle
                                cx="60" cy="60" :r="RADIUS"
                                stroke="white" stroke-opacity="0.3"
                                stroke-width="5" fill="none" />
                        <circle
                                cx="60" cy="60" :r="RADIUS"
                                stroke="#73E6BD" stroke-width="7" fill="none"
                                stroke-linecap="round"
                                :stroke-dasharray="CIRCUM"
                                :stroke-dashoffset="dashOffset"
                                style="transition: stroke-dashoffset 0.25s linear;" />
                    </g>
                </svg>

                <button
                        class="top-1/2 left-1/2 z-10 absolute flex justify-center items-center rounded-full focus:outline-none w-16 h-16"
                        style="transform: translate(-50%, -50%);"
                        @click.stop="isReady && (playing = !playing)"
                        :disabled="!isReady"
                        aria-label="Play/Pause">
                    <img
                         v-if="!playing"
                         :src="PlayIcon"
                         class="ml-3 w-16 h-16"
                         alt="Play" />
                    <img
                         v-else
                         :src="PauseIcon"
                         class="w-16 h-16"
                         alt="Pause" />
                </button>
            </div>

            <div class="mt-3 font-bold text-white text-2xl">
                {{ formattedTime }}
            </div>
            <audio ref="audioRef" :src="props.src" class="hidden" preload="auto" />
        </div>
    </div>
</template>
