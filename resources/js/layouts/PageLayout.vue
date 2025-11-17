<script setup lang="ts">

const props = defineProps<{ title?: string }>()
</script>

<template>
    <div class="m-4 border shadow rounded-lg">
        <div v-if="props.title" class="p-4 flex gap-6 border-b">
            <h1 class="font-bold text-2xl">{{ props.title }}</h1>
            <slot name="button" />
        </div>
        <div class="p-4">
            <slot />
        </div>
    </div>
</template>
