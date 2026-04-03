<script setup>
import { Link } from '@inertiajs/vue3';

function tabClass(active) {
    return [
        'rounded-t-lg px-3 py-2 text-sm font-semibold transition sm:px-4',
        active
            ? 'bg-brand-blue/15 text-brand-blue-dark dark:text-app-ink-dark'
            : 'text-app-muted hover:bg-brand-blue/10 hover:text-app-ink dark:text-app-muted-dark dark:hover:text-app-ink-dark',
    ];
}
</script>

<template>
    <div class="mb-3 flex flex-wrap gap-1 border-b border-brand-blue/35" role="tablist" aria-label="Dolfijnen onderdelen">
        <Link
            role="tab"
            :href="route('members.index')"
            preserve-scroll
            :aria-selected="route().current('members.index')"
            :class="tabClass(route().current('members.index'))"
        >
            Dolfijnen
        </Link>
        <Link
            role="tab"
            :href="route('members.bijzonderheden')"
            preserve-scroll
            :aria-selected="route().current('members.bijzonderheden')"
            :class="tabClass(route().current('members.bijzonderheden'))"
        >
            Bijzonderheden
        </Link>
        <Link
            role="tab"
            :href="route('tipper-topper-opkomst.index')"
            preserve-scroll
            :aria-selected="route().current('tipper-topper-opkomst.index')"
            :class="tabClass(route().current('tipper-topper-opkomst.index'))"
        >
            Tipper- & Topper opkomst
        </Link>
        <Link
            role="tab"
            :href="route('pods.index')"
            preserve-scroll
            :aria-selected="route().current('pods.index')"
            :class="tabClass(route().current('pods.index'))"
        >
            Vinindeling
        </Link>
    </div>
</template>
