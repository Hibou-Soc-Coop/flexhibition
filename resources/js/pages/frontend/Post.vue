<script setup lang="ts">
import { ref, computed } from 'vue';
import Button from "@/components/ui/button/Button.vue";
import LanguageMenu from '@/components/LanguageMenu.vue';
import HMenu from '@/components/HMenu.vue';
import Close from '@storage/assets/chiudi.svg';
import { ScrollArea } from "@/components/ui/scroll-area";
import type { PostData } from '@/types/flexhibition';
import AudioPlayer from "@/components/ui/audio-player/AudioPlayer.vue";
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    post : PostData | PostData[];
}>();
console.log("Post prop:", props.post);
// Support both array and object, but prefer object (single post)
const post = computed(() => Array.isArray(+) ? props.post[0] : props.post);
const read = ref(false);
const listen = ref(false)

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
    <div class="h-screen w-screen grid grid-rows-[15%_70%_15%]">
        <LanguageMenu />
        <div
            class="w-[90%] h-full grid grid-rows-[60%_20%_20%] grid-cols-2 *:border *:border-black justify-center mx-auto mt-2">
                <img :src="`/storage/assets/collections/${post.id}.png`" alt=""
                    class="w-full h-full object-contain bg-[#dfdfdf] col-span-2">
                <div class="text-lg font-bold col-span-2 text-center pt-1 overflow-scroll" v-html="post.name[locale]"></div>
            <div class="grid justify-center items-center">
                <button @click="openRead" class="p-1">
                    <img src="@storage/assets/leggi.png" alt="" class="mx-auto my-2 h-14 w-14">
                    <h2 class="text-xl font-bold">{{ t("read.Read") }}</h2>
                </button>
            </div>
            <div class="grid justify-center items-center">
                <button @click="openListen" class="p-1">
                    <img src="@storage/assets/audio.png" alt="" class="mx-auto my-2 h-12 w-12">
                    <h2 class="text-xl font-bold">{{ t("listen.Listen") }}</h2>
                </button>
            </div>
        </div>
            <div class="fixed justify-center w-full bottom-4 left-0">
                <HMenu :museum-id="post.exhibition_id || 1" :language="locale" />
            </div>
    </div>

    <div v-if="read" class="w-screen h-screen bg-[#1e1e1e] fixed top-0 left-0 grid grid-rows-[10%_90%]">
        <div class="flex flex-col mt-4 items-center">
            <Button @click="closeRead" class="rounded-full w-32 h-8 bg-black text-xl text-white p-5" variant="outline">
                <Close class="inline-block mb-1" />{{ t("close.Close") }}
            </Button>
        </div>
        <ScrollArea class="px-4 relative text-white">
                <div class="text-xl mb-2 px-2 font-bold" v-html="post.name[locale]"></div>
                <div class="px-2 text-justify" v-html="post.description[locale]"></div>
        </ScrollArea>
    </div>
    <div v-if="listen" class="w-screen h-screen bg-[#1e1e1e] fixed top-0 left-0 grid grid-rows-[10%_90%]">
        <div class="flex flex-col mt-4 items-center">
            <Button @click="closeListen" class="rounded-full w-32 h-8 bg-black text-xl text-white p-5"
                variant="outline">
                <Close class="inline-block mb-1" /> {{ t("close.Close") }}
            </Button>
        </div>
            <AudioPlayer v-if="post?.audio?.[locale]" :src="`/storage/${post.audio[locale]}`" />
            <AudioPlayer v-else :src="`/storage/media/d07bca2f-ceed-471a-b82d-9d1849344355.mp3`" />
    </div>
</template>
