<script setup lang="ts">
import { Button } from '@/components/ui/button';

interface CustomProps {
  colorScheme?: 'create' | 'save' | 'edit' | 'archive' | 'delete';
}

const props = defineProps<CustomProps>();

function getColorClasses(scheme?: string) {
  switch (scheme) {
    case 'show':
      return 'bg-flex-show-400 hover:bg-flex-show-600 text-white font-semibold';
    case 'create':
      return 'bg-flex-create-400 hover:bg-flex-create-600 text-white font-semibold';
    case 'save':
      return 'bg-flex-save-400 hover:bg-flex-save-600 text-white font-semibold';
    case 'edit':
      return 'bg-flex-edit-400 hover:bg-flex-edit-600 text-white font-semibold';
    // case 'archive':
    //   return 'bg-flex-archive hover:bg-slate-600 text-white font-semibold';
    case 'delete':
      return 'bg-flex-delete-400 hover:bg-flex-delete-600 text-white font-semibold';
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
