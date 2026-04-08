<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AppShellBackground from '@/Components/AppShellBackground.vue';
import { Head, router } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Geen toegang" />
    <AppShellBackground>
        <div class="min-h-screen px-6 py-10">
            <div class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-2xl items-center justify-center">
                <div class="surface-brand-top-2xl w-full rounded-2xl border border-white/20 bg-white/95 p-8 text-center shadow-2xl backdrop-blur dark:border-brand-blue/35 dark:bg-app-panel-dark/95">
                    <ApplicationLogo class="mx-auto h-20 w-20" />
                    <p class="mt-3 text-xs font-semibold uppercase tracking-[0.22em] text-brand-blue-dark dark:text-brand-blue-light">Fridtjof Nansen Groep 12</p>

                    <h1 class="mt-4 text-3xl font-bold text-slate-900 dark:text-app-ink-dark">Nog geen toegang</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-700 dark:text-app-muted-dark">
                        Je account is aangemaakt, maar er is nog geen rol gekoppeld.
                        Neem contact op met een beheerder of bestuurslid zodat je toegang krijgt tot de juiste onderdelen.
                    </p>

                    <div class="mt-6 rounded-xl border border-brand-blue/20 bg-brand-blue/5 px-4 py-3 text-left text-sm text-slate-700 dark:border-brand-blue/35 dark:bg-brand-blue/10 dark:text-app-muted-dark">
                        <p class="font-semibold text-brand-blue-dark dark:text-brand-blue-light">Wat kun je nu doen?</p>
                        <p class="mt-1">Controleer met het bestuur of je bent toegevoegd aan de juiste speltak en rol.</p>
                    </div>

                    <button
                        type="button"
                        class="mt-7 inline-flex items-center rounded-lg bg-brand-blue px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-blue-dark"
                        @click="router.post(route('logout'))"
                    >
                        Uitloggen
                    </button>
                </div>
            </div>
        </div>
    </AppShellBackground>
</template>
