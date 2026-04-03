<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeftIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    leader: { type: Object, required: true },
});

function formatBirthday(value) {
    if (value == null || value === '') return '–';
    const s = String(value).slice(0, 10);
    const parts = s.split('-');
    if (parts.length !== 3) return s;
    const [y, m, d] = parts;
    return `${d}-${m}-${y}`;
}

function dashIfEmpty(value) {
    if (value == null || String(value).trim() === '') return '–';
    return value;
}

function leaderDisplayName(l) {
    const parts = [l?.first_name, l?.last_name].filter(Boolean);
    return parts.join(' ').trim() || '–';
}

const editIndexUrl = `${route('leaders.index')}?edit=${props.leader.id}`;

function deleteLeader() {
    if (!confirm('Deze leiding verwijderen?')) return;
    router.delete(route('leaders.destroy', props.leader.id));
}
</script>

<template>
    <Head :title="leaderDisplayName(leader)" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-3">
                <Link
                    :href="route('leaders.index')"
                    class="inline-flex items-center gap-1 text-sm font-medium text-brand-red hover:text-brand-red-dark dark:text-brand-blue-light dark:hover:text-brand-yellow-soft"
                >
                    <ChevronLeftIcon class="h-5 w-5" />
                    Terug naar Leiding
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-lg space-y-4 text-app-ink dark:text-app-ink-dark">
            <div class="rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-5">
                <h2 class="border-b border-brand-blue/35 pb-3 text-xl font-semibold text-brand-yellow-soft">
                    {{ leaderDisplayName(leader) }}
                </h2>

                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Voornaam</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ dashIfEmpty(leader.first_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Achternaam</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ dashIfEmpty(leader.last_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Bijzonderheden</dt>
                        <dd class="mt-1 whitespace-pre-wrap text-sm text-app-ink dark:text-app-ink-dark">
                            {{ dashIfEmpty(leader.bijzonderheden) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Adres</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ dashIfEmpty(leader.address) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Postcode</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ dashIfEmpty(leader.postal_code) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Plaats</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ dashIfEmpty(leader.city) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Geboortedatum</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ formatBirthday(leader.birthday) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Telefoon</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ dashIfEmpty(leader.phone_number) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">E-mail</dt>
                        <dd class="mt-1 break-all text-sm">
                            <a
                                v-if="leader.email"
                                :href="`mailto:${leader.email}`"
                                class="text-brand-blue-light underline decoration-brand-blue-light/70 underline-offset-2 hover:text-brand-yellow-soft"
                            >
                                {{ leader.email }}
                            </a>
                            <span v-else class="text-app-muted dark:text-app-muted-dark">–</span>
                        </dd>
                    </div>
                </dl>

                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                    <Link
                        :href="editIndexUrl"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-red px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-red-dark"
                    >
                        <PencilSquareIcon class="h-5 w-5" />
                        Bewerken
                    </Link>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-800/60 bg-red-950/35 px-4 py-2.5 text-sm font-medium text-red-300 hover:bg-red-950/55"
                        @click="deleteLeader"
                    >
                        <TrashIcon class="h-5 w-5" />
                        Verwijderen
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
