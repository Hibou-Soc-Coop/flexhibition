<script setup lang="ts" generic="TData">
import { ref } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { FlexRender, getCoreRowModel, getPaginationRowModel, getSortedRowModel, getFilteredRowModel, useVueTable } from '@tanstack/vue-table';
import type { ColumnDef, ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';

const props = defineProps<{
    data: TData[];
    columns: ColumnDef<TData>[];
    searchColumns?: string[];
    searchPlaceholder?: string;
    widths?: string[]; // array di classi larghezza colonna
}>();

const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({});
const pagination = ref({ pageIndex: 0, pageSize: 50 });

const table = useVueTable({
    get data() { return props.data; },
    get columns() { return props.columns; },
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: updaterOrValue => sorting.value = typeof updaterOrValue === 'function' ? updaterOrValue(sorting.value) : updaterOrValue,
    onColumnFiltersChange: updaterOrValue => columnFilters.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnFilters.value) : updaterOrValue,
    onColumnVisibilityChange: updaterOrValue => columnVisibility.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnVisibility.value) : updaterOrValue,
    onPaginationChange: updaterOrValue => pagination.value = typeof updaterOrValue === 'function' ? updaterOrValue(pagination.value) : updaterOrValue,
    state: {
        get sorting() { return sorting.value; },
        get columnFilters() { return columnFilters.value; },
        get columnVisibility() { return columnVisibility.value; },
        get pagination() { return pagination.value; },
    },
});

const search = ref('');

function onSearch(val: string | number) {
    search.value = String(val);
    table.setGlobalFilter(String(val));
}
</script>

<template>
    <div>
        <div class="flex items-center gap-2 py-4">
            <Input
                   class="max-w-sm"
                   :placeholder="props.searchPlaceholder || 'Cerca...'"
                   :model-value="search"
                   @update:model-value="onSearch" />
            <div class="flex items-center gap-2 ml-auto">
                <span class="text-sm">Righe per pagina</span>
                <Select :model-value="`${table.getState().pagination.pageSize}`" @update:model-value="val => table.setPageSize(Number(val))">
                    <SelectTrigger class="w-[70px] h-8">
                        <SelectValue :placeholder="`${table.getState().pagination.pageSize}`" />
                    </SelectTrigger>
                    <SelectContent side="top">
                        <SelectItem v-for="pageSize in [10, 20, 50, 100]" :key="pageSize" :value="`${pageSize}`">
                            {{ pageSize }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>
        <div class="border rounded-md w-full sm:max-w-[calc(100vw_-_var(--sidebar-width)_-_2rem)]">
            <Table class="w-full">
                <colgroup v-if="props.widths?.length">
                    <col v-for="(w, i) in props.widths" :key="i" :class="w" />
                </colgroup>
                <TableHeader>
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead v-for="header in headerGroup.headers" :key="header.id" class="p-2 align-middle">
                            <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <template v-if="table.getRowModel().rows?.length">
                        <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" class="p-2 align-middle">
                                <div class="min-w-0 max-w-full truncate">
                                    <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow>
                            <TableCell :colspan="props.columns.length" class="h-24 text-center">
                                Nessun risultato.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </Table>
        </div>
        <div class="flex justify-between items-center px-2 py-4">
            <div class="flex-1 text-muted-foreground text-sm">
                Pagina {{ table.getState().pagination.pageIndex + 1 }} di {{ table.getPageCount() }}
            </div>
            <div class="flex items-center space-x-2">
                <Button variant="secondary" size="sm" :disabled="!table.getCanPreviousPage()" @click="table.previousPage()">Precedente</Button>
                <Button variant="secondary" size="sm" :disabled="!table.getCanNextPage()" @click="table.nextPage()">Successiva</Button>
            </div>
        </div>
    </div>
</template>
