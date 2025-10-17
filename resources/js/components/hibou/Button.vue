<script setup lang="ts">
import { Button } from '@/components/ui/button';

interface CustomProps {
  colorScheme?: 'create' | 'save' | 'edit' | 'archive' | 'delete';
}

const props = defineProps<CustomProps>();

function getColorClasses(scheme?: string) {
  switch (scheme) {
    case 'create':
      return 'bg-green-500 hover:bg-green-600 text-white font-semibold';
    case 'save':
      return 'bg-blue-500 hover:bg-blue-600 text-white font-semibold';
    case 'edit':
      return 'bg-yellow-500 hover:bg-yellow-600 text-white font-semibold';
    case 'archive':
      return 'bg-slate-500 hover:bg-slate-600 text-white font-semibold';
    case 'delete':
      return 'bg-red-500 hover:bg-red-600 text-white font-semibold';
    default:
      return 'bg-gray-500 hover:bg-gray-600 text-white font-semibold';
  }
}
</script>

<template>
  <Button v-bind="$attrs" class="cursor-pointer" :class="getColorClasses(props.colorScheme)">
    <slot />
  </Button>
</template>
