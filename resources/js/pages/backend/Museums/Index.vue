<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage, router } from '@inertiajs/vue3';
import Button from '@/components/hibou/Button.vue';
import { type BreadcrumbItem } from '@/types';
import { MuseumData, type Language } from '@/types/flexhibition';
import Card from '@/components/hibou/Card.vue';
import museumsRoutes from '@/routes/museums';
import PageLayout from '@/layouts/PageLayout.vue';

const page = usePage();
const primaryLanguage = page.props.primaryLanguage as Language | null;
const primaryLanguageCode = primaryLanguage?.code || 'it';
const props = defineProps<{ museums: MuseumData[], maxMuseum: number }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Museums',
        href: '#',
    },
];

function truncate(text: string | null | undefined, maxLength: number): string {
    if (!text) return '-';
    return text.length > maxLength
        ? text.substring(0, maxLength) + '...'
        : text;
}
/**
 * Helper to safely get translation
 */
function getTranslation(field: Record<string, string> | null, lang: string): string {
    return field ? (field[lang] || '') : '';
}

</script>

<template>

    <Head title="Museums" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Elenco Musei">
            <template #button>
                <div class="flex gap-2">
                    <Button v-if="props.museums.length < Number(props.maxMuseum)" @click="router.visit(museumsRoutes.create().url)" colorScheme="create" class="ml-6 h-8">Aggiungi nuovo museo</Button>
                </div>
            </template>
            <div class="gap-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <Card v-for="museum in props.museums" :key="museum.id" :route="museumsRoutes" :id="museum.id" :title="getTranslation(museum.name, primaryLanguageCode)" :excerpt="truncate(getTranslation(museum.description, primaryLanguageCode), 60)" :thumbnail="museum.logo.url || '/storage/sample-data/images/placeholder.jpg'"></Card>
                <div v-if="props.museums.length === 0" class="col-span-full py-8 text-muted-foreground text-center dark:text-gray-400">
                    Nessun museo trovato.
                </div>
            </div>
        </PageLayout>
    </AppLayout>
</template>
