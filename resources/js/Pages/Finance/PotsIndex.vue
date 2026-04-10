<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    pots: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    canCreatePots: { type: Boolean, default: false },
});

const page = usePage();
const sectionLabelMap = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabelMap[page.props.auth?.active_section] || 'Dolfijnen');

const potForm = useForm({
    name: '',
    starting_amount: '',
    active: true,
});

function submitPot() {
    potForm.post(route('finance.pots.store'), {
        preserveScroll: true,
        onSuccess: () => potForm.reset(),
    });
}

function updatePot(pot) {
    router.patch(route('finance.pots.update', pot.id), {
        name: pot.name,
        current_amount: pot.current_amount,
        active: !!pot.active,
    }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`${speltakLabel} - Potjes`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Potjes</h2>
                <Link
                    v-if="canCreatePots"
                    :href="route('finance.pots.create')"
                    class="rounded bg-brand-blue px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-blue-dark"
                >
                    Nieuw potje
                </Link>
            </div>
        </template>

        <div class="space-y-4">
            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <h4 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Potjes overzicht</h4>

                <form v-if="canCreatePots" class="mt-3 grid gap-3 rounded-lg border border-app-border bg-white p-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="submitPot">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Naam potje</label>
                        <input v-model="potForm.name" type="text" placeholder="Bijv. Kamp 2026" class="w-full rounded border border-app-border bg-white px-3 py-2 text-black" required />
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold uppercase tracking-wide text-slate-600">Startbudget</label>
                        <input v-model="potForm.starting_amount" type="number" step="0.01" min="0" placeholder="0,00" class="w-full rounded border border-app-border bg-white px-3 py-2 text-black" required />
                    </div>
                    <label class="inline-flex items-center gap-2 self-end rounded border border-app-border bg-slate-50 px-3 py-2.5 text-sm font-medium text-black">
                        <input v-model="potForm.active" type="checkbox" />
                        Direct actief
                    </label>
                    <button type="submit" class="self-end rounded bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60" :disabled="potForm.processing">
                        {{ potForm.processing ? 'Bezig met aanmaken...' : 'Potje aanmaken' }}
                    </button>
                </form>

                <div class="mt-3 space-y-2">
                    <p v-if="!props.pots.length" class="rounded-lg border border-dashed border-app-border bg-white px-3 py-3 text-sm text-slate-600">
                        Er zijn nog geen potjes voor deze speltak.
                    </p>
                    <div v-for="pot in props.pots" :key="`pot-${pot.id}`" class="grid gap-2 rounded-lg border border-app-border bg-white p-3 sm:grid-cols-[1fr_10rem_10rem_auto]">
                        <input v-model="pot.name" class="rounded border border-app-border px-2 py-1.5 text-black" :disabled="!canManage" />
                        <input :value="pot.starting_amount" class="rounded border border-app-border bg-slate-100 px-2 py-1.5 text-black" disabled />
                        <input v-model="pot.current_amount" type="number" step="0.01" class="rounded border border-app-border px-2 py-1.5 text-black" :disabled="!canManage" />
                        <button v-if="canManage" type="button" class="rounded bg-brand-blue px-3 py-1.5 text-sm text-white hover:bg-brand-blue-dark" @click="updatePot(pot)">Opslaan</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

