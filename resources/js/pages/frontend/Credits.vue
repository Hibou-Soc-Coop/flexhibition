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
    <div class="grid h-dvh grid-rows-[15%_85%]">
        <div class="flex flex-col items-center justify-center">
            <LanguageMenu />
            <h1 class="text-3xl font-bold">{{ t('menu.credits') }}</h1>
        </div>
        <div class="h-full py-15 overflow-y-scroll bg-[#eccdc3]">
            <p class="text-center text-3xl" lang="it">
                {{ t('credits.direction') }}
            </p>
            <div class="flex flex-row justify-center items-center py-6">
                <img
                    src="@assets/credits/orani.png"
                    alt="Comune di Orani"
                    class="w-18 h-18 object-contain"
                />
                <h2 class="text-2xl font-bold text-gray-500">
                    Comune di Orani
                </h2>
            </div>
            <p class="text-center text-3xl mt-20" lang="it">
                {{ t('credits.production') }}
            </p>
            <img
                src="@assets/credits/hibou.png"
                alt="Hibou"
                class="w-58 object-contain mx-auto py-6"
            />

            <HMenu />
        </div>
    </div>
</template>
