<script setup lang="ts">
import Button from '@/components/hibou/Button.vue';
import Card from '@/components/hibou/Card.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import postsRoutes from '@/routes/posts';
import { type BreadcrumbItem } from '@/types';
import { type Language, PostData } from '@/types/flexhibition';
import { Head, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const languages = page.props.languages as Language[];
const primaryLanguage = page.props.primaryLanguage as Language | null;
const primaryLanguageCode = primaryLanguage?.code || 'it';

const props = defineProps<{ posts: PostData[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Opere',
        href: '#',
    },
];

function truncate(text: string | undefined, maxLength: number): string {
    if (!text) return '-';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}
</script>

<template>
    <Head title="Opere" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 py-8 container">
            <div class="flex items-center mb-4">
                <h1 class="font-bold text-3xl">Opere</h1>
                <Button
                    @click="router.visit(postsRoutes.create().url)"
                    colorScheme="create"
                    class="ml-6 h-8"
                    >Nuova Opera</Button
                >
            </div>
            <div class="gap-6 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <Card
                    v-for="post in props.posts"
                    :key="post.id"
                    :route="postsRoutes"
                    :id="post.id"
                    :title="post.name[primaryLanguageCode]"
                    :excerpt="truncate(post.description?.[primaryLanguageCode], 60)"
                    :thumbnail="
                        post.images?.[0]?.url ?? '/storage/sample-data/images/placeholder.jpg'
                    "
                ></Card>
                <div
                    v-if="props.posts.length === 0"
                    class="col-span-full py-8 text-muted-foreground text-center"
                >
                    Nessuna opera trovata.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
