<script setup lang="ts">
import { ref } from 'vue';
import LogoLight from '@storage/assets/logo.svg';
import LogoDark from '@storage/assets/logo-trasparente.svg';

const loadingComplete = ref(true);

setTimeout(() => {
    loadingComplete.value = false;
}, 3000);

</script>

<template>
    <div class="relative h-screen w-screen">
        <div class="absolute inset-0 bg-white flex items-center justify-center">
            <LogoDark />
        </div>
        <transition leave-active-class="transition-opacity duration-3000" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="loadingComplete" class="absolute inset-0 flex justify-center items-center bg-black">
                <LogoLight class="bg-white"/>
            </div>
        </transition>
    </div>
</template>
