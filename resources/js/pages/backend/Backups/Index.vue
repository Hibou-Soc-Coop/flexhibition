<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { download, restore, store } from '@/routes/backups';
import { update as updateSettings } from '@/routes/backups/settings';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Impostazioni',
        href: '#',
    },
    {
        title: 'Backup',
        href: window.location.pathname,
    },
];

type BackupEntry = {
    disk: string;
    path: string;
    name: string;
    size: number;
    last_modified: number;
    checksum_exists: boolean;
};

type BackupSettings = {
    remote_enabled: boolean;
    remote_disk: string;
    retention_days: number;
    checksum_enabled: boolean;
    schedule_enabled: boolean;
    schedule_cron: string;
};

const props = defineProps<{
    settings: BackupSettings;
    available_disks: string[];
    backups: {
        local: BackupEntry[];
        remote: BackupEntry[];
    };
}>();

const settingsForm = useForm({
    remote_enabled: props.settings.remote_enabled,
    remote_disk: props.settings.remote_disk,
    retention_days: props.settings.retention_days,
    checksum_enabled: props.settings.checksum_enabled,
    schedule_enabled: props.settings.schedule_enabled,
    schedule_cron: props.settings.schedule_cron,
});
const createForm = useForm({});
const restoreForm = useForm({
    backup_file: null as File | null,
    checksum_file: null as File | null,
});

const fileInput = ref<HTMLInputElement | null>(null);

const createBackup = () => {
    createForm.post(store().url, {
        onSuccess: () => {
            alert('Backup creato con successo.');
        },
        onError: () => {
            alert('Creazione backup fallita. Controlla i log.');
        },
    });
};

const saveSettings = () => {
    settingsForm.put(updateSettings().url, {
        preserveScroll: true,
    });
};

const handleRestore = () => {
    if (!restoreForm.backup_file) return;

    if (
        !confirm(
            'Sei sicuro di voler ripristinare? Questa operazione sovrascrivera il database e i media attuali.',
        )
    ) {
        return;
    }

    restoreForm.post(restore().url, {
        onSuccess: () => {
            restoreForm.reset();
            if (fileInput.value) fileInput.value.value = '';
            alert('Ripristino completato con successo.');
        },
        onError: (errors) => {
            console.error(errors);
            alert('Ripristino fallito. Controlla la console o i log.');
        },
    });
};

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        restoreForm.backup_file = target.files[0];
    }
};

const onChecksumChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        restoreForm.checksum_file = target.files[0];
    }
};

const downloadBackup = (disk: string, fileName: string) => {
    window.location.href = download({ disk, file: fileName }).url;
};

const formatBytes = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const index = Math.floor(Math.log(bytes) / Math.log(1024));
    return `${(bytes / Math.pow(1024, index)).toFixed(2)} ${units[index]}`;
};

const formatDate = (timestamp: number) => {
    return new Date(timestamp * 1000).toLocaleString();
};
</script>

