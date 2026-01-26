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
import TipTap from '@/components/hibou/TipTap.vue';
import MultipleMediaUploader from '@/components/hibou/MultipleMediaUploader.vue';

const props = defineProps<{ museum: MuseumData }>();

const page = usePage();
const languages = page.props.languages as Language[];
const primaryLanguage = page.props.primaryLanguage as Language;
console.log("MuseumData:", props.museum);
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
    logo: props.museum.logo as MediaData ?? null,
    audio: null as MediaData | null,
    images: Object.values(props.museum.images || {}).map(image => ({
        id: image.id,
        url: image.url,
        title: image.title,
        description: image.description,
    })) as MediaData[],
    processing: false,
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
        <PageLayout title="Dettaglio Museo">
            <form @submit.prevent="submit">
                <div class="mb-4 text-right">
                    <Button :disabled="form.processing" color-scheme="create" class="mr-2">
                        Salva Modifiche
                    </Button>
                    <Button @click="deleteMuseum" color-scheme="delete">
                        Elimina Museo
                    </Button>
                </div>
                <div class="grid grid-cols-[1fr_4fr] grid-rows-[auto_auto] gap-4">
                    <div class="rounded-lg border p-4 shadow">
                        <Label class="mb-4 text-lg font-semibold"> Logo Museo </Label>
                        <!-- {{ form.logo.url[primaryLanguage.code] }} -->
                        <div class="overflow-hidden rounded-md border border-gray-300">
                            <SingleMediaUpload v-if="props.museum.logo.url[primaryLanguage.code]"
                                :media_preview="`/storage/${props.museum.logo.url[primaryLanguage.code]}`" v-model="form.logo"
                                :is-readonly="false" :accept="'image/*'" :max-file-size="5 * 1024 * 1024" />
                           <div v-else class="mt-2 w-full rounded-md border border-gray-300 bg-gray-100">
                            <p class="p-4 text-sm text-gray-500">Nessun logo disponibile</p>
                        </div>
                        </div>
                    </div>
                    <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow">
                        <Label class="block text-lg font-semibold"> Audio Museo </Label>
                        <SingleMediaUpload v-model="form.audio" v-if="props.museum.audio?.url[primaryLanguage.code]"
                            :media_preview="`/storage/${props.museum.audio.url[primaryLanguage.code] || ''}`" :is-readonly="false"
                            :accept="'audio/*'" :max-file-size="10 * 1024 * 1024" />
                        <div v-else class="mt-2 w-full rounded-md border border-gray-300 bg-gray-100">
                            <p class="p-4 text-sm text-gray-500">Nessun audio disponibile</p>
                        </div>
                    </div>
                    <div class="col-start-2 col-end-3 row-start-1 row-end-3 rounded-lg border p-4 shadow">
                        <h2 class="mb-4 text-lg font-semibold">Informazioni Museo</h2>
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
                                <Label class="mb-4 text-base font-semibold"> Nome Museo - {{ language.name }} </Label>
                                <Input class="mb-4" v-model="form.name[language.code]" />
                                <div v-if="form.errors[`name.${language.code}`]"
                                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-700">
                                    {{ form.errors[`name.${language.code}`] }}
                                </div>
                                <Label class="mb-4 text-base font-semibold"> Descrizione Museo - {{ language.name }}
                                </Label>
                                <div class="mb-4">
                                    <TipTap v-model="form.description[language.code]" />
                                </div>
                            </TabsContent>
                        </Tabs>
                    </div>
                    <div class="col-span-2 rounded-lg border p-4 shadow">
                        <Label class="mb-4 text-lg font-semibold"> Immagini del Museo</Label>
                        <div class="col-span-2 rounded-lg border p-4 shadow">
                            <Label class="mb-4 text-lg font-semibold"> Immagini della Collezione </Label>
                            <MultipleMediaUploader v-model="form.images" :is-readonly="false" :show-caption="false"
                                :language="primaryLanguage.code" :primary="true" />
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-right">
                    <Button :disabled="form.processing" color-scheme="create" class="mr-2">
                        Salva Modifiche
                    </Button>
                    <Button @click="deleteMuseum" color-scheme="delete">
                        Elimina Museo
                    </Button>
                </div>
            </form>
        </PageLayout>
    </AppLayout>
</template>
