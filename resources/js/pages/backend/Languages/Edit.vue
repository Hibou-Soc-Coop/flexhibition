<script setup lang="ts">
import Button from '@/components/hibou/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import languagesRoutes from '@/routes/languages';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

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

const form = useForm({
    name: props.language.name,
    code: props.language.code,
});

function submit() {
    form.processing = true;
    form.put(languagesRoutes.update(props.language.code).url, {
        onFinish: () => {
            form.processing = false;
        },
    });
}
</script>

<template>
    <Head title="Languages" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Modifica Lingua">
            <form @submit.prevent="submit">
                <div class="mb-4">
                    <Label
                        for="name"
                        class="block mb-1"
                        >Nome Lingua</Label
                    >
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="w-96"
                        data-1p-ignore
                    />
                </div>
                <div class="mb-4">
                    <Label
                        for="code"
                        class="block mb-1"
                        >Codice (es. it)</Label
                    >
                    <Input
                        id="code"
                        v-model="form.code"
                        type="text"
                        class="w-96"
                        maxlength="2"
                        data-1p-ignore
                    />
                </div>
                <Button
                    type="submit"
                    :disabled="form.processing"
                    colorScheme="save"
                >
                    {{ form.processing ? 'Salvataggio...' : 'Salva Modifiche' }}
                </Button>
            </form>
        </PageLayout>
    </AppLayout>
</template>
