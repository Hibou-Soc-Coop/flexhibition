<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const warning = ref(t('screenOrientation.warning'));
</script>

<template>
  <div class="inset-0 flex justify-center items-center bg-[#FFCC00] w-dvw text-black warning-message">
    <p class="text-lg text-center">{{ warning }}</p>
  </div>
</template>
