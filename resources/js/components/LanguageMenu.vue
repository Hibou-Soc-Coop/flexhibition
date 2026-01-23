<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import type { Language } from '@/types/flexhibition';
import Dropdown from '@assets/drop-down.svg';
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const page = usePage();
const languages = page.props.languages as Language[];

const open = ref(false);
const rotateLanguageIcon = ref(false);
const { t, locale } = useI18n();

function rotate() {
    open.value = !open.value;
    rotateLanguageIcon.value = !rotateLanguageIcon.value;
}
function changeLanguage(code: string) {
    const splitUrl = page.url.split('/');
    console.log('Split URL:', splitUrl);
    if (languages.some((lang) => lang.code === splitUrl[splitUrl.length - 1])) {
        splitUrl.pop();
    }
    const newUrl = `${splitUrl.join('/')}/${code}`;
    locale.value = code;
    history.replaceState(null, page.props.name, newUrl);
}
</script>
<template>
    <div class="flex z-10 flex-col items-center justify-center gap-4 bg-white p-4">
        <Button class="relative h-8 w-20 rounded-full text-xl uppercase" @click="rotate" variant="outline">
            {{ locale }}
            <span>
                <Dropdown :class="[rotateLanguageIcon ? 'rotate-180' : '', 'mb-1 inline-block transition-all duration-500']" />
            </span>
            <div
                :class="[
                    'absolute mt-40 min-w-20 origin-top overflow-hidden rounded bg-white p-4 shadow-lg transition-all duration-500',
                    open ? 'scale-y-100 opacity-100' : 'scale-y-0 opacity-0',
                ]"
            >
                <a
                    v-for="language in languages"
                    :key="language.code"
                    @click.prevent="changeLanguage(language.code)"
                    class="mt-1 block font-bold uppercase"
                    >{{ language.code }}</a
                >
            </div>
        </Button>
    </div>
</template>
