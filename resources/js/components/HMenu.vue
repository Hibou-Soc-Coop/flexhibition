<script setup lang="ts">
import { ref } from 'vue';
import Menu from '@storage/assets/burger-menu.svg';
import Close from '@storage/assets/chiudi.svg';
import { router } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const page = usePage();
const languages = page.props.languages as Array<{ code: string; name: string }>;
const { t , locale } = useI18n();

const props = defineProps<{ museumId: number; language: string }>();



const currentLanguage = ref(props.language);
const isOpen = ref(false);

function toggleMenu() {
    isOpen.value = !isOpen.value;
}
function closeMenu() {
    isOpen.value = false;
}
</script>

<template>
    <div class="fixed z-40 flex justify-center w-full bottom-4">
        <button
            @click="toggleMenu"
            :class="[
                'transition-all duration-300 bg-black/75 flex items-center justify-center focus:outline-none',
                isOpen
                    ? 'w-[75vw] h-[90vh] rounded-[2vw] shadow-xl'
                    : 'w-32 h-16 rounded-[10%]  shadow-lg'
            ]"
        >
            <template v-if="!isOpen">
                <span class="text-2xl font-bold tracking-wider text-white select-none">MENU</span>
                <Menu class="inline-block ml-2 mb-1" />
            </template>
            <template v-else>
                <div class="flex flex-col items-center justify-center w-full h-full overflow-hidden">
                    <ul class="flex flex-col mt-[30vh] mb-8 gap-8">
                        <li><a  class="font-bold text-white text-3xl tracking-widest"  @click="router.visit(`/museum/${props.museumId}/${props.language}`)" >HOME</a></li>
                        <li><a class="font-bold text-white text-3xl tracking-widest" @click="router.visit(`/museum/${props.museumId}/collections/${props.collectionId}/`)">COLLEZIONE</a></li>
                        <li><a class="font-bold text-white text-3xl tracking-widest">CONTATTI</a></li>
                    </ul>
                    <button
                        @click.stop="closeMenu"
                        class="mt-auto mb-6 leading-none text-white transition-transform text-8xl"
                        aria-label="Chiudi menu"
                    >
                        <span class="text-2xl font-bold tracking-wider text-white select-none">
                            MENU
                            <Close class="inline-block ml-1 mb-2" />
                        </span>
                    </button>
                </div>
            </template>
        </button>
    </div>
</template>
