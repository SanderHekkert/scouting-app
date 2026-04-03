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

const isAgendaTab = () => route().current('events.*');
const isJaarThemaTab = () => route().current('jaar-thema');
</script>

<template>
    <div class="mb-3 flex flex-wrap gap-1 border-b border-brand-blue/35" role="tablist" aria-label="Agenda onderdelen">
        <Link
            role="tab"
            :href="route('events.index')"
            preserve-scroll
            :aria-selected="isAgendaTab()"
            :class="tabClass(isAgendaTab())"
        >
            Agenda
        </Link>
        <Link
            role="tab"
            :href="route('jaar-thema')"
            preserve-scroll
            :aria-selected="isJaarThemaTab()"
            :class="tabClass(isJaarThemaTab())"
        >
            Jaar thema
        </Link>
    </div>
</template>
