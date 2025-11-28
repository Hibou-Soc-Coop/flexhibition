<script setup lang="ts">
import { ref } from 'vue';
import Dropdown from '@storage/assets/drop-down.svg';
import Button from "@/components/ui/button/Button.vue";

const open = ref(false)
const rotateLanguageIcon = ref(false)

function rotate() {
    open.value = !open.value;
    rotateLanguageIcon.value = !rotateLanguageIcon.value;
}
</script>
<template>
    <div class="flex flex-col  justify-center items-center bg-white p-4 gap-4">
        <Button class="relative rounded-full w-20 h-8 text-xl" @click="rotate" variant="outline">ITA
            <span>
                <Dropdown
                    :class="[rotateLanguageIcon ? 'rotate-180' : '', 'inline-block mb-1 transition-all duration-500']" />
            </span>
            <div class="absolute bg-white shadow-lg p-4 rounded mt-48 overflow-hidden transition-all duration-500 origin-top min-w-20"
                :class="open ? 'scale-y-100 opacity-100' : 'scale-y-0 opacity-0'">
                <a href="#" class="block font-bold mt-1">ENG</a>
                <a href="#" class="block font-bold mt-1">FR</a>
                <a href="#" class="block font-bold mt-1">SPA</a>
                <a href="#" class="block font-bold mt-1">CIN</a>
            </div>
        </Button>
    </div>
</template>
