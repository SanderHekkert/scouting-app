<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeftIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    member: { type: Object, required: true },
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

function memberDisplayName(m) {
    const fn = m?.first_name ?? '';
    const ln = m?.last_name ?? '';
    return `${fn}${ln ? ` ${ln}` : ''}`.trim() || '–';
}

function yesNo(value) {
    return value ? 'Ja' : 'Nee';
}

const editIndexUrl = `${route('members.index')}?edit=${props.member.id}`;

function deleteMember() {
    if (!confirm('Dit contact verwijderen?')) return;
    router.delete(route('members.destroy', props.member.id));
}
</script>

<template>
    <Head :title="memberDisplayName(member)" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center gap-3">
                <Link
                    :href="route('members.index')"
                    class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    <ChevronLeftIcon class="h-5 w-5" />
                    Terug naar Dolfijnen
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-lg space-y-4 text-white">
            <div class="rounded-xl bg-gray-800 p-5 shadow-sm">
                <h2 class="border-b border-gray-600 pb-3 text-xl font-semibold text-indigo-200">
                    {{ memberDisplayName(member) }}
                </h2>

                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Geïnstalleerd</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ yesNo(member.installed) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Actief</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ yesNo(member.active) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Bijzonderheden</dt>
                        <dd class="mt-1 whitespace-pre-wrap text-sm text-gray-200">
                            {{ dashIfEmpty(member.bijzonderheden) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Voornaam</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ dashIfEmpty(member.first_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Achternaam</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ dashIfEmpty(member.last_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Verjaardag</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ formatBirthday(member.birthday) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Leeftijd</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ member.age ?? '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Adres</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ dashIfEmpty(member.address) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Telefoon moeder</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ dashIfEmpty(member.phone_mother) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Telefoon vader</dt>
                        <dd class="mt-1 text-sm text-gray-100">{{ dashIfEmpty(member.phone_father) }}</dd>
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
                        @click="deleteMember"
                    >
                        <TrashIcon class="h-5 w-5" />
                        Verwijderen
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
