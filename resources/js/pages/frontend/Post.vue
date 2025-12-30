<script setup lang="ts">
import HMenu from '@/components/HMenu.vue';
import LanguageMenu from '@/components/LanguageMenu.vue';
import AudioPlayer from '@/components/ui/audio-player/AudioPlayer.vue';
import Button from '@/components/ui/button/Button.vue';
import { ScrollArea } from '@/components/ui/scroll-area';
import type { PostData } from '@/types/flexhibition';
import Close from '@assets/chiudi.svg';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    post: PostData | PostData[];
}>();

const post = computed(() => (Array.isArray(props.post) ? props.post[0] : props.post));
const read = ref(false);
const listen = ref(false);

const { locale, t } = useI18n();

function openRead() {
    listen.value = false;
    read.value = true;
}
function openListen() {
    read.value = false;
    listen.value = true;
}
function closeRead() {
    read.value = false;
}
function closeListen() {
    listen.value = false;
}
</script>
<template>
    <div class="grid h-dvh w-dvw grid-rows-[15%_70%_15%]">
        <LanguageMenu />
        <div class="mx-auto mt-2 grid h-full w-[90%] grid-cols-2 grid-rows-[60%_20%_20%] justify-center *:border *:border-black">
            <!-- FIXME: Le immagini dei post non dovrebbero essere hardcoded ma presi da DB -->
            <img :src="post.images[0][locale] || post.images[0]['it']" alt="" class="col-span-2 h-full w-full bg-[#dfdfdf] object-contain" />
            <div class="col-span-2 overflow-scroll pt-1 text-center text-lg font-bold" v-html="post.name[locale] || post.name['it']"></div>
            <div class="grid items-center justify-center">
                <button @click="openRead" class="p-1">
                    <img src="@assets/leggi.png" alt="" class="mx-auto my-2 h-14 w-14" />
                    <h2 class="text-xl font-bold">{{ t('read.Read') }}</h2>
                </button>
            </div>
            <div class="grid items-center justify-center">
                <button @click="openListen" class="p-1">
                    <img src="@assets/audio.png" alt="" class="mx-auto my-2 h-12 w-12" />
                    <h2 class="text-xl font-bold">{{ t('listen.Listen') }}</h2>
                </button>
            </div>
        </div>
        <div class="fixed bottom-4 left-0 w-full justify-center">
            <HMenu :museum-id="post.exhibition_id || 1" :language="locale" />
        </div>
    </div>

    <div v-if="read" class="fixed top-0 left-0 grid h-screen w-screen grid-rows-[10%_90%] bg-[#1e1e1e]">
        <div class="mt-4 flex flex-col items-center">
            <Button @click="closeRead" class="h-8 w-32 rounded-full bg-black p-5 text-xl text-white" variant="outline">
                <Close class="mb-1 inline-block" />{{ t('close.Close') }}
            </Button>
        </div>
        <ScrollArea class="relative px-4 text-white">
            <div class="mb-2 px-2 text-xl font-bold" v-html="post.name[locale] || post.name['it']"></div>
            <div class="px-2 text-justify" v-html="post.description?.[locale] || post.description?.['it']"></div>
        </ScrollArea>
    </div>
    <div v-if="listen" class="fixed top-0 left-0 grid h-screen w-screen grid-rows-[10%_90%] bg-[#1e1e1e]">
        <div class="mt-4 flex flex-col items-center">
            <Button @click="closeListen" class="h-8 w-32 rounded-full bg-black p-5 text-xl text-white" variant="outline">
                <Close class="mb-1 inline-block" /> {{ t('close.Close') }}
            </Button>
        </div>
        <AudioPlayer v-if="post.audio?.[locale]" :src="post.audio[locale] || post.audio['it']" />
        <AudioPlayer v-else :src="`@assets/audio.mp3`" />
    </div>
</template>
