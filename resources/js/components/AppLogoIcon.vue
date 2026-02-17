<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="#000000"
        id="Square-Letter-F--Streamline-Tabler-Filled"
        height="24"
        width="24"
    >
        <desc>Square Letter F Streamline Icon: https://streamlinehq.com</desc>
        <path
            d="M19 2a3 3 0 0 1 3 3v14a3 3 0 0 1 -3 3H5a3 3 0 0 1 -3 -3V5a3 3 0 0 1 3 -3zm-5 5h-4a1 1 0 0 0 -1 1v8a1 1 0 0 0 1 1l0.117 -0.007A1 1 0 0 0 11 16v-3h2a1 1 0 0 0 0.993 -0.883L14 12a1 1 0 0 0 -1 -1h-2V9h3a1 1 0 0 0 0 -2"
            stroke-width="1"
        ></path>
    </svg>
</template>
