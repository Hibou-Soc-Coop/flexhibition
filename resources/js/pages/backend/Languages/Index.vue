<script setup lang="ts">
import Button from '@/components/hibou/Button.vue';
import DataTable from '@/components/hibou/DataTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import PageLayout from '@/layouts/PageLayout.vue';
import languagesRoutes from '@/routes/languages';
import { type BreadcrumbItem } from '@/types';
import { Language } from '@/types/flexhibition';
import { Head } from '@inertiajs/vue3';
import { ColumnDef } from '@tanstack/vue-table';
import { ArrowUpDown } from 'lucide-vue-next';
import { h } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Languages',
        href: '#',
    },
];

const props = defineProps<{ languages: Language[] }>();

function sortableHeader(label: string) {
    return ({ column }: any) =>
        h(
            'button',
            {
                class: 'flex items-center gap-1 font-semibold',
                onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
            },
            [label, h(ArrowUpDown, { class: 'ml-1 w-4 h-4 opacity-60' })],
        );
}

const columns: ColumnDef<Language>[] = [
    {
        accessorKey: 'name',
        header: sortableHeader('Name'),
        cell: ({ row }) => h('span', row.getValue('name')),
    },
    {
        accessorKey: 'code',
        header: sortableHeader('Code'),
        cell: ({ row }) => h('span', row.getValue('code')),
    },
    {
        id: 'actions',
        header: 'Azioni',
        enableSorting: false,
        cell: ({ row }) =>
            h('div', { class: 'flex gap-2' }, [
                h(
                    Button,
                    {
                        as: 'a',
                        href: languagesRoutes.show(row.original.id).url,
                        size: 'sm',
                        class: 'px-2',
                    },
                    () => 'Dettagli',
                ),
                h(
                    Button,
                    {
                        as: 'a',
                        href: languagesRoutes.edit(row.original.id).url,
                        colorScheme: 'edit',
                        size: 'sm',
                        class: 'px-2',
                    },
                    () => 'Modifica',
                ),
                h(
                    Button,
                    {
                        as: 'button',
                        type: 'button',
                        colorScheme: 'delete',
                        size: 'sm',
                        class: 'px-2',
                        onClick: () => {},
                    },
                    () => 'Elimina',
                ),
            ]),
    },
];
</script>

<template>
    <Head title="Languages" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <PageLayout title="Lingue">
            <template #button>
                <Button :href="languagesRoutes.create().url" color-scheme="create" size="sm"> Crea nuova lingua </Button>
            </template>
            <DataTable :data="props.languages" :columns="columns" search-placeholder="Cerca" :widths="['', '', 'w-[1%]']" />
        </PageLayout>
    </AppLayout>
</template>
