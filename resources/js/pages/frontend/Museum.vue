<script setup lang="ts">
import Dropdown from '@assets/drop-down.svg';
import LogoLight from '@assets/logo.svg';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const page = usePage();
const languages = page.props.languages as Array<{ code: string; name: string }>;
const { t, locale } = useI18n();

const props = defineProps<{ museumId: number; language: string }>();

const currentLanguage = ref(props.language);

const loading = ref(true);
const containerRef = ref<HTMLElement | null>(null);

const scrollToNextScreen = () => {
    containerRef.value?.scrollBy({ top: window.innerHeight, behavior: 'smooth' });
};

setTimeout(() => {
    loading.value = false;
}, 2000);
</script>

<template>
    <div ref="containerRef" class="relative h-screen w-screen snap-y snap-mandatory overflow-y-scroll">
        <transition leave-active-class="transition-opacity duration-3000" leave-from-class="opacity-0" leave-to-class="opacity-100">
            <div v-if="!loading" class="flex h-full w-full snap-start items-center justify-center">
                <img class="absolute inset-0 h-full object-cover" src="@assets/1.jpg" alt="" />
                <img class="relative z-10 h-[454px] w-[187px]" src="@assets/logo-trasparente.png" alt="" />
                <Dropdown
                    class="absolute bottom-8 cursor-pointer place-self-center animate-[bounce_2s_infinite]"
                    @click="scrollToNextScreen"
                />
            </div>
        </transition>
        <div v-if="!loading" class="intro h-full w-full snap-start bg-white p-4">
            <p class="text-lg font-medium text-black">{{ t('intro.Introduction') }}</p>
            <button
                class="mx-auto mt-8 block bg-black px-8 py-4 font-bold text-white"
                @click="router.visit(`/museum/${props.museumId}/collections/1`)"
            >
                {{ t('enter.Enter') }}
            </button>
        </div>
        <transition leave-active-class="transition-opacity duration-3000" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-black">
                <LogoLight />
            </div>
        </transition>
    </div>
</template>
