<script setup lang="ts">
import HMenu from '@/components/HMenu.vue';
import LanguageMenu from '@/components/LanguageMenu.vue';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
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
    console.log('Current URL:', currentUrl.value);
    return currentUrl.value;
}
</script>
<template>
    <div class="grid h-screen grid-rows-[15%_85%]">
        <div class="flex flex-col items-center justify-center bg-white">
            <LanguageMenu />
            <h1 class="text-3xl font-bold">{{ t('collection.title') }}</h1>
        </div>
        <div class="h-full overflow-y-scroll bg-[#eccdc3] p-8">
            <img
                v-for="exhibition in exhibitions"
                :key="exhibition.id"
                :src="`/assets${exhibition.images[0][locale]}` || `/assets/${exhibition.images[0]['it']}`"
                alt=""
                @click="router.visit(`${getCurrentUrlWithoutLanguage()}/${exhibition.id}/${locale}`)"
                class="mb-4 rounded-[40%] bg-white"
            />

            <div class="fixed bottom-4 left-0 flex w-full justify-center">
                <HMenu />
            </div>
        </div>
    </div>
</template>
