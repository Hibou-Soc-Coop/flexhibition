<script setup lang="ts">
import Button from '@/components/hibou/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import languagesRoutes from '@/routes/languages';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps<{ language: { name: string; code: string } }>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Languages',
        href: languagesRoutes.index().url,
    },
    {
        title: props.language.name,
        href: '#',
    },
];
</script>

<template>
    <Head title="Languages" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Dettagli Lingua">
            <template #button>
                <Button type="edit" colorScheme="save" @click="router.visit(languagesRoutes.edit(language.code).url)"> Modifica </Button>
            </template>
            <div class="mb-4">
                <Label for="name" class="mb-2 block">Nome Lingua</Label>
                <Input v-model="language.name" id="name" class="w-96" type="text" disabled data-1p-ignore />
            </div>
            <div class="mb-4">
                <Label for="code" class="mb-2 block">Codice (es. it)</Label>
                <Input v-model="language.code" id="code" class="w-96" type="text" maxlength="2" disabled data-1p-ignore />
            </div>
        </PageLayout>
    </AppLayout>
</template>
