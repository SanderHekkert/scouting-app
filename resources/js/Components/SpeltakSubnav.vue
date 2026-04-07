<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const sectionLabelMap = {
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    bevers: 'Bevers',
    wilde_vaart: 'Wilde Vaart',
};
const speltakLabel = computed(() => sectionLabelMap[page.props.auth?.active_section] || 'Dolfijnen');
const showTipperTopper = computed(
    () => !['bevers', 'zeeverkenners', 'wilde_vaart'].includes(page.props.auth?.active_section),
);
const showPods = computed(
    () => !['bevers'].includes(page.props.auth?.active_section),
);
const podsLabel = computed(
    () => page.props.auth?.active_section === 'zeeverkenners' ? 'Bakindeling' : 'Vinindeling',
);

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
    <div
        class="mb-3 flex gap-1 overflow-x-auto overflow-y-hidden border-b border-brand-blue/35 pb-px [-webkit-overflow-scrolling:touch] sm:flex-wrap"
        role="tablist"
        :aria-label="`${speltakLabel} onderdelen`"
    >
        <Link
            role="tab"
            :href="route('members.index')"
            preserve-scroll
            :aria-selected="route().current('members.index')"
            class="shrink-0 touch-manipulation whitespace-nowrap"
            :class="tabClass(route().current('members.index'))"
        >
            {{ speltakLabel }}
        </Link>
        <Link
            role="tab"
            :href="route('members.bijzonderheden')"
            preserve-scroll
            :aria-selected="route().current('members.bijzonderheden')"
            class="shrink-0 touch-manipulation whitespace-nowrap"
            :class="tabClass(route().current('members.bijzonderheden'))"
        >
            Bijzonderheden
        </Link>
        <Link
            v-if="showTipperTopper"
            role="tab"
            :href="route('tipper-topper-opkomst.index')"
            preserve-scroll
            :aria-selected="route().current('tipper-topper-opkomst.index')"
            class="shrink-0 touch-manipulation whitespace-nowrap"
            :class="tabClass(route().current('tipper-topper-opkomst.index'))"
        >
            Tipper- & Topper opkomst
        </Link>
        <Link
            v-if="showPods"
            role="tab"
            :href="route('pods.index')"
            preserve-scroll
            :aria-selected="route().current('pods.index')"
            class="shrink-0 touch-manipulation whitespace-nowrap"
            :class="tabClass(route().current('pods.index'))"
        >
            {{ podsLabel }}
        </Link>
    </div>
</template>
