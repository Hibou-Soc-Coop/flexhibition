<script setup lang="ts">
import { ref } from 'vue';
import Dropdown from '@storage/assets/drop-down.svg';
import Button from "@/components/ui/button/Button.vue";
import type { Language } from '@/types/flexhibition';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const page = usePage();
const languages = page.props.languages as Language[];

const open = ref(false)
const rotateLanguageIcon = ref(false)
const { t , locale } = useI18n();

function rotate() {
    open.value = !open.value;
    rotateLanguageIcon.value = !rotateLanguageIcon.value;
}
function changeLanguage(code: string) {
    const splitUrl = page.url.split('/');
    console.log('Split URL:', splitUrl);
    if (languages.some(lang => lang.code === splitUrl[splitUrl.length - 1])) {
        splitUrl.pop();
    }
    const newUrl = `${splitUrl.join('/')}/${code}`;
    locale.value = code;
    history.pushState(null, page.props.name, newUrl);
}
</script>
<template>
    <div class="flex flex-col  justify-center items-center bg-white p-4 gap-4">
        <Button class="relative rounded-full w-20 h-8 text-xl uppercase" @click="rotate" variant="outline"> {{ locale }}
            <span>
                <Dropdown
                    :class="[rotateLanguageIcon ? 'rotate-180' : '', 'inline-block mb-1 transition-all duration-500']" />
            </span>
            <div
                :class="['absolute bg-white shadow-lg p-4 rounded mt-50 overflow-hidden transition-all duration-500 origin-top min-w-20', open ? 'scale-y-100 opacity-100' : 'scale-y-0 opacity-0']">
                <a v-for="language in languages" :key="language.code" @click.prevent="changeLanguage(language.code)" class="block font-bold mt-1 uppercase">{{ language.code }}</a>
            </div>
        </Button>
    </div>
</template>
