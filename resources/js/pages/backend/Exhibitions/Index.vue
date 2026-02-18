<script setup lang="ts">
import Button from '@/components/hibou/Button.vue';
import Card from '@/components/hibou/Card.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import exhibitionsRoutes from '@/routes/exhibitions';
import { type BreadcrumbItem } from '@/types';
import { type Language, ExhibitionData } from '@/types/flexhibition';
import { Head, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const primaryLanguage = page.props.primaryLanguage as Language | null;
const primaryLanguageCode = primaryLanguage?.code || 'it';

const props = defineProps<{ exhibitions: ExhibitionData[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mostre',
        href: '#',
    },
];

function truncate(text: string | undefined, maxLength: number): string {
    if (!text) return '-';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function getTranslation(field: Record<string, string> | null, lang: string): string {
    return field ? field[lang] || '' : '';
}
</script>

<template>
    <Head title="Mostre" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Elenco Mostre">
            <template #button>
                <div class="flex gap-2">
                    <Button
                        @click="router.visit(exhibitionsRoutes.create().url)"
                        colorScheme="create"
                        class="ml-6 h-8"
                        >Aggiungi nuova mostra</Button
                    >
                </div>
            </template>
            <div class="gap-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <Card
                    v-for="exhibition in props.exhibitions"
                    :key="exhibition.id"
                    :route="exhibitionsRoutes"
                    :id="exhibition.id"
                    :title="getTranslation(exhibition.name, primaryLanguageCode)"
                    :excerpt="
                        truncate(getTranslation(exhibition.description, primaryLanguageCode), 60)
                    "
                    :thumbnail="
                        exhibition.images?.[0]?.url ?? '/storage/sample-data/images/placeholder.jpg'
                    "
                ></Card>
                <div
                    v-if="props.exhibitions.length === 0"
                    class="col-span-full py-8 text-muted-foreground text-center"
                >
                    Nessuna mostra trovata.
                </div>
            </div>
        </PageLayout>
    </AppLayout>
</template>
