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
                    class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    <ChevronLeftIcon class="h-5 w-5" />
                    Terug naar Leiding
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-lg space-y-4 text-white">
            <div class="rounded-xl bg-gray-800 p-5 shadow-sm">
                <h2 class="border-b border-gray-600 pb-3 text-xl font-semibold text-indigo-200">
                    {{ leaderDisplayName(leader) }}
                </h2>

                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Voornaam</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ dashIfEmpty(leader.first_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Achternaam</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ dashIfEmpty(leader.last_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Bijzonderheden</dt>
                        <dd class="mt-1 whitespace-pre-wrap text-sm text-gray-200">
                            {{ dashIfEmpty(leader.bijzonderheden) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Adres</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ dashIfEmpty(leader.address) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Postcode</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ dashIfEmpty(leader.postal_code) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Plaats</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ dashIfEmpty(leader.city) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Geboortedatum</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ formatBirthday(leader.birthday) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Telefoon</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ dashIfEmpty(leader.phone_number) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">E-mail</dt>
                        <dd class="mt-1 break-all text-sm">
                            <a
                                v-if="leader.email"
                                :href="`mailto:${leader.email}`"
                                class="text-indigo-300 underline decoration-indigo-400/80 underline-offset-2 hover:text-indigo-200"
                            >
                                {{ leader.email }}
                            </a>
                            <span v-else class="text-gray-500">–</span>
                        </dd>
                    </div>
                </dl>

                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                    <Link
                        :href="editIndexUrl"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-500"
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
