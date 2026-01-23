<script setup lang="ts">
import HMenu from '@/components/HMenu.vue';
import LanguageMenu from '@/components/LanguageMenu.vue';
import AudioPlayer from '@/components/ui/audio-player/AudioPlayer.vue';
import Button from '@/components/ui/button/Button.vue';
import { ScrollArea } from '@/components/ui/scroll-area';
import type { Post } from '@/types/flexhibition';
import Close from '@assets/chiudi.svg';
import Left from '@assets/left.svg';
import ScreenOrientation from '@/components/ui/ScreenOrientation.vue';
import Right from '@assets/right.svg';
import { Carousel, CarouselContent, CarouselItem, CarouselApi } from '@/components/ui/carousel';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<{
    post: Post;
    allPosts: Post[];
}>();

// -------------------------------
// Stato UI
// -------------------------------
const selectedIndex = ref(0);

const idx = props.allPosts.findIndex(p => p.id === props.post.id);
if (idx !== -1) selectedIndex.value = idx;
const isScrolling = ref(false);


const api = ref<CarouselApi | null>(null);

function setApi(val: CarouselApi) {
    api.value = val;
}


const post = computed(() => (Array.isArray(props.post) ? props.post[0] : props.post));
const read = ref(false);
const listen = ref(false);
const nameLength = computed(() => {
    const currentPost = props.allPosts[selectedIndex.value];
    return (currentPost?.name['it'] || '').length;
});
// const nameLength = computed(() => {
//     return (post.value.name['it'] || '').length;
// });

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
watch(api, (newApi) => {
    if (!newApi) return;
    newApi.on('scroll', () => {
        isScrolling.value = true;
    });
    newApi.on('settle', () => {
        selectedIndex.value = newApi.selectedScrollSnap();
        isScrolling.value = false;

        const currentPost = props.allPosts[selectedIndex.value];
        if (currentPost) {
            const url = `/museum/${currentPost.museum_id}/collections/${currentPost.exhibition_id}/posts/${currentPost.id}/${locale.value}`;
            window.history.replaceState(window.history.state, '', url);
        }
    });
});

onMounted(() => {
    if (api.value) {
        api.value.scrollTo(selectedIndex.value, false);
    }
});
const isPortrait = ref(window.innerHeight > window.innerWidth);

window.addEventListener('resize', () => {
    isPortrait.value = window.innerHeight > window.innerWidth;
});
</script>
<template>
    <ScreenOrientation v-if="!isPortrait" />
    <div class="grid h-dvh w-dvw grid-rows-[15%_70%_15%]">
        <LanguageMenu />
        <div class="relative h-full w-full ">
            <div class="absolute left-0 top-1/3 -translate-y-1/2 z-10 flex items-center" @click="api?.scrollPrev()"
                style="cursor: pointer;">
                <div class="w-8 h-16 bg-black/20 rounded-r-full flex items-center justify-center">
                    <Left class="-ml-2" />
                </div>
            </div>
            <div class="absolute right-0 top-1/3 -translate-y-1/2 z-10 flex items-center" @click="api?.scrollNext()"
                style="cursor: pointer;">
                <div class="w-8 h-16 bg-black/20 rounded-l-full flex items-center justify-center">
                    <Right class="-mr-2" />
                </div>
            </div>
            <Carousel class="mt-2 h-full fix" :opts="{ loop: true }" @init-api="setApi">
                <CarouselContent class="h-full m-0">
                    <CarouselItem v-for="(post, index) in allPosts" :key="index"
                        class="mx-auto min-h-full h-full grid grid-cols-2 grid-rows-[60%_20%_20%] snap-x snap-mandatory overflow-y-hidden justify-center *:border *:border-black p-4 relative">
                        <div class="col-span-2 relative h-full w-full">


                            <img v-if="post.images && post.images[0]"
                                :src="post.images[0][locale] || post.images[0]['it']" alt=""
                                @click="api?.scrollTo(index)" class="h-full w-full bg-[#f4f4f4] object-contain" />
                        </div>
                        <div v-if="post.name[locale] && (post.name[locale] || '').length <= 50"
                            class="col-span-2 flex items-center justify-center px-2 h-full text-lg font-bold"
                            v-html="post.name[locale] || post.name['it']"></div>
                        <div v-else class="col-span-2 overflow-scroll pt-5 px-2 text-center text-lg font-bold"
                            v-html="post.name[locale] || post.name['it']"></div>
                        <div class="grid items-center justify-center">
                            <button @click="openRead" class="p-1">
                                <img src="@assets/leggi.png" alt="" class="mx-auto my-1 h-11 w-12" />
                                <h2 class="text-xl font-bold">{{ t('read.Read') }}</h2>
                            </button>
                        </div>
                        <div class="grid items-center justify-center">
                            <button @click="openListen" class="p-1">
                                <img src="@assets/audio.png" alt="" class="mx-auto my-1 h-11 w-12" />
                                <h2 class="text-xl font-bold">{{ t('listen.Listen') }}</h2>
                            </button>
                        </div>
                    </CarouselItem>
                </CarouselContent>
            </Carousel>
        </div>
            <HMenu :museum-id="post.exhibition_id || 1" :language="locale" />
    </div>

    <div v-if="read && !isScrolling"
        class="fixed z-20 top-0 left-0 grid h-dvh w-screen grid-rows-[10%_90%] bg-[#1e1e1e] pb-6">
        <div class="mt-4 flex flex-col items-center">
            <Button @click="closeRead" class="h-8 w-32 rounded-full bg-black p-5 text-xl text-white" variant="outline">
                <Close class="mb-1 inline-block" />{{ t('close.Close') }}
            </Button>
        </div>
        <ScrollArea class="relative px-4 text-white">
            <div class="mb-4 px-2 text-xl font-bold"
                v-html="allPosts[selectedIndex]?.name[locale] || allPosts[selectedIndex]?.name['it']"></div>
            <div class="px-2 text-lg hyphens-auto tracking-[0.02rem]" lang="it"
                v-html="allPosts[selectedIndex]?.description?.[locale] || allPosts[selectedIndex]?.description?.['it']">
            </div>
        </ScrollArea>
    </div>
    <div v-if="listen && !isScrolling"
        class="fixed  z-20 top-0 left-0 grid h-dvh w-screen grid-rows-[10%_90%] bg-[#1e1e1e]">
        <div class="mt-4 flex flex-col items-center">
            <Button @click="closeListen" class="h-8 w-32 rounded-full bg-black p-5 text-xl text-white"
                variant="outline">
                <Close class="mb-1 inline-block" /> {{ t('close.Close') }}
            </Button>
        </div>
        <AudioPlayer v-if="allPosts[selectedIndex].audio"
            :src="allPosts[selectedIndex]?.audio?.[locale] ?? allPosts[selectedIndex]?.audio?.['it'] ?? ''" />
        <AudioPlayer v-else :src="`@assets/audio.mp3`" />
    </div>
</template>
<style>
.fix>div {
    height: 100%;
}
</style>
