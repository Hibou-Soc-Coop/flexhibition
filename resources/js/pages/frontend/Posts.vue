<script setup lang="ts">
import HMenu from '@/components/HMenu.vue';
import LanguageMenu from '@/components/LanguageMenu.vue';
import { Post } from '@/types/flexhibition';
import ScreenOrientation from '@/components/ui/ScreenOrientation.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const page = usePage();
const props = defineProps<{
    posts: Post[];
}>();
const { t, locale } = useI18n();
const isPortrait = ref(window.innerHeight > window.innerWidth);

window.addEventListener('resize', () => {
    isPortrait.value = window.innerHeight > window.innerWidth;
});
</script>
<template>
    <ScreenOrientation v-if="!isPortrait" />
    <div class="grid h-dvh grid-rows-[15%_85%]">
        <div class="flex flex-col items-center justify-center bg-white">
            <LanguageMenu />
            <h1 class="text-3xl font-bold">{{ t('collection.title') }}</h1>
        </div>
        <div class="h-full overflow-y-scroll bg-[#eccdc3] p-8 pb-24">
            <div v-for="post in posts" :key="post.id" class="mb-4 min-h-50 min-w-full cursor-pointer rounded-[40%] bg-black">
                <img
                    class="h-75 w-full rounded-[40%] object-cover object-top"
                    :key="post.id"
                    :src="post.images?.[0][locale] || post.images?.[0]['it']"
                    alt=""
                    @click="router.visit(`/museum/${post.museum_id}/collections/${post.exhibition_id}/posts/${post.id}/${locale}`)"
                />
            </div>

            <HMenu />
        </div>
    </div>
</template>
