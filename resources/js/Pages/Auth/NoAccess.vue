<script setup>
import { Head, router } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Geen toegang" />
    <div class="flex min-h-screen items-center justify-center bg-brand-blue px-6 text-white">
        <div class="max-w-xl text-center">
            <h1 class="text-3xl font-bold">Geen toegang</h1>
            <p class="mt-3 text-sm text-white/90">
                Je account heeft nog geen rol. Neem contact op met een admin om toegang te krijgen.
            </p>
            <button
                type="button"
                class="mt-6 rounded bg-white px-4 py-2 text-sm font-semibold text-brand-blue hover:bg-white/90"
                @click="router.post(route('logout'))"
            >
                Uitloggen
            </button>
        </div>
    </div>
</template>
