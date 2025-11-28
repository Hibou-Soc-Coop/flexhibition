<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage, router } from '@inertiajs/vue3';
import  Button from '@/components/hibou/Button.vue';
import { type BreadcrumbItem } from '@/types';
import { type Language, MuseumData } from '@/types/flexhibition';
import Card from '@/components/hibou/Card.vue';

import museumsRoutes from '@/routes/museums';

const page = usePage();
const languages = page.props.languages as Language[];
const primaryLanguage = page.props.primaryLanguage as Language | null;

const primaryLanguageCode = primaryLanguage?.code || 'it';
const props = defineProps<{ museums: MuseumData[], maxMuseum: Number }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Museums',
        href: '#',
    },
];

function truncate(text: string | undefined, maxLength: number): string {
    if (!text) return '-';
    return text.length > maxLength
        ? text.substring(0, maxLength) + '...'
        : text;
}

</script>

<template>

    <Head title="Museums" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 py-8 container">
            <div class="flex items-center mb-4">
                <h1 class="font-bold text-3xl">Musei</h1>
                <Button v-if="props.museums.length < Number(props.maxMuseum)" @click="router.visit(museumsRoutes.create().url)" colorScheme="create" class="ml-6 h-8">Aggiungi nuovo museo</Button>
            </div>
            <div class="gap-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <Card
                       v-for="museum in props.museums"
                        :key="museum.id"
                       :route="museumsRoutes"
                       :id="museum.id"
                       :title="museum.name[primaryLanguageCode]"
                       :excerpt="truncate(museum.description[primaryLanguageCode], 60)"
                       :thumbnail="museum.logo.url ? museum.logo.url[primaryLanguageCode] : undefined"></Card>
                <div v-if="props.museums.length === 0" class="col-span-full py-8 text-muted-foreground text-center">
                    No museums found.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
