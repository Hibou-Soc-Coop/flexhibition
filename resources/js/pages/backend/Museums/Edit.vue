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
import { MuseumData, type Language, MediaData } from '@/types/flexhibition';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import MultipleMediaUploader from '@/components/hibou/MultipleMediaUploader.vue';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
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


const form = useForm({
    name: props.museum.name ?? { ...emptyByLanguage },
    description: props.museum.description ?? { ...emptyByLanguage },
    logo: props.museum.logo as MediaData | null,
    audio: props.museum.audio as MediaData | null,
    images: props.museum.images as MediaData[],
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
                            <SingleMediaUpload v-model="form.logo" :is-readonly="false" :mimetype="'image/*'" :max-file-size="5 * 1024 * 1024" />
                        </div>
                    </div>
                    <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                        <Label class="block text-lg font-semibold dark:text-gray-200"> Audio Museo </Label>
                        <SingleMediaUpload v-model="form.audio" :is-readonly="false" :mimetype="'audio/*'" :max-file-size="10 * 1024 * 1024" />
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
                                    <QuillEditor class="min-h-30" v-model:content="form.description[language.code]" content-type="html" />
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
