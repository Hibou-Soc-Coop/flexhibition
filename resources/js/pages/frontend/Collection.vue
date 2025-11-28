<script setup lang="ts">
import LanguageMenu from '@/components/LanguageMenu.vue';
import HMenu from '@/components/HMenu.vue';
import { router } from '@inertiajs/vue3';


</script>
<template>
    <div class="h-screen grid grid-rows-[15%_85%]">
        <div class="flex flex-col justify-center items-center bg-white">
             <LanguageMenu />
            <h1 class="text-3xl font-bold">COLLEZIONE</h1>
        </div>
        <div class="p-8 bg-[#eccdc3] h-full overflow-y-scroll">
            <img src="@storage/assets/collections/1.png" alt="" @click="router.visit('/museum/collection/1')" class="bg-white mb-4 rounded-[40%]">
            <img src="@storage/assets/collections/2.png" alt="" @click="router.visit('/museum/collection/2')" class="bg-white mb-4 rounded-[40%]">
            <img src="@storage/assets/collections/3.png" alt="" @click="router.visit('/museum/collection/3')" class="bg-white mb-4 rounded-[40%]">
            <div class="fixed flex justify-center w-full bottom-4 left-0">
                <HMenu/>
            </div>
        </div>
    </div>
</template>