<template>
    <Head title="Backup" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-2">
                <!-- Export Section -->
                <Card>
                    <CardHeader>
                        <CardTitle>Crea backup</CardTitle>
                        <CardDescription>
                            Crea un backup completo di database e file multimediali.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button
                            @click="createBackup"
                            :disabled="createForm.processing"
                        >
                            {{ createForm.processing ? 'Creazione...' : 'Crea backup' }}
                        </Button>
                    </CardContent>
                </Card>

                <!-- Import Section -->
                <Card>
                    <CardHeader>
                        <CardTitle>Ripristina backup</CardTitle>
                        <CardDescription>
                            Ripristina il sistema da un backup ZIP. <br />
                            <span class="text-red-500 font-bold"
                                >Attenzione: questa operazione sovrascrivera i dati esistenti!</span
                            >
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid w-full max-w-sm items-center gap-1.5">
                            <Label for="backup-file">File di backup (ZIP)</Label>
                            <Input
                                id="backup-file"
                                type="file"
                                accept=".zip"
                                @change="onFileChange"
                                ref="fileInput"
                            />
                        </div>
                        <div class="grid w-full max-w-sm items-center gap-1.5 mt-4">
                            <Label for="checksum-file">File checksum (.sha256)</Label>
                            <Input
                                id="checksum-file"
                                type="file"
                                accept=".sha256,.txt"
                                @change="onChecksumChange"
                            />
                        </div>
                        <div class="mt-4">
                            <Button
                                variant="destructive"
                                @click="handleRestore"
                                :disabled="restoreForm.processing || !restoreForm.backup_file"
                            >
                                {{
                                    restoreForm.processing ? 'Ripristino...' : 'Carica e ripristina'
                                }}
                            </Button>
                        </div>
                        <div
                            v-if="restoreForm.errors.backup_file"
                            class="text-red-500 text-sm mt-2"
                        >
                            {{ restoreForm.errors.backup_file }}
                        </div>
                        <div
                            v-if="restoreForm.errors.checksum_file"
                            class="text-red-500 text-sm mt-2"
                        >
                            {{ restoreForm.errors.checksum_file }}
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Impostazioni backup</CardTitle>
                        <CardDescription>
                            Configura retention, checksum, storage remoto e pianificazione.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            class="flex flex-col gap-4"
                            @submit.prevent="saveSettings"
                        >
                            <div class="grid gap-2">
                                <Label for="retention-days">Retention (giorni)</Label>
                                <Input
                                    id="retention-days"
                                    type="number"
                                    min="1"
                                    v-model.number="settingsForm.retention_days"
                                />
                                <div
                                    v-if="settingsForm.errors.retention_days"
                                    class="text-red-500 text-sm"
                                >
                                    {{ settingsForm.errors.retention_days }}
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <input
                                    id="checksum-enabled"
                                    type="checkbox"
                                    class="h-4 w-4"
                                    v-model="settingsForm.checksum_enabled"
                                />
                                <Label for="checksum-enabled">
                                    Richiedi checksum per il ripristino
                                </Label>
                            </div>

                            <div class="flex items-center gap-2">
                                <input
                                    id="remote-enabled"
                                    type="checkbox"
                                    class="h-4 w-4"
                                    v-model="settingsForm.remote_enabled"
                                />
                                <Label for="remote-enabled">Abilita backup remoto</Label>
                            </div>

                            <div class="grid gap-2">
                                <Label for="remote-disk">Disco remoto</Label>
                                <select
                                    id="remote-disk"
                                    class="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                    v-model="settingsForm.remote_disk"
                                    :disabled="!settingsForm.remote_enabled"
                                >
                                    <option
                                        v-for="disk in available_disks"
                                        :key="disk"
                                        :value="disk"
                                    >
                                        {{ disk }}
                                    </option>
                                </select>
                                <div
                                    v-if="settingsForm.errors.remote_disk"
                                    class="text-red-500 text-sm"
                                >
                                    {{ settingsForm.errors.remote_disk }}
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <input
                                    id="schedule-enabled"
                                    type="checkbox"
                                    class="h-4 w-4"
                                    v-model="settingsForm.schedule_enabled"
                                />
                                <Label for="schedule-enabled">Abilita backup pianificati</Label>
                            </div>

                            <div class="grid gap-2">
                                <Label for="schedule-cron">Pianificazione (cron)</Label>
                                <Input
                                    id="schedule-cron"
                                    type="text"
                                    placeholder="0 2 * * *"
                                    v-model="settingsForm.schedule_cron"
                                    :disabled="!settingsForm.schedule_enabled"
                                />
                                <div
                                    v-if="settingsForm.errors.schedule_cron"
                                    class="text-red-500 text-sm"
                                >
                                    {{ settingsForm.errors.schedule_cron }}
                                </div>
                            </div>

                            <div>
                                <Button
                                    type="submit"
                                    :disabled="settingsForm.processing"
                                >
                                    {{
                                        settingsForm.processing ? 'Salvataggio...' : (
                                            'Salva impostazioni'
                                        )
                                    }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Backup disponibili</CardTitle>
                        <CardDescription>
                            Backup recenti salvati in locale e in remoto.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col gap-4">
                            <div>
                                <div class="text-sm font-semibold">Locale</div>
                                <div
                                    v-if="backups.local.length === 0"
                                    class="text-sm text-muted-foreground"
                                >
                                    Nessun backup locale trovato.
                                </div>
                                <ul
                                    v-else
                                    class="flex flex-col gap-2 mt-2"
                                >
                                    <li
                                        v-for="backup in backups.local"
                                        :key="`local-${backup.name}`"
                                        class="flex items-center justify-between rounded-md border border-border p-3"
                                    >
                                        <div class="flex flex-col text-sm">
                                            <span class="font-medium">{{ backup.name }}</span>
                                            <span class="text-muted-foreground">
                                                {{ formatBytes(backup.size) }} ·
                                                {{ formatDate(backup.last_modified) }}
                                            </span>
                                            <span class="text-muted-foreground">
                                                Checksum:
                                                {{
                                                    backup.checksum_exists ? 'Disponibile' : (
                                                        'Mancante'
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <Button
                                            variant="secondary"
                                            @click="downloadBackup(backup.disk, backup.name)"
                                        >
                                            Scarica
                                        </Button>
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <div class="text-sm font-semibold">Remoto</div>
                                <div
                                    v-if="backups.remote.length === 0"
                                    class="text-sm text-muted-foreground"
                                >
                                    Nessun backup remoto trovato.
                                </div>
                                <ul
                                    v-else
                                    class="flex flex-col gap-2 mt-2"
                                >
                                    <li
                                        v-for="backup in backups.remote"
                                        :key="`remote-${backup.name}`"
                                        class="flex items-center justify-between rounded-md border border-border p-3"
                                    >
                                        <div class="flex flex-col text-sm">
                                            <span class="font-medium">{{ backup.name }}</span>
                                            <span class="text-muted-foreground">
                                                {{ formatBytes(backup.size) }} ·
                                                {{ formatDate(backup.last_modified) }}
                                            </span>
                                            <span class="text-muted-foreground">
                                                Checksum:
                                                {{
                                                    backup.checksum_exists ? 'Disponibile' : (
                                                        'Mancante'
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <Button
                                            variant="secondary"
                                            @click="downloadBackup(backup.disk, backup.name)"
                                        >
                                            Scarica
                                        </Button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
