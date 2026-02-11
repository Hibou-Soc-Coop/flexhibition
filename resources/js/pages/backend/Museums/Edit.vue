<script setup lang="ts">
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/hibou/Button.vue';
import Label from '@/components/ui/label/Label.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import museumRoutes from '@/routes/museums';
import SingleMediaUpload from '@/components/hibou/SingleMediaUpload.vue';
import { type BreadcrumbItem } from '@/types';
import { MuseumData, type Language, MediaData, SpatieMedia } from '@/types/flexhibition';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import TipTap from '@/components/hibou/TipTap.vue';
import MultipleMediaUploader from '@/components/hibou/MultipleMediaUploader.vue';
import { ref } from 'vue';

const props = defineProps<{ museum: MuseumData }>();

const page = usePage();
const languages = page.props.languages as Language[];
const primaryLanguage = page.props.primaryLanguage as Language;
const currentLang = ref<string>(primaryLanguage.code);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Museo', href: museumRoutes.index().url },
    { title: 'Modifica', href: '#' },
];

const deleteMuseum = () => {
    if (confirm('Sei sicuro di voler eliminare questo museo?')) {
        router.delete(museumRoutes.destroy.url(props.museum.id));
    }
};

const emptyByLanguage = Object.fromEntries(languages.map((l) => [l.code, '']));

// Helpers to transform SpatieMedia[] to MediaData
function transformSingleMedia(mediaList: SpatieMedia[]): MediaData | null {
    if (!mediaList || mediaList.length === 0) return null;
    const res: MediaData = {
        id: mediaList[0].id,
        url: {},
        title: {},
        description: {},
        file: {}
    };
    mediaList.forEach(m => {
        const lang = m.custom_properties?.lang || 'it';
        if (res.url) res.url[lang] = m.original_url;
        if (res.title) res.title[lang] = m.custom_properties?.title || '';
        if (res.description) res.description[lang] = m.custom_properties?.description || '';
    });
    return res;
}

function transformGalleryMedia(mediaList: SpatieMedia[]): MediaData[] {
    const groups: Record<number, MediaData> = {};
    mediaList.forEach(m => {
        const idx = m.custom_properties?.group_index ?? m.id;
        if (!groups[idx]) {
            groups[idx] = {
                id: m.id,
                url: {}, title: {}, description: {}, file: {}
            };
        }
        const lang = m.custom_properties?.lang || 'it';
        if (groups[idx].url) groups[idx].url![lang] = m.original_url;
        if (groups[idx].title) groups[idx].title[lang] = m.custom_properties?.title || '';
        if (groups[idx].description) groups[idx].description![lang] = m.custom_properties?.description || '';
    });
    return Object.values(groups);
}

const form = useForm({
    name: props.museum.name ?? { ...emptyByLanguage },
    description: props.museum.description ?? { ...emptyByLanguage },
    logo: transformSingleMedia(props.museum.logo) as MediaData | null,
    audio: transformSingleMedia(props.museum.audio) as MediaData | null,
    images: transformGalleryMedia(props.museum.images) as MediaData[],
});

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'PUT'
    })).post(museumRoutes.update.url(props.museum.id), {
        onFinish: () => {
            form.processing = false;
        },
    });
}
</script>

<template>

    <Head :title="props.museum.name[primaryLanguage.code] + ' - Modifica'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Modifica Museo">
            <template #button>
                <div class="flex gap-2">
                    <Button :disabled="form.processing" @click="submit" color-scheme="create">
                        Salva Modifiche
                    </Button>
                    <Button @click="deleteMuseum" color-scheme="delete">
                        Elimina Museo
                    </Button>
                </div>
            </template>
            <form @submit.prevent="submit">
                <div class="grid grid-cols-[1fr_4fr] grid-rows-[auto_auto] gap-4 dark:text-white">
                    <div class="rounded-lg border p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                        <Label class="mb-4 text-lg font-semibold dark:text-gray-200"> Logo Museo </Label>
                        <div class="overflow-hidden rounded-md border border-gray-300 dark:border-gray-600">
                            <SingleMediaUpload multi-language :current-lang="currentLang" v-model="form.logo" :is-readonly="false" :accept="'image/*'" :max-file-size="5 * 1024 * 1024" />
                        </div>
                    </div>
                    <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                        <Label class="block text-lg font-semibold dark:text-gray-200"> Audio Museo </Label>
                        <SingleMediaUpload multi-language :current-lang="currentLang" v-model="form.audio" :is-readonly="false" :accept="'audio/*'" :max-file-size="10 * 1024 * 1024" />
                    </div>
                    <div class="col-start-2 col-end-3 row-start-1 row-end-3 rounded-lg border p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-lg font-semibold dark:text-gray-200">Informazioni Museo</h2>
                        <Tabs v-model="currentLang" :unmount-on-hide="false" class="grid w-full grid-cols-[15%_auto] gap-8" orientation="vertical">
                            <TabsList class="grid h-fit w-full grid-cols-1 gap-2 dark:bg-gray-800">
                                <TabsTrigger v-for="language in languages" :key="language.code" :value="language.code" class="dark:data-[state=active]:bg-gray-700 dark:text-gray-300">
                                    {{ language.name }}
                                </TabsTrigger>
                            </TabsList>
                            <TabsContent v-for="language in languages" :key="language.code" :value="language.code" class="mt-0">
                                <Label class="mb-2 block font-semibold dark:text-gray-200">Nome ({{ language.name }})</Label>
                                <Input class="mb-4 dark:bg-gray-700 dark:text-white" v-model="form.name[language.code]" />
                                <div v-if="form.errors[`name.${language.code}`]" class="mb-4 rounded bg-red-100 p-2 text-sm text-red-700">
                                    {{ form.errors[`name.${language.code}`] }}
                                </div>
                                <Label class="mb-2 block font-semibold dark:text-gray-200">Descrizione ({{ language.name }})</Label>
                                <div class="mb-4 bg-white dark:bg-gray-700 rounded-md text-black">
                                    <!-- TipTap usually handles its own styling, but container might need tweaks -->
                                    <TipTap v-model="form.description[language.code]" />
                                </div>
                            </TabsContent>
                        </Tabs>
                    </div>
                    <div class="col-span-2 rounded-lg border p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                        <Label class="mb-4 text-lg font-semibold dark:text-gray-200"> Immagini del Museo </Label>
                        <MultipleMediaUploader v-model="form.images" :is-readonly="false" :show-caption="false" :primary="true" />
                    </div>
                </div>
            </form>
        </PageLayout>
    </AppLayout>
</template>
