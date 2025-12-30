<script setup lang="ts">
import HMenu from '@/components/HMenu.vue';
import LanguageMenu from '@/components/LanguageMenu.vue';
import { router, usePage } from '@inertiajs/vue3';
import { h, ref } from 'vue';
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
            <h1 class="text-3xl font-bold">{{ t('menu.credits') }}</h1>
        </div>
        <div class="h-full py-15 overflow-y-scroll bg-[#eccdc3]">
            <img
                src="@assets/credits/hibou.png"
                alt="Hibou"
                class="px-10"
            />
            <div class="flex flex-row gap-4 items-center px-6 mt-10">
                <img
                    src="@assets/credits/orani.png"
                    alt="Comune di Orani"
                    class="w-24 h-24 object-contain rounded-full border-4 border-[#eccdc3]"
                />
                <h2 class="text-2xl tracking-[0.1rem] font-bold text-gray-500">
                    Comune di Orani
                </h2>
            </div>

            <div class="fixed bottom-4 left-0 flex w-full justify-center">
                <HMenu />
            </div>
        </div>
    </div>
</template>
