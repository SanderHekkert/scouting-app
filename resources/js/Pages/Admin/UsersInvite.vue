<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';

import { useSaveRedirect } from '@/utils/saveForm';

const page = usePage();
const { applySaveRedirect, saveFormOptions } = useSaveRedirect();

const form = useForm({
    email: '',
});

function submit() {
    form
        .transform((data) => applySaveRedirect(data))
        .post(route('admin.users.invite'), saveFormOptions({
        onSuccess: () => {
            form.reset();
        },
    }));
}
</script>

<template>
    <Head title="Gebruiker uitnodigen" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Gebruiker uitnodigen</h2>
                <Link
                    :href="route('admin.users.index')"
                    class="btn-action-back"
                    title="Terug"
                    aria-label="Terug"
                >
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <div class="surface-brand-top mx-auto max-w-2xl rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
            <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Uitnodiging via e-mail</h3>
            <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                De gebruiker ontvangt een link, vult zelf gegevens in en moet daarna e-mail verifiëren.
            </p>

            <p v-if="page.props.flash?.status" class="text-sm text-emerald-700 dark:text-emerald-300">{{ page.props.flash.status }}</p>

            <form class="mt-4 space-y-3" @submit.prevent="submit">
                <div>
                    <label for="invite-email" class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">E-mailadres</label>
                    <input
                        id="invite-email"
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="naam@voorbeeld.nl"
                        class="mt-1 w-full rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    >
                    <p v-if="form.errors.email" class="mt-2 text-xs text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
                </div>

                <div>
                    <button
                        type="submit"
                        class="rounded bg-brand-blue px-4 py-2 text-sm font-semibold text-white hover:bg-brand-blue-dark disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        Uitnodiging versturen
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
