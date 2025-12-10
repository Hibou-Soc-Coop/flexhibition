<script setup lang="ts">
import { ref } from 'vue';
import LanguageMenu from '@/components/LanguageMenu.vue';
import HMenu from '@/components/HMenu.vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const page = usePage();
const props = defineProps<{
    exhibitions: Array<any>;
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
    <div class="h-screen grid grid-rows-[15%_85%]">
        <div class="flex flex-col justify-center items-center bg-white">
            <LanguageMenu />
            <h1 class="text-3xl font-bold">{{ t('collection.title') }}</h1>
        </div>
        <div class="p-8 bg-[#eccdc3] h-full overflow-y-scroll">
            <img v-for="exhibition in exhibitions" :key="exhibition.id"
                :src="`/storage${exhibition.images[0][locale] || exhibition.images[0]['it']}`" alt=""
                @click="router.visit(`${getCurrentUrlWithoutLanguage()}/${exhibition.id}/${locale}`)" class="bg-white mb-4 rounded-[40%]">

            <div class="fixed flex justify-center w-full bottom-4 left-0">
                <HMenu />
            </div>
        </div>
    </div>
</template>
