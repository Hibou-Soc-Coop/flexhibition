<script setup lang="ts">
import { ref } from 'vue';
import Button from "@/components/ui/button/Button.vue";
import LanguageMenu from '@/components/LanguageMenu.vue';
import HMenu from '@/components/HMenu.vue';
import Close from '@storage/assets/chiudi.svg';
import { ScrollArea } from "@/components/ui/scroll-area";
import type { PostData } from '@/types/flexhibition';
import AudioPlayer from "@/components/ui/audio-player/AudioPlayer.vue";
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    post : PostData[];
}>();
console.log("Post prop in Post.vue:", props.post.audio);
const read = ref(false);
const listen = ref(false)

const { t, locale } = useI18n();


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
            class="w-[90%] h-full grid grid-rows-[60%_15%_25%] grid-cols-2 *:border *:border-black justify-center mx-auto mt-2">
            <img src="@storage/assets/collections/1.png" alt=""
                class="w-full h-full object-cover cover bg-[#dfdfdf] col-span-2">
            <div class="text-xl font-bold col-span-2 text-center pt-2 overflow-scroll" v-html="post.description[locale]"></div>
            <div class="grid justify-center items-center">
                <button @click="openRead" class="p-2">
                    <img src="@storage/assets/leggi.png" alt="" class="mx-auto my-2 h-14 w-14">
                    <h2 class="text-xl font-bold">LEGGI</h2>
                </button>
            </div>
            <div class="grid justify-center items-center">
                <button @click="openListen" class="p-2">
                    <img src="@storage/assets/audio.png" alt="" class="mx-auto my-2 h-12 w-12">
                    <h2 class="text-xl font-bold">ASCOLTA</h2>
                </button>
            </div>
        </div>
        <div class="fixed justify-center w-full bottom-4 left-0">
            <HMenu />
        </div>
    </div>

    <div v-if="read" class="w-screen h-screen bg-[#1e1e1e] fixed top-0 left-0 grid grid-rows-[10%_90%]">
        <div class="flex flex-col mt-4 items-center">
            <Button @click="closeRead" class="rounded-full w-32 h-8 bg-black text-xl text-white p-5" variant="outline">
                <Close class="inline-block mb-1" />CHIUDI
            </Button>
        </div>
        <ScrollArea class="px-4 relative text-white">
            <div class="text-xl font-bold" v-html="post.name[locale]"></div>
            <div v-html="post.content[locale]"></div>
        </ScrollArea>
    </div>
    <div v-if="listen" class="w-screen h-screen bg-[#1e1e1e] fixed top-0 left-0 grid grid-rows-[10%_90%]">
        <div class="flex flex-col mt-4 items-center">
            <Button @click="closeListen" class="rounded-full w-32 h-8 bg-black text-xl text-white p-5"
                variant="outline">
                <Close class="inline-block mb-1" />CHIUDI
            </Button>
        </div>
        <AudioPlayer v-if="props.post?.audio?.[locale]" :src="`/storage/${props.post.audio[locale]}`" />
        <AudioPlayer v-else :src="`/storage/media/d07bca2f-ceed-471a-b82d-9d1849344355.mp3`" />
    </div>
</template>
