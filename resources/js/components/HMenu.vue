<script setup lang="ts">
import Menu from '@assets/burger-menu.svg';
import Close from '@assets/chiudi.svg';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t, locale } = useI18n();

const isOpen = ref(false);

function toggleMenu() {
    isOpen.value = !isOpen.value;
}
function closeMenu() {
    isOpen.value = false;
}
</script>

<template>
    <div class="fixed z-20 bottom-4 left-0 flex w-full justify-center">
        <button
            @click="toggleMenu"
            :class="[
                'flex items-center justify-center bg-black/75 transition-all duration-300 focus:outline-none',
                isOpen ? 'h-[80vh] w-[75vw] rounded-[2vw] shadow-xl' : 'h-16 w-32 rounded-[10%] shadow-lg',
            ]"
        >
            <template v-if="!isOpen">
                <span class="text-2xl font-bold tracking-wider text-white select-none">{{ t('menu.menu') }}</span>
                <Menu class="mb-1 ml-2 inline-block" />
            </template>
            <template v-else>
                <div class="flex h-full w-full flex-col items-center justify-center overflow-hidden">
                    <ul class="mt-[30vh] mb-8 flex flex-col gap-8">
                        <li>
                            <a
                                class="text-3xl font-bold tracking-widest text-white"
                                @click="router.visit(`/museum/1/${locale}`, { data: { skipAnimation: true } })"
                                >{{ t('menu.home') }}</a
                            >
                        </li>
                        <li>
                            <a class="text-3xl font-bold tracking-widest text-white" @click="router.visit(`/museum/1/collections/1/${locale}`)">{{
                                t('menu.collection')
                            }}</a>
                        </li>
                        <li>
                            <a class="text-3xl font-bold tracking-widest text-white" @click="router.visit(`/credits/${locale}`)">{{
                                t('menu.credits')
                            }}</a>
                        </li>
                    </ul>
                    <button
                        @click.stop="closeMenu"
                        class="mt-auto mb-6 text-8xl leading-none text-white transition-transform"
                        aria-label="Chiudi menu"
                    >
                        <span class="text-2xl font-bold tracking-wider text-white select-none">
                            MENU
                            <Close class="mb-2 ml-1 inline-block" />
                        </span>
                    </button>
                </div>
            </template>
        </button>
    </div>
</template>
