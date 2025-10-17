<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import MediaUploader from '@/components/hibou/MediaUploader.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { Museum, type Language } from '@/types/flexhibition';
import { type BreadcrumbItem } from '@/types';
import Button from '@/components/hibou/Button.vue';
import GalleryUploader from '@/components/hibou/GalleryUploader.vue';

const { museum, contents } = defineProps<{ museum: Museum, contents: Record<string, any> }>();

const page = usePage();
const languages = page.props.languages as Language[];
const primaryLanguage = page.props.primaryLanguage as Language;

const filteredLanguages = languages.filter(lang =>
    Object.keys(contents).includes(lang.language_code)
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Museo', href: route('museums.index') },
    { title: 'Dettaglio', href: '#' },
];
</script>

<template>

    <Head :title="contents[primaryLanguage.language_code].museum_name + ' - Dettaglio'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 md:p-8 container">
            <HButton as="a" :href="route('museums.edit', museum.museum_id)" class="mb-8 h-8" colorScheme="edit">
                <span>Modifica</span>
            </HButton>
            <div v-for="language in filteredLanguages" :key="language.language_code"
                 class="shadow-sm mb-8 p-4 border rounded-md">
                <h2 class="mb-4 font-semibold text-gray-700 text-xl">{{ language.language_name }}</h2>
                <div class="flex md:flex-row flex-col gap-6">
                    <div class="md:w-1/2">
                        <div class="mb-4">
                            <span class="block mb-1 font-medium text-gray-600">Logo</span>
                            <HMediaUploader v-model:model-value="contents[language.language_code].museum_logo"
                                            accept="image/*"
                                            isReadonly
                                            class="mb-4" />
                        </div>
                        <div v-if="language.language_code === primaryLanguage.language_code">
                            <span class="block mb-1 font-medium text-gray-600">Galleria</span>
                            <GalleryUploader v-if="Array.isArray(contents[language.language_code].museum_gallery) && contents[language.language_code].museum_gallery.length > 0"
                                             v-model="contents[language.language_code].museum_gallery"
                                             isReadonly />
                            <div v-else class="bg-gray-50 p-2 border rounded min-h-[2.5rem] text-gray-800">
                                Nessuna immagine caricata nella galleria
                            </div>
                        </div>
                        <div v-if="contents[language.language_code].museum_audio">
                            <span class="block mb-1 font-medium text-gray-600">Audio</span>
                            <HMediaUploader v-model:model-value="contents[language.language_code].museum_audio"
                                            accept="audio/*"
                                            isReadonly
                                            class="mb-4" />
                        </div>
                    </div>
                    <div class="md:w-1/2">
                        <div class="mb-4">
                            <span class="block mb-1 font-medium text-gray-600">Titolo</span>
                            <div class="bg-gray-50 p-2 border rounded min-h-[2.5rem] text-gray-800">
                                {{ contents[language.language_code].museum_name || '-' }}
                            </div>
                        </div>
                        <div class="mb-4">
                            <span class="block mb-1 font-medium text-gray-600">Descrizione</span>
                            <div class="bg-gray-50 p-2 border rounded min-h-[4rem] text-gray-800"
                                 v-html="contents[language.language_code].museum_description || '-'">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <HButton as="a" :href="route('museums.edit', museum.museum_id)" class="h-8" colorScheme="edit">
                <span>Modifica</span>
            </HButton>
        </div>
    </AppLayout>
</template>
