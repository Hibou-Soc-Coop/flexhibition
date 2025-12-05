<script setup lang="ts">
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import postsRoutes from '@/routes/posts';
import { type BreadcrumbItem } from '@/types';
import { type Language, MediaData, ExhibitionData } from '@/types/flexhibition';
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

const props = defineProps<{ exhibitions: ExhibitionData[] }>();
console.log("exhibitions:", props.exhibitions);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Posts',
        href: '#',
    },
    {
        title: 'Create',
        href: postsRoutes.create().url,
    },
];

// Inizializziamo gli oggetti multilingua usando i codici reali (language.code)
const emptyByLanguage = Object.fromEntries(languages.map((l) => [l.code, '']));

const form = useForm({
    name: { ...emptyByLanguage },
    description: { ...emptyByLanguage },
    content: { ...emptyByLanguage },
    audio: null as MediaData | null,
    images: [] as MediaData[],
    exhibition_id: null as number | null,
    processing: false,
});

function submit() {
    form.processing = true;
    form.post(postsRoutes.store().url, {
        onFinish: () => {
            form.processing = false;
        },
    });
}
</script>

<template>
    <Head title="Posts" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Aggiungi Opera">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-[1fr_4fr] grid-rows-[auto_auto] gap-4">
                    <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow">
                        <Label class="block text-lg font-semibold"> Audio Opera </Label>
                        <SingleMediaUpload v-model="form.audio" :is-readonly="false" :accept="'audio/*'" :max-file-size="10 * 1024 * 1024" />
                    </div>
                    <div class="col-start-2 col-end-3 row-start-1 row-end-3 rounded-lg border p-4 shadow">
                        <h2 class="mb-4 text-lg font-semibold">Informazioni Opera</h2>
                        <Tabs default-value="it" :unmount-on-hide="false" class="grid w-full grid-cols-[15%_auto] gap-8" orientation="vertical">
                            <TabsList class="grid h-fit w-full grid-cols-1 gap-2">
                                <TabsTrigger v-for="language in languages" :key="language.code" :value="language.code">
                                    {{ language.name }}
                                </TabsTrigger>
                            </TabsList>
                            <TabsContent class="mt-1" v-for="language in languages" :key="language.code" :value="language.code">
                                <Label class="mb-1 font-semibold">Nome</Label>
                                <Input class="mb-4" v-model="form.name[language.code]" />
                                <Label class="mb-1 font-semibold">descrizione</Label>
                                <Input class="mb-4" v-model="form.description[language.code]" />
                                <div v-if="form.errors[`name.${language.code}`]" class="mb-4 rounded bg-red-100 p-2 text-sm text-red-700">
                                    {{ form.errors[`name.${language.code}`] }}
                                </div>
                                <Label class="mb-1 font-semibold">Contenuto {{ language.name }}</Label>
                                <div class="mb-4">
                                    <TipTap v-model="form.content[language.code]" />
                                </div>
                                <Label class="mb-1 font-semibold">Exhibition</Label>
                                <Select class="mb-4" v-model="form.exhibition_id">
                                    <SelectTrigger>
                                            <SelectValue placeholder="Seleziona Collezione" />
                                        </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="exhibition in props.exhibitions" :key="exhibition.id" :value="exhibition.id">{{ exhibition.name[language.code] }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </TabsContent>
                        </Tabs>
                    </div>
                    <div class="col-span-2 rounded-lg border p-4 shadow">
                        <Label class="mb-4 text-lg font-semibold"> Immagini della Opera </Label>
                        <MultipleMediaUploader v-model="form.images" :is-readonly="false" :show-caption="false" :primary="true" />
                    </div>
                </div>
                <div class="mt-4">
                    <Button type="submit" :disabled="form.processing">Crea Opera</Button>
                </div>
            </form>
        </PageLayout>
    </AppLayout>
</template>
