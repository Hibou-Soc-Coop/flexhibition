<script setup lang="ts">
import HMenu from '@/components/HMenu.vue';
import LanguageMenu from '@/components/LanguageMenu.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const page = usePage();
const props = defineProps<{
    posts: Array<any>;
}>();
const { t, locale } = useI18n();
const currentUrl = ref('');

function getCurrentUrlWithoutLanguage() {
    const splitUrl = page.url.split('/');
    if (splitUrl[splitUrl.length - 1] === locale.value) {
        splitUrl.pop();
    }
    currentUrl.value = splitUrl.join('/');
    return currentUrl.value;
}
</script>
<template>
    <div class="grid h-dvh grid-rows-[15%_85%]">
        <div class="flex flex-col items-center justify-center bg-white">
            <LanguageMenu />
            <h1 class="text-3xl font-bold">{{ t('collection.title') }}</h1>
        </div>
        <div class="h-full overflow-y-scroll bg-[#eccdc3] p-8">
            <div v-for="post in posts" :key="post.id" class="mb-4 rounded-[40%] bg-white min-w-full min-h-50 cursor-pointer">
            <img class="object-cover rounded-[40%] w-full h-64"
                :key="post.id"
                :src="post.images[0][locale] || post.images[0]['it']"
                alt=""
                @click="router.visit(`${getCurrentUrlWithoutLanguage()}/posts/${post.id}/${locale}`)"

            />
            </div>

            <div class="fixed bottom-4 left-0 flex w-full justify-center">
                <HMenu />
            </div>
        </div>
    </div>
</template>
