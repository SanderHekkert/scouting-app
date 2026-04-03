<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center overflow-hidden bg-app-canvas pt-6 dark:bg-app-canvas-dark sm:justify-center sm:pt-0"
    >
        <div
            class="pointer-events-none absolute inset-0 opacity-40 dark:opacity-30"
            aria-hidden="true"
        >
            <div
                class="absolute -left-24 top-0 h-[120%] w-48 rotate-12 bg-brand-red/15 blur-2xl dark:bg-brand-red/25"
            />
            <div
                class="absolute -right-20 bottom-0 h-96 w-96 rounded-full bg-brand-blue/20 blur-3xl dark:bg-brand-blue/25"
            />
            <div
                class="absolute left-1/3 top-1/4 h-64 w-64 rounded-full bg-brand-green/10 blur-3xl dark:bg-brand-green/15"
            />
        </div>

        <div class="relative z-10 w-full">
            <div class="flex justify-center pt-2">
                <Link href="/" class="flex justify-center transition hover:opacity-90">
                    <ApplicationLogo class="h-24 max-h-28 max-w-[12rem]" />
                </Link>
            </div>

            <div
                class="mx-auto mt-6 w-full max-w-md overflow-hidden border border-app-border bg-app-panel px-6 py-4 shadow-lg dark:border-brand-blue/25 dark:bg-app-panel-dark sm:rounded-xl"
            >
                <div
                    class="mx-auto mb-4 h-1 w-16 rounded-full bg-gradient-to-r from-brand-red via-brand-yellow to-brand-blue"
                    aria-hidden="true"
                />
                <slot />
            </div>
        </div>
    </div>
</template>
