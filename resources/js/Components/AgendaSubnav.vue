<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const showJaarThema = computed(
    () => !['zeeverkenners', 'loodsen', 'wilde_vaart'].includes(page.props.auth?.active_section),
);

function tabClass(active) {
    return [
        'rounded-t-lg px-3 py-2 text-sm font-semibold transition sm:px-4',
        active
            ? 'bg-brand-blue/15 text-brand-blue-dark dark:text-app-ink-dark'
            : 'text-app-muted hover:bg-brand-blue/10 hover:text-app-ink dark:text-app-muted-dark dark:hover:text-app-ink-dark',
    ];
}

const isAgendaTab = () => route().current('events.index');
const isArchivedTab = () => route().current('events.archived');
const isJaarThemaTab = () => route().current('jaar-thema');
</script>

<template>
    <div
        class="mb-3 flex gap-1 overflow-x-auto overflow-y-hidden border-b border-brand-blue/35 pb-px [-webkit-overflow-scrolling:touch] sm:flex-wrap"
        role="tablist"
        aria-label="Agenda onderdelen"
    >
        <Link
            role="tab"
            :href="route('events.index')"
            preserve-scroll
            :aria-selected="isAgendaTab()"
            class="shrink-0 touch-manipulation whitespace-nowrap"
            :class="tabClass(isAgendaTab())"
        >
            Agenda
        </Link>
        <Link
            role="tab"
            :href="route('events.archived')"
            preserve-scroll
            :aria-selected="isArchivedTab()"
            class="shrink-0 touch-manipulation whitespace-nowrap"
            :class="tabClass(isArchivedTab())"
        >
            Gearchiveerde opkomsten
        </Link>
        <Link
            v-if="showJaarThema"
            role="tab"
            :href="route('jaar-thema')"
            preserve-scroll
            :aria-selected="isJaarThemaTab()"
            class="shrink-0 touch-manipulation whitespace-nowrap"
            :class="tabClass(isJaarThemaTab())"
        >
            Jaar thema
        </Link>
    </div>
</template>
