<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="E-mailadres verifiëren" />

        <div class="surface-brand-top mb-5 rounded-xl border border-app-border bg-app-panel/95 p-4 text-center shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark/95">
            <ApplicationLogo class="mx-auto h-14 w-14" />
            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.2em] text-app-muted dark:text-app-muted-dark">
                Fridtjof Nansen Groep 12
            </p>
            <h1 class="mt-3 text-xl font-semibold text-app-ink dark:text-app-ink-dark">Bevestig je e-mailadres</h1>
        </div>

        <div class="rounded-xl border border-brand-blue/20 bg-brand-blue/5 p-4 text-sm leading-6 text-app-ink dark:border-brand-blue/30 dark:bg-brand-blue/10 dark:text-app-ink-dark">
            We hebben een verificatielink gestuurd naar je e-mailadres.
            Open die e-mail en klik op de link om je account te activeren.
            Geen e-mail ontvangen? Vraag hieronder eenvoudig een nieuwe aan.
        </div>

        <div
            v-if="verificationLinkSent"
            class="mt-4 rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:border-emerald-700/70 dark:bg-emerald-900/30 dark:text-emerald-300"
        >
            Er is een nieuwe verificatiemail verstuurd.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-6 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Verstuur opnieuw
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-sm text-app-muted underline hover:text-brand-blue-dark focus:outline-none focus:ring-2 focus:ring-brand-blue/50 focus:ring-offset-2 focus:ring-offset-app-canvas dark:text-app-muted-dark dark:hover:text-app-ink-dark dark:focus:ring-offset-app-canvas-dark"
                    >Uitloggen</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
