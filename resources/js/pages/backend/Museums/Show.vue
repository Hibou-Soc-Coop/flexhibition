<script setup lang="ts">
import Button from '@/components/hibou/Button.vue';
import Label from '@/components/ui/label/Label.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import { default as museumRoutes, default as museumsRoutes } from '@/routes/museums';
import { type BreadcrumbItem } from '@/types';
import { MuseumData, type Language } from '@/types/flexhibition';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ museum: MuseumData }>();

const page = usePage();
const languages = page.props.languages as Language[];
const primaryLanguage = page.props.primaryLanguage as Language;
const primaryLanguageCode = primaryLanguage?.code || 'it';
const currentLangCode = ref<string>(primaryLanguageCode);
const currentLangName = computed(() => {
    const lang = languages.find((l) => l.code === currentLangCode.value);
    return lang ? lang.name : currentLangCode.value;
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Museo', href: museumRoutes.index().url },
    { title: 'Dettaglio', href: '#' },
];

const deleteMuseum = () => {
    if (confirm('Sei sicuro di voler eliminare questo museo?')) {
        router.delete(museumRoutes.destroy.url(props.museum.id));
    }
};

const logo = props.museum.logo;
const audio = props.museum.audio;
const gallery = props.museum.images;
</script>

<template>

    <Head :title="props.museum.name[primaryLanguage.code] + ' - Dettaglio'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Dettaglio Museo">
            <template #button>
                <div class="flex gap-2">
                    <Button @click="router.visit(museumsRoutes.edit.url(props.museum.id))" color-scheme="edit"> Modifica Museo </Button>
                    <Button @click="deleteMuseum" color-scheme="delete"> Elimina Museo </Button>
                </div>
            </template>
            <div class="grid grid-cols-[1fr_4fr] grid-rows-[auto_auto] gap-4 dark:text-white">
                <div class="rounded-lg border p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                    <Label class="mb-4 text-lg font-semibold dark:text-gray-200"> Logo </Label>
                    <div class="flex max-h-80 items-center justify-center overflow-hidden rounded-md border border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-900">
                        <img v-if="logo?.url" :src="logo.url" alt="Logo Museo" class="h-full w-full object-contain" />
                        <div v-else class="flex h-40 w-full items-center justify-center text-gray-400 dark:text-gray-500">Nessun logo</div>
                    </div>
                </div>
                <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                    <Label class="block text-lg font-semibold dark:text-gray-200"> Audio ({{ currentLangName }})</Label>
                    <audio v-if="audio?.[currentLangCode]?.url" :src="audio?.[currentLangCode]?.url ?? undefined" controls class="mt-2 w-full" />
                    <div v-else class="mt-2 w-full rounded-md border border-gray-300 bg-gray-100 p-4 text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-400">
                        Nessun audio disponibile
                    </div>
                </div>
                <div class="col-start-2 col-end-3 row-start-1 row-end-3 rounded-lg border p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-semibold dark:text-gray-200">Informazioni</h2>
                    <Tabs v-model="currentLangCode" default-value="it" :unmount-on-hide="false" class="grid w-full grid-cols-[15%_auto] gap-8" orientation="vertical">
                        <TabsList class="grid h-fit w-full grid-cols-1 gap-2 border-r border-gray-200 pr-4 dark:border-gray-700">
                            <template v-for="language in languages" :key="language.code">
                                <TabsTrigger
                                             v-if="props.museum.name[language.code] || props.museum.description[language.code]"
                                             :value="language.code"
                                             class="w-full justify-start dark:text-gray-300 dark:data-[state=active]:bg-gray-700">
                                    {{ language.name }}
                                </TabsTrigger>
                            </template>
                        </TabsList>
                        <TabsContent v-for="language in languages" :key="language.code" :value="language.code" class="mt-0">
                            <Label class="mb-2 block font-semibold dark:text-gray-200"> Nome ({{ language.name }}) </Label>
                            <p class="mb-6 flex min-h-9 w-full items-center rounded-md border border-input px-3 py-1 text-sm shadow-xs shadow-input dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                {{ props.museum.name[language.code] }}
                            </p>
                            <Label class="mb-2 block font-semibold dark:text-gray-200"> Descrizione ({{ language.name }}) </Label>
                            <div class="prose dark:prose-invert mb-2 flex min-h-15 w-full max-w-none items-center rounded-md border border-input px-3 py-1 text-sm shadow-xs shadow-input dark:border-gray-600 dark:bg-gray-900 dark:text-white" v-html="props.museum.description[language.code] || '-'"></div>
                        </TabsContent>
                    </Tabs>
                </div>
                <div class="col-span-2 rounded-lg border p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                    <Label class="mb-4 text-lg font-semibold dark:text-gray-200"> Galleria </Label>
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-5">
                        <div v-for="(image, index) in gallery" :key="index" class="aspect-square w-full overflow-hidden rounded-md border border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-900">
                            <img :src="image.original_url" :alt="image.custom_properties?.title || ''" class="h-full w-full object-cover" />
                        </div>
                        <div v-if="gallery.length === 0" class="col-span-full py-4 text-center text-gray-500 dark:text-gray-400">
                            Nessuna immagine presente.
                        </div>
                    </div>
                </div>
            </div>
        </PageLayout>
    </AppLayout>
</template>
