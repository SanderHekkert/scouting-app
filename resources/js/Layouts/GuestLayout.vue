<script setup>
import AppShellBackground from '@/Components/AppShellBackground.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AuthShellCard from '@/Components/AuthShellCard.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <AppShellBackground>
        <div
            class="flex min-h-screen flex-col items-center justify-center px-4 py-10 pt-6 sm:px-6 sm:pt-0"
        >
            <div class="mb-6 w-full max-w-md">
                <div class="flex justify-center">
                    <Link href="/" class="flex flex-col items-center gap-3 transition-opacity hover:opacity-90">
                        <ApplicationLogo class="h-24 max-h-28 max-w-[12rem] drop-shadow-lg" />
                        <span class="text-center text-xs font-medium uppercase tracking-[0.2em] text-white/90">
                            Fridtjof Nansen Groep 12
                        </span>
                    </Link>
                </div>
            </div>

            <AuthShellCard content-class="px-6 py-8 sm:px-8 sm:py-8" class="mx-auto w-full max-w-md">
                <slot />
            </AuthShellCard>
        </div>
    </AppShellBackground>
</template>
