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
import postsRoutes from '@/routes/posts';
import { type BreadcrumbItem } from '@/types';
import {
    ExhibitionData,
    ExhibitionMinimalData,
    Language,
    MediaData,
    MediaDataLocalized,
} from '@/types/flexhibition';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { computed, ref } from 'vue';

const page = usePage();
const languages = page.props.languages as Language[];
const primaryLanguage = page.props.primaryLanguage as Language | null;
const primaryLanguageCode = primaryLanguage?.code || 'it';
const currentLangCode = ref<string>(primaryLanguageCode);
const currentLangName = computed(() => {
    const lang = languages.find((l) => l.code === currentLangCode.value);
    return lang ? lang.name : currentLangCode.value;
});

const props = defineProps<{ exhibitions: ExhibitionData[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Posts',
        href: postsRoutes.index().url,
    },
    {
        title: 'Create',
        href: postsRoutes.create().url,
    },
];

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
    name: { ...emptyContentByLanguage },
    description: { ...emptyContentByLanguage },
    content: { ...emptyContentByLanguage },
    audio: { ...emptyMediaDataLocalized },
    images: [] as MediaData[],
    exhibition: null as ExhibitionMinimalData | null,
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
                        <SingleMediaUploadLocalized
                            v-model="form.audio"
                            :is-readonly="false"
                            :mimetype="'audio/*'"
                            :max-file-size="10 * 1024 * 1024"
                        />
                    </div>
                    <div
                        class="col-start-2 col-end-3 row-start-1 row-end-3 rounded-lg border p-4 shadow"
                    >
                        <h2 class="mb-4 text-lg font-semibold">Informazioni Opera</h2>
                        <Tabs
                            default-value="it"
                            :unmount-on-hide="false"
                            class="grid w-full grid-cols-[15%_auto] gap-8"
                            orientation="vertical"
                        >
                            <TabsList class="grid h-fit w-full grid-cols-1 gap-2">
                                <TabsTrigger
                                    v-for="language in languages"
                                    :key="language.code"
                                    :value="language.code"
                                >
                                    {{ language.name }}
                                </TabsTrigger>
                            </TabsList>
                            <TabsContent
                                class="mt-1"
                                v-for="language in languages"
                                :key="language.code"
                                :value="language.code"
                            >
                                <Label class="mb-1 font-semibold">Nome</Label>
                                <Input
                                    class="mb-4"
                                    v-model="form.name[language.code]"
                                />
                                <Label class="mb-1 font-semibold">descrizione</Label>
                                <Input
                                    class="mb-4"
                                    v-model="form.description[language.code]"
                                />
                                <div
                                    v-if="form.errors[`name.${language.code}`]"
                                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-700"
                                >
                                    {{ form.errors[`name.${language.code}`] }}
                                </div>
                                <Label class="mb-1 font-semibold"
                                    >Contenuto {{ language.name }}</Label
                                >
                                <div class="mb-4">
                                    <QuillEditor
                                        v-model:content="form.content[language.code]"
                                        content-type="html"
                                    />
                                </div>
                                <Label class="mb-1 font-semibold">Exhibition</Label>
                                <Select
                                    class="mb-4"
                                    v-model="form.exhibition?.id"
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleziona Collezione" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="exhibition in props.exhibitions"
                                            :key="exhibition.id"
                                            :value="exhibition.id"
                                            >{{ exhibition.name[language.code] }}</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                            </TabsContent>
                        </Tabs>
                    </div>
                    <div class="col-span-2 rounded-lg border p-4 shadow">
                        <Label class="mb-4 text-lg font-semibold"> Immagini della Opera </Label>
                        <MultipleMediaUploader
                            v-model="form.images"
                            :is-readonly="false"
                            :show-caption="false"
                            :primary="true"
                        />
                    </div>
                </div>
                <div class="mt-4">
                    <Button
                        type="submit"
                        :disabled="form.processing"
                        >Crea Opera</Button
                    >
                </div>
            </form>
        </PageLayout>
    </AppLayout>
</template>
