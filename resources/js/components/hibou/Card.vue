<script setup lang="ts">
import Button from '@/components/hibou/Button.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
// import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
// import { byPrefixAndName } from '@awesome.me/kit-d33824e8fe/icons';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

import type { ResourceRoutes } from '@/types/flexhibition';

const props = defineProps<{
    route: ResourceRoutes;
    id: string | number;
    title: string;
    excerpt?: string;
    thumbnail?: string;
    tag?: string;
}>();

const isImage = computed(() =>
    props.thumbnail
        ? props.thumbnail.match(/\.(jpg|png|svg|webp|gif)$/i) || props.thumbnail.includes('unsplash')
        : false,
);

const isAudio = computed(() =>
    props.thumbnail ? props.thumbnail.match(/\.(mp3|wav)$/i) : false,
);

</script>

<template>
    <Card
        class="group relative flex h-full cursor-pointer flex-col overflow-hidden transition-shadow hover:shadow-lg"
        @click="router.visit(route.show(id).url)"
    >
        <div
            v-if="tag"
            class="absolute top-1 left-1 z-10 rounded bg-slate-500 px-2 py-0.5 text-xs font-semibold text-white"
        >
            {{ tag }}
        </div>
        <div class="absolute top-2 right-2 z-10 flex gap-2">
            <Button
                size="icon"
                class="rounded-full bg-white p-2 opacity-0 shadow transition-opacity group-hover:opacity-100 hover:bg-yellow-400"
                @click.stop="router.visit(route.edit(id).url)"
                title="Modifica media"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                    <!-- !Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. -->
                    <path
                        d="M416.9 85.2L372 130.1L509.9 268L554.8 223.1C568.4 209.6 576 191.2 576 172C576 152.8 568.4 134.4 554.8 120.9L519.1 85.2C505.6 71.6 487.2 64 468 64C448.8 64 430.4 71.6 416.9 85.2zM338.1 164L122.9 379.1C112.2 389.8 104.4 403.2 100.3 417.8L64.9 545.6C62.6 553.9 64.9 562.9 71.1 569C77.3 575.1 86.2 577.5 94.5 575.2L222.3 539.7C236.9 535.6 250.2 527.9 261 517.1L476 301.9L338.1 164z"
                    />
                </svg>
                <!-- <FontAwesomeIcon class="text-black text-4xl" :icon="byPrefixAndName.fas['pen']" /> -->
            </Button>
            <slot name="extra-actions" />
        </div>
        <CardHeader>
            <CardTitle class="text-lg">{{ title }}</CardTitle>
            <CardDescription
                v-if="excerpt"
                v-html="excerpt"
                class="pt-1 text-sm text-muted-foreground"
            >
            </CardDescription>
        </CardHeader>
        <CardContent
            v-if="thumbnail"
            :class="[
                'mt-auto flex items-center justify-center',
                { 'flex-grow': isAudio },
            ]"
        >
            <img
                v-if="isImage"
                :src="thumbnail"
                :alt="title"
                class="h-auto w-full rounded-md border object-contain max-h-60"
            />
            <svg v-if="isAudio" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                <!-- !Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc. -->
                <path
                    d="M128 128C128 92.7 156.7 64 192 64L341.5 64C358.5 64 374.8 70.7 386.8 82.7L493.3 189.3C505.3 201.3 512 217.6 512 234.6L512 512C512 547.3 483.3 576 448 576L192 576C156.7 576 128 547.3 128 512L128 128zM336 122.5L336 216C336 229.3 346.7 240 360 240L453.5 240L336 122.5zM389.8 307.7C380.7 301.4 368.3 303.6 362 312.7C355.7 321.8 357.9 334.2 367 340.5C390.9 357.2 406.4 384.8 406.4 416C406.4 447.2 390.8 474.9 367 491.5C357.9 497.8 355.7 510.3 362 519.3C368.3 528.3 380.8 530.6 389.8 524.3C423.9 500.5 446.4 460.8 446.4 416C446.4 371.2 424 331.5 389.8 307.7zM208 376C199.2 376 192 383.2 192 392L192 440C192 448.8 199.2 456 208 456L232 456L259.2 490C262.2 493.8 266.8 496 271.7 496L272 496C280.8 496 288 488.8 288 480L288 352C288 343.2 280.8 336 272 336L271.7 336C266.8 336 262.2 338.2 259.2 342L232 376L208 376zM336 448.2C336 458.9 346.5 466.4 354.9 459.8C367.8 449.5 376 433.7 376 416C376 398.3 367.8 382.5 354.9 372.2C346.5 365.5 336 373.1 336 383.8L336 448.3z"
                />
            </svg>
            <!-- <FontAwesomeIcon v-else-if="isAudio" class="text-black text-9xl" :icon="byPrefixAndName.fas['file-audio']" /> -->
        </CardContent>
    </Card>
</template>
