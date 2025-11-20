<script setup lang="ts">
import { ref } from 'vue';
import Button from "@/components/ui/button/Button.vue";
import Dropdown from '@storage/assets/drop-down.svg';
import HMenu from '@/components/HMenu.vue';
import Leggi from '@storage/assets/leggi.svg';
import Ascolta from '@storage/assets/audio.svg';
import Close from '@storage/assets/chiudi.svg';
import { ScrollArea } from "@/components/ui/scroll-area";
import AudioPlayer from "@/components/ui/audio-player/AudioPlayer.vue";

const read = ref(false);
const listen = ref(false)

function openRead() {
    listen.value = false;
    read.value = true;
}
function openListen() {
    read.value = false;
    listen.value = true;
}
function closeRead() {
    read.value = false;
}
function closeListen() {
    listen.value = false;
}

</script>
<template>
    <div class="h-screen w-screen grid grid-rows-[15%_70%_15%]">
        <div class="flex flex-col justify-center items-center bg-white border-b p-4 gap-4">
            <Button class="rounded-full w-20 h-8 text-xl " variant="outline">ITA
                <span>
                    <Dropdown class="inline-block mb-1" />
                </span>
            </Button>
        </div>
        <div
            class="w-[90%] h-full grid grid-rows-[60%_15%_25%] grid-cols-2 *:border *:border-black justify-center mx-auto mt-2">
            <img src="@storage/assets/collections/1.png" alt=""
                class="w-full h-full object-cover cover bg-[#dfdfdf] col-span-2">
            <p class="text-2xl font-bold col-span-2 p-2 ">POST</p>
            <div class="grid justify-center items-center">
                <button @click="openRead">
                    <Leggi class="" />
                    <h2 class="text-xl font-bold">LEGGI</h2>
                </button>
            </div>
            <div class="grid justify-center items-center">
                <button @click="openListen">
                    <Ascolta class="" />
                    <h2 class="text-xl font-bold">ASCOLTA</h2>
                </button>
            </div>
        </div>
        <div class="fixed justify-center w-full bottom-4 left-0">
            <HMenu />
        </div>
    </div>
    <div v-if="read" class="w-screen h-screen bg-[#1e1e1e] fixed top-0 left-0 grid grid-rows-[10%_90%]">
        <div class="flex flex-col mt-4 items-center">
            <Button @click="closeRead" class="rounded-full w-32 h-8 bg-black text-xl text-white p-5" variant="outline">
                <Close class="inline-block mb-1" />CHIUDI
            </Button>
        </div>
        <ScrollArea class="px-4 relative text-white">
            <h2 class="text-xl font-bold">Contenuto</h2>
            <p>Emilio Vedova nasce a Venezia il 9 agosto 1919. Accostatosi alla pittura da autodidatta, frequenta per un
                breve periodo la scuola serale di decorazione ai Carmini. Nel 1942 aderisce al gruppo Corrente, cui
                appartengono anche Renato Birolli, Umberto Vittorini, Renato Guttuso ed Ennio Morlotti. Dal 1943
                partecipa attivamente alla Resistenza. Nel 1946, a Milano, collabora con Morlotti al manifesto Oltre
                Guernica e a Venezia è uno dei fondatori del Fronte Nuovo delle Arti. In questo periodo inizia la serie
                delle Geometrie nere, opere in bianco e nero che risentono dell’impostazione spaziale cubista.
                La sua prima personale negli Stati Uniti si tiene alla Catherine Viviano Gallery di New York nel 1951,
                anno in cui riceve anche il premio per i giovani pittori alla prima Biennale di San Paolo. Nel 1952
                prende parte al Gruppo degli Otto. Nel 1955 partecipa alla prima Documenta di Kassel, e l’anno dopo gli
                viene assegnato il Guggenheim International Award.</p>
        </ScrollArea>
    </div>
    <div v-if="listen" class="w-screen h-screen bg-[#1e1e1e] fixed top-0 left-0 grid grid-rows-[10%_90%]">
        <div class="flex flex-col mt-4 items-center">
            <Button @click="closeListen" class="rounded-full w-32 h-8 bg-black text-xl text-white p-5"
                variant="outline">
                <Close class="inline-block mb-1" />CHIUDI
            </Button>
        </div>
        <AudioPlayer src="audio1.mp3" />
    </div>
</template>
