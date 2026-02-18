<script setup lang="ts">
import EmailVerificationNotificationController from '@/actions/App/Http/Controllers/Auth/EmailVerificationNotificationController';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Verifica email"
        description="Per favore verifica il tuo indirizzo email cliccando sul link che ti abbiamo appena inviato."
    >
        <Head title="Verifica email" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            Un nuovo link di verifica è stato inviato all'indirizzo email fornito durante la
            registrazione.
        </div>

        <Form
            v-bind="EmailVerificationNotificationController.store.form()"
            class="space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button
                :disabled="processing"
                variant="secondary"
            >
                <LoaderCircle
                    v-if="processing"
                    class="h-4 w-4 animate-spin"
                />
                Reinvia email di verifica
            </Button>

            <TextLink
                :href="logout()"
                as="button"
                class="mx-auto block text-sm"
            >
                Esci
            </TextLink>
        </Form>
    </AuthLayout>
</template>
