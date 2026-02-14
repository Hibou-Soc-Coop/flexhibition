<script setup lang="ts">
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/hibou/Button.vue';
import Label from '@/components/ui/label/Label.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import exhibitionRoutes from '@/routes/exhibitions';
import MultipleMediaUploader from '@/components/hibou/MultipleMediaUploader.vue';
import { type BreadcrumbItem } from '@/types';
import { ExhibitionData, MuseumData, type Language, MediaData } from '@/types/flexhibition';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import TipTap from '@/components/hibou/TipTap.vue';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select';
import SingleMediaUpload from '@/components/hibou/SingleMediaUpload.vue';

const props = defineProps<{ exhibition: ExhibitionData, museums: MuseumData[] }>();

const page = usePage();
const languages = page.props.languages as Language[];
console.log("museums:", props.museums);
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

const emptyByLanguage = Object.fromEntries(languages.map((l) => [l.code, '']));

const form = useForm({
    name: props.exhibition.name || { ...emptyByLanguage },
    description: props.exhibition.description ?? { ...emptyByLanguage },
    start_date: props.exhibition.start_date ?? '' as string,
    end_date: props.exhibition.end_date ?? '' as string,
    audio: null as MediaData | null,
    images: Object.values(props.exhibition.images || {}).map(image => ({
        id: image.id,
        url: image.url,
        title: image.title,
        description: image.description,
    })) as MediaData[],
    is_archived: props.exhibition.is_archived ?? false,
    museum_id: props.exhibition.museum_id,
    museum_name: props.exhibition.museum_name,
    processing: false,
});

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'PUT'
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
                    <Button :disabled="form.processing" color-scheme="create" class="mr-2">
                        Salva Modifiche
                    </Button>
                    <Button @click="deleteExhibition" color-scheme="delete">
                        Elimina Mostra
                    </Button>
                </div>
                <div class="grid grid-cols-[1fr_4fr] grid-rows-[auto_auto] gap-4">
                    <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow">
                        <Label class="block text-lg font-semibold"> Audio Mostra </Label>
                        <SingleMediaUpload v-model="form.audio" v-if="props.exhibition.audio"
                                           :media_preview="`/storage/${props.exhibition.audio?.url[primaryLanguage.code]}`" :is-readonly="false"
                                           :mimetype="'audio/*'" :max-file-size="10 * 1024 * 1024" />
                        <div v-else class="mt-2 w-full rounded-md border border-gray-300 bg-gray-100">
                            <p class="p-4 text-sm text-gray-500">Nessun audio disponibile</p>
                        </div>
                    </div>
                    <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow">
                        <Label class="block text-lg font-semibold"> Data d'inizio </Label>
                        <Input class="mb-4" v-model="form.start_date" type="date" />
                        <Label class="block text-lg font-semibold"> Data di fine </Label>
                        <Input class="mb-4" v-model="form.end_date" type="date" />
                    </div>
                    <div class="col-start-2 col-end-3 row-start-1 row-end-3 rounded-lg border p-4 shadow">
                        <h2 class="mb-4 text-lg font-semibold">Informazioni Mostra</h2>
                        <Tabs default-value="it" :unmount-on-hide="false" class="grid w-full grid-cols-[15%_auto] gap-8"
                              orientation="vertical">
                            <TabsList class="grid h-fit w-full grid-cols-1 gap-2">
                                <template v-for="language in languages" :key="language.code">
                                    <TabsTrigger :value="language.code">
                                        {{ language.name }}
                                    </TabsTrigger>
                                </template>
                            </TabsList>
                            <TabsContent v-for="language in languages" :key="language.code" :value="language.code">
                                <Label class="mb-4 text-base font-semibold"> Nome Mostra - {{ language.name }} </Label>
                                <Input class="mb-4" v-model="form.name[language.code]" />
                                <div v-if="form.errors[`name.${language.code}`]" class="mb-4 rounded bg-red-100 p-2 text-sm text-red-700">
                                    {{ form.errors[`name.${language.code}`] }}
                                </div>
                                <Label class="mb-4 text-base font-semibold"> Descrizione Mostra - {{ language.name }}
                                </Label>
                                <div class="mb-4">
                                    <TipTap v-model="form.description[language.code]" />
                                </div>
                                <div class="mb-4 hidden">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" v-model="form.is_archived" class="form-checkbox" />
                                        <span class="ml-2">Archiviata</span>
                                    </label>
                                </div>
                                <Label class="mb-1 font-semibold">Museum</Label>
                                <Select class="mb-4" v-model="form.museum_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleziona museo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="museum in props.museums" :key="museum.id" :value="museum.id">
                                            {{
                                                museum.name[language.code] }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </TabsContent>
                        </Tabs>
                    </div>
                    <div class="col-span-2 rounded-lg border p-4 shadow">
                        <Label class="mb-4 text-lg font-semibold"> Immagini del Mostra</Label>
                        <div class="col-span-2 rounded-lg border p-4 shadow">
                            <Label class="mb-4 text-lg font-semibold"> Immagini della Collezione </Label>
                            <MultipleMediaUploader v-model="form.images" :is-readonly="false" :show-caption="false" :language="primaryLanguage.code" :primary="true" />
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-right">
                    <Button :disabled="form.processing" color-scheme="create" class="mr-2">
                        Salva Modifiche
                    </Button>
                    <Button @click="deleteExhibition" color-scheme="delete">
                        Elimina Mostra
                    </Button>
                </div>
            </form>
        </PageLayout>
    </AppLayout>
</template>
