<script setup lang="ts">
import Button from '@/components/hibou/Button.vue';
import MultipleMediaUploader from '@/components/hibou/MultipleMediaUploader.vue';
import SingleMediaUploadLocalized from '@/components/hibou/SingleMediaUploadLocalized.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import exhibitionRoutes from '@/routes/exhibitions';
import { type BreadcrumbItem } from '@/types';
import {
    ExhibitionData,
    MediaData,
    MediaDataLocalized,
    MuseumData,
    MuseumMinimalData,
    type Language,
} from '@/types/flexhibition';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const props = defineProps<{ exhibition: ExhibitionData; museums: MuseumData[] }>();

const page = usePage();
const languages = page.props.languages as Language[];
console.log('museums:', props.museums);
const primaryLanguage = page.props.primaryLanguage as Language;
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Exhibitions', href: exhibitionRoutes.index().url },
    { title: 'Edit', href: '#' },
];

const deleteExhibition = () => {
    if (confirm('Sei sicuro di voler eliminare questo mostra?')) {
        router.delete(exhibitionRoutes.destroy.url(props.exhibition.id));
    }
};

const emptyContentByLanguage = Object.fromEntries(languages.map((l) => [l.code, '']));

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
    name: props.exhibition.name || { ...emptyContentByLanguage },
    description: props.exhibition.description ?? { ...emptyContentByLanguage },
    start_date: props.exhibition.start_date ?? ('' as string),
    end_date: props.exhibition.end_date ?? ('' as string),
    audio: props.exhibition.audio ?? ({ ...emptyMediaDataLocalized } as MediaDataLocalized),
    images: props.exhibition.images || ([] as MediaData[]),
    is_archived: props.exhibition.is_archived ?? false,
    museum: props.exhibition.museum ?? (null as MuseumMinimalData | null),
});

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(exhibitionRoutes.update.url(props.exhibition.id), {
        onFinish: () => {
            form.processing = false;
        },
    });
}
</script>

<template>
    <Head :title="props.exhibition.name[primaryLanguage.code] + ' - Modifica'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Dettaglio Mostra">
            <form @submit.prevent="submit">
                <div class="mb-4 text-right">
                    <Button
                        :disabled="form.processing"
                        color-scheme="create"
                        class="mr-2"
                    >
                        Salva Modifiche
                    </Button>
                    <Button
                        @click="deleteExhibition"
                        color-scheme="delete"
                    >
                        Elimina
                    </Button>
                </div>
                <div class="grid grid-cols-[1fr_4fr] grid-rows-[auto_auto] gap-4">
                    <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow">
                        <Label class="block text-lg font-semibold"> Audio </Label>
                        <SingleMediaUploadLocalized
                            v-model="form.audio"
                            v-if="props.exhibition.audio"
                            :media_preview="`/storage/${props.exhibition.audio?.url[primaryLanguage.code]}`"
                            :is-readonly="false"
                            :mimetype="'audio/*'"
                            :max-file-size="10 * 1024 * 1024"
                        />
                        <div
                            v-else
                            class="mt-2 w-full rounded-md border border-gray-300 bg-gray-100"
                        >
                            <p class="p-4 text-sm text-gray-500">Nessun audio disponibile</p>
                        </div>
                    </div>
                    <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow">
                        <Label class="block text-lg font-semibold"> Data d'inizio </Label>
                        <Input
                            class="mb-4"
                            v-model="form.start_date"
                            type="date"
                        />
                        <Label class="block text-lg font-semibold"> Data di fine </Label>
                        <Input
                            class="mb-4"
                            v-model="form.end_date"
                            type="date"
                        />
                    </div>
                    <div
                        class="col-start-2 col-end-3 row-start-1 row-end-3 rounded-lg border p-4 shadow"
                    >
                        <h2 class="mb-4 text-lg font-semibold">Informazioni</h2>
                        <Tabs
                            default-value="it"
                            :unmount-on-hide="false"
                            class="grid w-full grid-cols-[15%_auto] gap-8"
                            orientation="vertical"
                        >
                            <TabsList class="grid h-fit w-full grid-cols-1 gap-2">
                                <template
                                    v-for="language in languages"
                                    :key="language.code"
                                >
                                    <TabsTrigger :value="language.code">
                                        {{ language.name }}
                                    </TabsTrigger>
                                </template>
                            </TabsList>
                            <TabsContent
                                v-for="language in languages"
                                :key="language.code"
                                :value="language.code"
                            >
                                <Label class="mb-4 text-base font-semibold">
                                    Nome - {{ language.name }}
                                </Label>
                                <Input
                                    class="mb-4"
                                    v-model="form.name[language.code]"
                                />
                                <div
                                    v-if="form.errors[`name.${language.code}`]"
                                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-700"
                                >
                                    {{ form.errors[`name.${language.code}`] }}
                                </div>
                                <Label class="mb-4 text-base font-semibold">
                                    Descrizione - {{ language.name }}
                                </Label>
                                <div class="mb-4">
                                    <QuillEditor
                                        v-model:content="form.description[language.code]"
                                        content-type="html"
                                    />
                                </div>
                                <div class="mb-4 hidden">
                                    <label class="inline-flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="form.is_archived"
                                            class="form-checkbox"
                                        />
                                        <span class="ml-2"> Archiviata </span>
                                    </label>
                                </div>
                                <Label class="mb-1 font-semibold"> Museo </Label>
                                <Select
                                    class="mb-4"
                                    v-model="form.museum.id"
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleziona museo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="museum in props.museums"
                                            :key="museum.id"
                                            :value="museum.id"
                                        >
                                            {{ museum.name[language.code] }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </TabsContent>
                        </Tabs>
                    </div>
                    <div class="col-span-2 rounded-lg border p-4 shadow">
                        <Label class="mb-4 text-lg font-semibold"> Galleria </Label>

                        <MultipleMediaUploader
                            v-model="form.images"
                            :is-readonly="false"
                            :show-caption="false"
                            :language="primaryLanguage.code"
                            :primary="true"
                        />
                    </div>
                </div>
                <div class="mt-4 text-right">
                    <Button
                        :disabled="form.processing"
                        color-scheme="create"
                        class="mr-2"
                    >
                        Salva Modifiche
                    </Button>
                    <Button
                        @click="deleteExhibition"
                        color-scheme="delete"
                    >
                        Elimina
                    </Button>
                </div>
            </form>
        </PageLayout>
    </AppLayout>
</template>
