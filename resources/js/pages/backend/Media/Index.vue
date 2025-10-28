<script setup lang="ts">
import Button from '@/components/hibou/Button.vue';
import MediaGallery from '@/components/hibou/MediaGallery.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import { Language } from '@/types/flexhibition';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{ primaryLanguage: Language; mediaItems: Array<any> }>();

const isNewMediaOpen = ref(false);
</script>

<template>
    <Head title="Galleria Media" />
    <AppLayout>
        <PageLayout title="Galleria Media">
            <template #button>
                <Button href="/admin/media/upload" color-scheme="create" class="btn btn-primary" @click="isNewMediaOpen = true"> Upload Media </Button>
            </template>
                <MediaGallery
                    :mediaItems="props.mediaItems"
                    :primary-language="props.primaryLanguage"
                    :is-new-media-open="isNewMediaOpen"
                />
        </PageLayout>
    </AppLayout>
</template>
