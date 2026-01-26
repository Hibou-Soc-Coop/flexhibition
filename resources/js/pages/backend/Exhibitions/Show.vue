<script setup lang="ts">
import Button from '@/components/hibou/Button.vue';
import Label from '@/components/ui/label/Label.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ExhibitionData, type Language } from '@/types/flexhibition';
import { Head, router, usePage } from '@inertiajs/vue3';
import exhibitionsRoutes from '@/routes/exhibitions';

const page = usePage();
const languages = page.props.languages as Language[];
const primaryLanguage = page.props.primaryLanguage as Language;
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Exhibitions', href: exhibitionsRoutes.index().url },
    { title: 'Details', href: '#' },
];

const props = defineProps<{ exhibition: ExhibitionData }>();


const deleteExhibition = () => {
    if (confirm('Sei sicuro di voler eliminare questa mostra?')) {
        router.delete(exhibitionsRoutes.destroy.url(props.exhibition.id));
    }
};
</script>

<template>
    <Head :title="props.exhibition.name[primaryLanguage.code] + ' - Dettaglio'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Dettaglio Mostra">
            <template #button>
                <Button @click="router.visit(exhibitionsRoutes.edit.url(props.exhibition.id))" color-scheme="edit" class="mr-2">
                    Modifica Mostra
                </Button>
                <Button @click="deleteExhibition" color-scheme="delete">
                    Elimina Mostra
                </Button>
            </template>
            <div class="grid grid-cols-[1fr_4fr] grid-rows-[auto_auto] gap-4">
                <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow">
                    <Label class="block text-lg font-semibold"> Audio Mostra </Label>
                    <audio
                        v-if="props.exhibition.audio?.url[primaryLanguage.code]"
                        :src="`/storage/${props.exhibition.audio.url[primaryLanguage.code] || ''}`"
                        controls
                        class="mt-2 w-full"
                    />
                    <div v-else class="mt-2 w-full rounded-md border border-gray-300 bg-gray-100">
                        <p class="p-4 text-sm text-gray-500">Nessun audio disponibile</p>
                    </div>
                </div>
                <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow">
                    <Label class="block text-lg font-semibold"> Date Mostra </Label>
                    <p class="mt-2 w-full rounded-md border border-gray-300 bg-gray-100 py-6 px-4 text-md text-gray-500">
                        Data Inizio: {{ props.exhibition.start_date || '-' }} <br />
                        Data Fine: {{ props.exhibition.end_date || '-' }}
                    </p>
                </div>
                <div class="col-start-2 col-end-3 row-start-1 row-end-3 rounded-lg border p-4 shadow">
                    <h2 class="mb-4 text-lg font-semibold">Informazioni Mostra</h2>
                    <Tabs default-value="it" :unmount-on-hide="false" class="grid w-full grid-cols-[15%_auto] gap-8" orientation="vertical">
                        <TabsList class="grid h-fit w-full grid-cols-1 gap-2">
                            <template v-for="language in languages" :key="language.code">
                                <TabsTrigger
                                    v-if="props.exhibition.name[language.code]"
                                    :value="language.code"
                                >
                                    {{ language.name }}
                                </TabsTrigger>
                            </template>
                        </TabsList>
                        <TabsContent v-for="language in languages" :key="language.code" :value="language.code">
                            <Label class="mb-4 text-base font-semibold"> Nome Mostra - {{ language.name }} </Label>
                            <p class="mb-6 flex min-h-9 w-full items-center rounded-md border border-input px-3 py-1 text-sm shadow-xs shadow-input">
                                {{ props.exhibition.name[language.code] }}
                            </p>
                            <Label class="mb-4 text-base font-semibold"> Descrizione Mostra - {{ language.name }} </Label>
                            <div class="mb-6 flex min-h-9 w-full items-center rounded-md border border-input px-3 py-1 text-sm shadow-xs shadow-input"
                                v-html="props.exhibition.description?.[language.code] || '-'">
                            </div>
                            <Label class="mb-4 text-base font-semibold"> Museo - {{ language.name }} </Label>
                            <div class="mb-6 flex min-h-9 w-full items-center rounded-md border border-input px-3 py-1 text-sm shadow-xs shadow-input"
                                v-html="props.exhibition.museum_name?.[language.code] || '-'">
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>
                <div class="col-span-2 rounded-lg border p-4 shadow">
                    <Label class="mb-4 text-lg font-semibold">Immagini del Mostra</Label>
                    <div class="grid grid-cols-5 gap-4">
                        <div
                            v-for="(image, index) in Object.values(props.exhibition.images)"
                            :key="index"
                            class="aspect-square w-full overflow-hidden rounded-md border border-gray-300"
                        >
                            <img :src="`/storage/${image.url[primaryLanguage.code]}`" :alt="image.title[primaryLanguage.code]" class="h-full w-full object-cover" />
                        </div>
                    </div>
                </div>
            </div>
        </PageLayout>
    </AppLayout>
</template>
