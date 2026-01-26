<script setup lang="ts">
import Input from '@/components/ui/input/Input.vue';
import Button from '@/components/hibou/Button.vue';
import Label from '@/components/ui/label/Label.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import postRoutes from '@/routes/posts';
import MultipleMediaUploader from '@/components/hibou/MultipleMediaUploader.vue';
import { type BreadcrumbItem } from '@/types';
import { PostData, ExhibitionData, type Language, MediaData } from '@/types/flexhibition';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import TipTap from '@/components/hibou/TipTap.vue';
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select';
import SingleMediaUpload from '@/components/hibou/SingleMediaUpload.vue';

const props = defineProps<{ post: PostData, exhibitions: ExhibitionData[] }>();

const page = usePage();
const languages = page.props.languages as Language[];
const primaryLanguage = page.props.primaryLanguage as Language;
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Posts', href: postRoutes.index().url },
    { title: 'Edit', href: '#' },
];
console.log("Post: ", props.post.audio.url[primaryLanguage.code]);
const deletePost = () => {
    if (confirm('Sei sicuro di voler eliminare questo post?')) {
        router.delete(postRoutes.destroy.url(props.post.id));
    }
};

const emptyByLanguage = Object.fromEntries(languages.map((l) => [l.code, '']));

const form = useForm({
    name: props.post.name || { ...emptyByLanguage },
    description: props.post.description ?? { ...emptyByLanguage },
    content: props.post.content ?? { ...emptyByLanguage },
    audio: null as MediaData | null,
    images: Object.values(props.post.images || {}).map(image => ({
        id: image.id,
        url: image.url,
        title: image.title,
        description: image.description,
    })) as MediaData[],
    exhibition_id: props.post.exhibition_id,
    exhibition_name: props.post.exhibition_name,
    processing: false,
});

function submit() {
    form.transform((data) => ({
        ...data,
        _method: 'PUT'
    })).post(postRoutes.update.url(props.post.id), {
        onFinish: () => {
            form.processing = false;
        },
    });
}
</script>

<template>

    <Head :title="props.post.name[primaryLanguage.code] + ' - Modifica'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Dettaglio Post">
            <form @submit.prevent="submit">
                <div class="mb-4 text-right">
                    <Button :disabled="form.processing" color-scheme="create" class="mr-2">
                        Salva Modifiche
                    </Button>
                    <Button @click="deletePost" color-scheme="delete">
                        Elimina Opera
                    </Button>
                </div>
                <div class="grid grid-cols-[1fr_4fr] grid-rows-[auto_auto] gap-4">
                    <div class="col-start-1 col-end-2 rounded-lg border p-4 shadow">
                        <Label class="block text-lg font-semibold"> Audio Opera </Label>
                        <SingleMediaUpload v-model="form.audio" multiLanguage v-if="props.post.audio.url[primaryLanguage.code]"
                            :media_preview="`/storage/${props.post.audio.url[primaryLanguage.code]}`" :is-readonly="false"
                            :accept="'audio/*'" :max-file-size="10 * 1024 * 1024" />
                        <div v-else class="mt-2 w-full rounded-md border border-gray-300 bg-gray-100">
                            <p class="p-4 text-sm text-gray-500">Nessun audio disponibile</p>
                        </div>
                    </div>
                    <div class="col-start-2 col-end-3 row-start-1 row-end-3 rounded-lg border p-4 shadow">
                        <h2 class="mb-4 text-lg font-semibold">Informazioni Opera</h2>
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
                                <Label class="mb-4 text-base font-semibold"> Nome Opera - {{ language.name }} </Label>
                                <Input class="mb-4" v-model="form.name[language.code]" />
                                <Label class="mb-4 text-base font-semibold"> Descrizione Opera - {{ language.name }} </Label>
                                <Input class="mb-4" v-model="form.description[language.code]" />
                                <div v-if="form.errors[`description.${language.code}`]"
                                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-700">
                                    {{ form.errors[`description.${language.code}`] }}
                                </div>
                                <Label class="mb-4 text-base font-semibold"> Contenuto Opera - {{ language.name }}
                                </Label>
                                <div class="mb-4">
                                    <TipTap v-model="form.content[language.code]" />
                                </div>
                                <Label class="mb-1 font-semibold">Mostra</Label>
                                <Select class="mb-4" v-model="form.exhibition_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Seleziona Opera" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="exhibition in props.exhibitions" :key="exhibition.id" :value="exhibition.id">
                                            {{
                                            exhibition.name[language.code] }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors[`exhibition_id`]"
                                    class="mb-4 rounded bg-red-100 p-2 text-sm text-red-700">
                                    {{ form.errors[`exhibition_id`] }}
                                </div>
                            </TabsContent>
                        </Tabs>
                    </div>
                    <div class="col-span-2 rounded-lg border p-4 shadow">
                        <Label class="mb-4 text-lg font-semibold"> Immagini del Opera</Label>
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
                    <Button @click="deletePost" color-scheme="delete">
                        Elimina Opera
                    </Button>
                </div>
            </form>
        </PageLayout>
    </AppLayout>
</template>
