<script setup lang="ts">
import Button from '@/components/hibou/Button.vue';
import MultipleMediaUploader from '@/components/hibou/MultipleMediaUploader.vue';
import SingleMediaUpload from '@/components/hibou/SingleMediaUpload.vue';
import SingleMediaUploadLocalized from '@/components/hibou/SingleMediaUploadLocalized.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import museumsRoutes from '@/routes/museums';
import { type BreadcrumbItem } from '@/types';
import { type Language, MediaData, MediaDataLocalized } from '@/types/flexhibition';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { ref } from 'vue';

const page = usePage();
const languages = page.props.languages as Language[];
const primaryLanguage = page.props.primaryLanguage as Language | null;
const primaryLanguageCode = primaryLanguage?.code || 'it';
const currentLang = ref<string>(primaryLanguageCode);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Museums',
        href: '#',
    },
    {
        title: 'Create',
        href: museumsRoutes.create().url,
    },
];

const emptyContentByLanguage = Object.fromEntries(languages.map((l) => [l.code, '']));
const emptyMediaData = {
    id: null,
    file: null,
    url: null,
} as MediaData;
const emptyMediaDataLocalized = Object.fromEntries(
    languages.map((l) => [
        l.code,
        {
            id: null,
            file: null,
            url: null,
        },
    ]),
) as MediaDataLocalized;

const form = useForm({
    name: { ...emptyContentByLanguage },
    description: { ...emptyContentByLanguage },
    logo: { ...emptyMediaData } as MediaData | null,
    audio: { ...emptyMediaDataLocalized } as MediaDataLocalized,
    images: [] as MediaData[],
});

function submit() {
    form.post(museumsRoutes.store().url, {
        onFinish: () => {
            // Processing handled by Inertia automatically
        },
    });
}
</script>

<template>
    <Head title="Create Museum" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Aggiungi Museo">
            <template #button>
                <Button :disabled="form.processing" @click="submit">Crea Museo</Button>
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
                        <SingleMediaUploadLocalized v-model="form.audio" :is-readonly="false" :mimetype="'audio/*'" :current-lang="currentLang" />
                    </div>
                    <div class="col-start-2 col-end-3 row-start-1 row-end-3 rounded-lg border p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-lg font-semibold dark:text-gray-200">Informazioni Museo</h2>
                        <Tabs
                            v-model="currentLang"
                            default-value="it"
                            :unmount-on-hide="false"
                            class="grid w-full grid-cols-[15%_auto] gap-8"
                            orientation="vertical"
                        >
                            <TabsList class="grid h-fit w-full grid-cols-1 gap-2 border-r border-gray-200 pr-4 dark:border-gray-700">
                                <TabsTrigger
                                    v-for="language in languages"
                                    :key="language.code"
                                    :value="language.code"
                                    class="w-full justify-start dark:text-gray-300 dark:data-[state=active]:bg-gray-700"
                                >
                                    {{ language.name }}
                                </TabsTrigger>
                            </TabsList>
                            <TabsContent class="mt-1" v-for="language in languages" :key="language.code" :value="language.code">
                                <Label class="mb-2 block font-semibold dark:text-gray-200">Nome ({{ language.name }})</Label>
                                <Input class="mb-4 dark:bg-gray-700 dark:text-white" v-model="form.name[language.code]" />
                                <div v-if="form.errors[`name.${language.code}`]" class="mb-4 rounded bg-red-100 p-2 text-sm text-red-700">
                                    {{ form.errors[`name.${language.code}`] }}
                                </div>
                                <Label class="mb-2 block font-semibold dark:text-gray-200">Descrizione ({{ language.name }})</Label>
                                <div class="mb-4 rounded-md bg-white text-black dark:bg-gray-700">
                                    <QuillEditor v-model="form.description[language.code]" />
                                </div>
                            </TabsContent>
                        </Tabs>
                    </div>
                    <div class="col-span-2 rounded-lg border p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                        <Label class="mb-4 text-lg font-semibold dark:text-gray-200"> Immagini del Museo </Label>
                        <MultipleMediaUploader v-model="form.images" :is-readonly="false" :show-caption="false" :primary="true" />
                    </div>
                </div>

                <div class="mt-4">
                    <!-- Button passed in slot template above, repeated here for form submit if outside portal? No, usually not needed if template #button used -->
                </div>
            </form>
        </PageLayout>
    </AppLayout>
</template>
