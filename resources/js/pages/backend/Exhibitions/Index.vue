<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage, router } from '@inertiajs/vue3';
import  Button from '@/components/hibou/Button.vue';
import { type BreadcrumbItem } from '@/types';
import { type Language, ExhibitionData } from '@/types/flexhibition';
import Card from '@/components/hibou/Card.vue';
import exhibitionsRoutes from '@/routes/exhibitions';

const page = usePage();
const languages = page.props.languages as Language[];
const primaryLanguage = page.props.primaryLanguage as Language | null;

const primaryLanguageCode = primaryLanguage?.code || 'it';
const props = defineProps<{ exhibitions: ExhibitionData[] }>();
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Exhibitions',
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

    <Head title="Exhibitions" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 py-8 container">
            <div class="flex items-center mb-4">
                <h1 class="font-bold text-3xl">Collezioni</h1>
                <Button @click="router.visit(exhibitionsRoutes.create().url)" colorScheme="create" class="ml-6 h-8">Aggiungi nuova collezione</Button>
            </div>
            <div class="gap-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <Card
                       v-for="exhibition in props.exhibitions"
                        :key="exhibition.id"
                       :route="exhibitionsRoutes"
                       :id="exhibition.id"
                       :title="exhibition.name[primaryLanguageCode]"
                       :excerpt="truncate(exhibition.description?.[primaryLanguageCode], 60)"
                       :thumbnail="exhibition.images?.[0]?.url?.[primaryLanguageCode] ? `/storage/${exhibition.images[0].url[primaryLanguageCode]}` : '/storage/sample-data/images/placeholder.jpg'"></Card>
                <div v-if="props.exhibitions.length === 0" class="col-span-full py-8 text-muted-foreground text-center">
                    No exhibitions found.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
