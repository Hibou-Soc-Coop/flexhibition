<script setup lang="ts">
import { ref } from 'vue';
import LogoLight from '@storage/assets/logo.svg';
import Dropdown from '@storage/assets/drop-down.svg';
import { router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const page = usePage();
const languages = page.props.languages as Array<{ code: string; name: string }>;
const { t , locale } = useI18n();

const props = defineProps<{ museumId: number; language: string }>();



const currentLanguage = ref(props.language);
console.log("Current Language in Museum.vue:", currentLanguage);

const loading = ref(true);

setTimeout(() => {
    loading.value = false;
}, 2000);

</script>

<template>
    <div class="relative h-screen w-screen snap-center snap-y snap-mandatory overflow-y-scroll">
        <transition leave-active-class="transition-opacity duration-3000" leave-from-class="opacity-0"
            leave-to-class="opacity-100">
            <div v-if="!loading" class="h-full flex justify-center items-center ">
                <img class="absolute inset-0 h-full" src="@storage/assets/2.jpeg" alt="">
                <img class="relative z-10 w-[187px] h-[454px]" src="@storage/assets/logo-trasparente.png" alt="">
                <Dropdown class="absolute bottom-8 place-self-center not-last:animate-[bounce_2s_infinite]" />
            </div>
        </transition>
        <div v-if="!loading" class="intro h-full bg-white py-4">
            <p class="text-lg font-medium text-black">{{ t('intro.Introduction') }}</p>
            <button  class="bg-black block text-white font-bold mx-auto py-4 px-8 mt-8" @click="router.visit(`/museum/${props.museumId}/collections`)">ENTRA</button>
        </div>
        <transition leave-active-class="transition-opacity duration-3000" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="loading" class="absolute inset-0 flex justify-center bg-black items-center ">
                <LogoLight />
            </div>
        </transition>
    </div>
</template>
