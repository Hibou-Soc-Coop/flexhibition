<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { restore, store } from '@/routes/backups';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: '#',
    },
    {
        title: 'Backups',
        href: window.location.pathname,
    },
];

const createForm = useForm({});
const restoreForm = useForm({
    backup_file: null as File | null,
});

const fileInput = ref<HTMLInputElement | null>(null);

const createBackup = () => {
    // We can't use Inertia for file download efficiently if we want to stay on page or handle stream easily without custom handling
    // taking a simpler approach: Submit form via Inertia
    // But Inertia doesn't support file downloads directly
    // Actually, simple window.location.href or a generated form submit is better for download.
    // However, the controller returns response()->download() which works with Inertia if we use window.open or similar
    // BUT, standard way:
    window.location.href = store().url;
    // or if it needs POST (which it does):
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = store().url;

    // Add CSRF token
    const token = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
    if (token) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = token;
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const handleRestore = () => {
    if (!restoreForm.backup_file) return;

    if (
        !confirm(
            'Are you sure you want to restore? This will overwrite the current database and media files.',
        )
    ) {
        return;
    }

    restoreForm.post(restore().url, {
        onSuccess: () => {
            restoreForm.reset();
            if (fileInput.value) fileInput.value.value = '';
            alert('Restore completed successfully.');
        },
        onError: (errors) => {
            console.error(errors);
            alert('Restore failed. Check console/logs.');
        },
    });
};

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        restoreForm.backup_file = target.files[0];
    }
};
</script>

<template>
    <Head title="Backups" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-2">
                <!-- Export Section -->
                <Card>
                    <CardHeader>
                        <CardTitle>Export Backup</CardTitle>
                        <CardDescription>
                            Create a full backup of the database and media files. The file will be
                            downloaded as a ZIP archive.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button @click="createBackup"> Create & Download Backup </Button>
                    </CardContent>
                </Card>

                <!-- Import Section -->
                <Card>
                    <CardHeader>
                        <CardTitle>Restore Backup</CardTitle>
                        <CardDescription>
                            Restore the system from a ZIP backup. <br />
                            <span class="text-red-500 font-bold"
                                >Warning: This will overwrite existing data!</span
                            >
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid w-full max-w-sm items-center gap-1.5">
                            <Label for="backup-file">Backup File (ZIP)</Label>
                            <Input
                                id="backup-file"
                                type="file"
                                accept=".zip"
                                @change="onFileChange"
                                ref="fileInput"
                            />
                        </div>
                        <div class="mt-4">
                            <Button
                                variant="destructive"
                                @click="handleRestore"
                                :disabled="restoreForm.processing || !restoreForm.backup_file"
                            >
                                {{ restoreForm.processing ? 'Restoring...' : 'Upload & Restore' }}
                            </Button>
                        </div>
                        <div
                            v-if="restoreForm.errors.backup_file"
                            class="text-red-500 text-sm mt-2"
                        >
                            {{ restoreForm.errors.backup_file }}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
