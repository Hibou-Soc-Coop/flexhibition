<script setup lang="ts">
import Button from '@/components/hibou/Button.vue';
import Label from '@/components/ui/label/Label.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import museumRoutes from '@/routes/museums';
import { type BreadcrumbItem } from '@/types';
import { MuseumData, type Language } from '@/types/flexhibition';
import { Head, router, usePage } from '@inertiajs/vue3';

const props = defineProps<{ museum: MuseumData }>();

const page = usePage();
const languages = page.props.languages as Language[];
const primaryLanguage = page.props.primaryLanguage as Language;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Museo', href: museumRoutes.index().url },
    { title: 'Dettaglio', href: '#' },
];

const deleteMuseum = () => {
    if (confirm('Sei sicuro di voler eliminare questo museo?')) {
        router.delete(museumRoutes.destroy.url(props.museum.id));
    }
};
</script>

<template>
    <Head :title="props.museum.name[primaryLanguage.code] + ' - Dettaglio'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Dettaglio Museo">
            <template #button>
                <Button :href="museumRoutes.edit.url(props.museum.id)" color-scheme="edit" class="mr-2">
                    Modifica Museo
                </Button>
                <Button @click="deleteMuseum" color-scheme="delete">
                    Elimina Museo
                </Button>
            </template>
            <div class="grid grid-cols-[1fr_4fr] grid-rows-[auto_auto] gap-4">
                <div class="rounded-lg border p-4 shadow">
                    <Label class="mb-4 text-lg font-semibold"> Logo Museo </Label>
                    <div class="overflow-hidden rounded-md border border-gray-300">
                        <img
                            v-if="props.museum.logo.url[primaryLanguage.code]"
                            :src="props.museum.logo.url[primaryLanguage.code]"
                            :alt="props.museum.logo.title[primaryLanguage.code]"
                            class="h-full w-full object-cover"
                        />
                        <img v-else src="/images/placeholder-image.png" alt="Placeholder" class="h-full w-full object-cover" />
                    </div>
                </div>
                <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow">
                    <Label class="block text-lg font-semibold"> Audio Museo </Label>
                    <audio
                        v-if="props.museum.audio.url[primaryLanguage.code]"
                        :src="props.museum.audio.url[primaryLanguage.code]"
                        controls
                        class="mt-2 w-full"
                    />
                    <div v-else class="mt-2 w-full rounded-md border border-gray-300 bg-gray-100">
                        <p class="p-4 text-sm text-gray-500">Nessun audio disponibile</p>
                    </div>
                </div>
                <div class="col-start-2 col-end-3 row-start-1 row-end-3 rounded-lg border p-4 shadow">
                    <h2 class="mb-4 text-lg font-semibold">Informazioni Museo</h2>
                    <Tabs default-value="it" :unmount-on-hide="false" class="grid w-full grid-cols-[15%_auto] gap-8" orientation="vertical">
                        <TabsList class="grid h-fit w-full grid-cols-1 gap-2">
                            <template v-for="language in languages" :key="language.code">
                                <TabsTrigger
                                    v-if="props.museum.name[language.code] || props.museum.description[language.code]"
                                    :value="language.code"
                                >
                                    {{ language.name }}
                                </TabsTrigger>
                            </template>
                        </TabsList>
                        <TabsContent v-for="language in languages" :key="language.code" :value="language.code">
                            <Label class="mb-4 text-base font-semibold"> Nome Museo - {{ language.name }} </Label>
                            <p class="mb-6 flex min-h-9 w-full items-center rounded-md border border-input px-3 py-1 text-sm shadow-xs shadow-input">
                                {{ props.museum.name[language.code] }}
                            </p>
                            <Label class="mb-4 text-base font-semibold"> Descrizione Museo - {{ language.name }} </Label>
                            <p class="mb-6 flex min-h-9 w-full items-center rounded-md border border-input px-3 py-1 text-sm shadow-xs shadow-input">
                                {{ props.museum.description[language.code] }}
                            </p>
                        </TabsContent>
                    </Tabs>
                </div>
                <div class="col-span-2 rounded-lg border p-4 shadow">
                    <Label class="mb-4 text-lg font-semibold"> Immagini del Museo </Label>
                    <div class="grid grid-cols-5 gap-4">
                        <div
                            v-for="(image, index) in Object.values(props.museum.images)"
                            :key="index"
                            class="aspect-square w-full overflow-hidden rounded-md border border-gray-300"
                        >
                            <img :src="image.url[primaryLanguage.code]" :alt="image.title[primaryLanguage.code]" class="h-full w-full object-cover" />
                        </div>
                    </div>
                </div>
            </div>
        </PageLayout>
    </AppLayout>
</template>
