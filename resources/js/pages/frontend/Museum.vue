<script setup lang="ts">
import { ref } from 'vue';
import LogoLight from '@storage/assets/logo.svg';
import Dropdown from '@storage/assets/drop-down.svg';
import { router } from '@inertiajs/vue3';

const loading = ref(true);

setTimeout(() => {
    loading.value = false;
}, 2000);

</script>

<template>
    <div class="relative h-screen w-screen">
        <transition leave-active-class="transition-opacity duration-3000" leave-from-class="opacity-0"
            leave-to-class="opacity-100">
            <div v-if="!loading" class="absolute inset-0 flex justify-center items-center ">
                <img src="@storage/assets/logo-trasparente.png" alt="">
            </div>
        </transition>
        <div class="w-full h-full bg-[url('@storage/assets/2.jpeg')] bg-cover bg-center ">
            <div class="absolute bottom-8 place-self-center">
            <Dropdown class="animate-[bounce_2s_infinite]" />
            </div>
        </div>
        <div v-if="!loading" class="intro h-[65vh] bg-white py-4">
            <p class="text-lg font-medium text-black">Il Museo conserva la più importante collezione al mondo delle
                opere di Costantino Nivola tra sculture e dipinti, più di 200 opere acquisite attraverso successive
                donazioni. La scelta iniziale, compiuta dalla vedova dell’artista Ruth Guggenheim, ha privilegiato
                l’opera scultorea di Nivola e particolarmente la fase finale del suo percorso, caratterizzata da un
                ritorno alla statuaria – con la serie delle Madri e delle Vedove – e ai materiali nobili della scultura
                tradizionale</p>
            <button  class="bg-black block text-white font-bold mx-auto py-4 px-8 mt-8" @click="router.visit('/museum/collection')">ENTRA</button>
        </div>
        <transition leave-active-class="transition-opacity duration-3000" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="loading" class="absolute inset-0 flex justify-center bg-black items-center ">
                <LogoLight />
            </div>
        </transition>
    </div>
</template>
