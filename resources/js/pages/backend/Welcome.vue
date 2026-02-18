<script setup lang="ts">
import { dashboard } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome" />

    <div
        class="flex min-h-screen flex-col items-center justify-between bg-zinc-50 dark:bg-black p-6 text-zinc-900 dark:text-zinc-50 font-sans"
    >
        <div class="flex-1 flex flex-col items-center justify-center gap-8">
            <div class="flex flex-col items-center gap-4">
                <!-- Logo Placehoder -->
                <div
                    class="h-24 w-24 bg-zinc-200 dark:bg-zinc-800 rounded-full flex items-center justify-center"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="w-12 h-12 text-zinc-400"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z"
                        />
                    </svg>
                </div>
                <h1 class="text-4xl font-bold tracking-tight">Nome del Museo</h1>
            </div>

            <Link
                :href="dashboard()"
                class="rounded-md bg-zinc-900 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-zinc-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zinc-900 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200 transition-colors"
            >
                Vai alla Dashboard
            </Link>
        </div>

        <footer class="text-xs text-zinc-500 dark:text-zinc-400">
            Powered by Hibou Soc. Coop.
        </footer>
    </div>
</template>
