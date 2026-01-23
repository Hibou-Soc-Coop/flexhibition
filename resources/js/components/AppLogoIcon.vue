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
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000000" id="Square-Letter-N--Streamline-Tabler-Filled" height="35" width="35">
  <desc>
    Square Letter N Streamline Icon: https://streamlinehq.com
  </desc>
  <path d="M19 2a3 3 0 0 1 3 3v14a3 3 0 0 1 -3 3H5a3 3 0 0 1 -3 -3V5a3 3 0 0 1 3 -3zm-8.106 5.553C10.423 6.609 9 6.945 9 8v8a1 1 0 0 0 1 1l0.117 -0.007A1 1 0 0 0 11 16v-3.764l2.106 4.211c0.471 0.944 1.894 0.608 1.894 -0.447V8a1 1 0 0 0 -1 -1l-0.117 0.007A1 1 0 0 0 13 8v3.764z" stroke-width="1"></path>
</svg>

</template>
