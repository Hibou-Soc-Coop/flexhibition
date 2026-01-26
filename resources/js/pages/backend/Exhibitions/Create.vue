<script setup lang="ts">
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import exhibitionsRoutes from '@/routes/exhibitions';
import { type BreadcrumbItem } from '@/types';
import { type Language, MediaData, MuseumData } from '@/types/flexhibition';
import { Head, useForm, usePage } from '@inertiajs/vue3';

import MultipleMediaUploader from '@/components/hibou/MultipleMediaUploader.vue';
import SingleMediaUpload from '@/components/hibou/SingleMediaUpload.vue';
import TipTap from '@/components/hibou/TipTap.vue';
import Button from '@/components/hibou/Button.vue';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select';

const page = usePage();
const languages = page.props.languages as Language[];
const primaryLanguage = page.props.primaryLanguage as Language | null;
const primaryLanguageCode = primaryLanguage?.code || 'it';

const props = defineProps<{ museums: MuseumData[] }>();
console.log("museums:", props.museums);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Exhibitions',
        href: '#',
    },
    {
        title: 'Create',
        href: exhibitionsRoutes.create().url,
    },
];

// Inizializziamo gli oggetti multilingua usando i codici reali (language.code)
const emptyByLanguage = Object.fromEntries(languages.map((l) => [l.code, '']));

const form = useForm({
    name: { ...emptyByLanguage },
    caption: { ...emptyByLanguage },
    description: { ...emptyByLanguage },
    start_date: '' as string,
    end_date: '' as string,
    audio: null as MediaData | null,
    images: [] as MediaData[],
    museum_id: null as number | null,
    processing: false,
});

function submit() {
    form.processing = true;
    form.post(exhibitionsRoutes.store().url, {
        onFinish: () => {
            form.processing = false;
        },
    });
}
</script>

<template>

    <Head title="Exhibitions" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Aggiungi Collezione">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-[1fr_4fr] grid-rows-[auto_auto] gap-4">
                    <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow">
                        <Label class="block text-lg font-semibold"> Audio Collezione </Label>
                        <SingleMediaUpload v-model="form.audio" :is-readonly="false" :accept="'audio/*'"
                            :max-file-size="10 * 1024 * 1024" />
                    </div>
                    <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow">
                        <Label class="block text-lg font-semibold"> Data d'inizio </Label>
                        <Input class="mb-4" v-model="form.start_date" type="date" />
                        <Label class="block text-lg font-semibold"> Data di fine </Label>
                        <Input class="mb-4" v-model="form.end_date" type="date" />
                    </div>
                    <div class="col-start-2 col-end-3 row-start-1 row-end-3 rounded-lg border p-4 shadow">
                        <h2 class="mb-4 text-lg font-semibold">Informazioni Collezione</h2>
                        <Tabs default-value="it" :unmount-on-hide="false" class="grid w-full grid-cols-[15%_auto] gap-8"
                            orientation="vertical">
                            <TabsList class="grid h-fit w-full grid-cols-1 gap-2">
                                <TabsTrigger v-for="language in languages" :key="language.code" :value="language.code">
                                    {{ language.name }}
                                </TabsTrigger>
                            </TabsList>
                            <TabsContent class="mt-1" v-for="language in languages" :key="language.code"
                                :value="language.code">
                                <Label class="mb-1 font-semibold">Nome</Label>
                                <Input class="mb-4" v-model="form.name[language.code]" />
                                <div v-if="form.errors[`name.${language.code}`]"
                                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-700">
                                    {{ form.errors[`name.${language.code}`] }}
                                </div>
                                <Label class="mb-1 font-semibold">Descrizione {{ language.name }}</Label>
                                <div class="mb-4">
                                    <TipTap v-model="form.description[language.code]" />
                                </div>
                                <Label class="mb-1 font-semibold">Museum</Label>
                                <Select class="mb-4" v-model="form.museum_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleziona museo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="museum in props.museums" :key="museum.id" :value="museum.id">
                                            {{ museum.name[language.code] }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </TabsContent>
                        </Tabs>
                    </div>
                    <div class="col-span-2 rounded-lg border p-4 shadow">
                        <Label class="mb-4 text-lg font-semibold"> Immagini della Collezione </Label>
                        <MultipleMediaUploader v-model="form.images" :is-readonly="false" :show-caption="false"
                            :primary="true" />
                    </div>
                </div>
                <div class="mt-4">
                    <Button type="submit" :disabled="form.processing">Crea Collezione</Button>
                </div>
            </form>
        </PageLayout>
    </AppLayout>
</template>
