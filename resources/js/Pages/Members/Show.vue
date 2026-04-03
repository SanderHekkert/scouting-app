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
                    class="inline-flex items-center gap-1 text-sm font-medium text-brand-red hover:text-brand-red-dark dark:text-brand-blue-light dark:hover:text-app-ink-dark"
                >
                    <ChevronLeftIcon class="h-5 w-5" />
                    Terug naar Dolfijnen
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-lg space-y-4 text-app-ink dark:text-app-ink-dark">
            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-5">
                <h2 class="border-b border-brand-blue/35 pb-3 text-xl font-semibold text-app-ink dark:text-app-ink-dark">
                    {{ memberDisplayName(member) }}
                </h2>

                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Geïnstalleerd</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ yesNo(member.installed) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Actief</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ yesNo(member.active) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Bijzonderheden</dt>
                        <dd class="mt-1 whitespace-pre-wrap text-sm text-app-ink dark:text-app-ink-dark">
                            {{ dashIfEmpty(member.bijzonderheden) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Voornaam</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ dashIfEmpty(member.first_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Achternaam</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ dashIfEmpty(member.last_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Verjaardag</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ formatBirthday(member.birthday) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Leeftijd</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ member.age ?? '–' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Adres</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ dashIfEmpty(member.address) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Telefoon moeder</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ dashIfEmpty(member.phone_mother) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Telefoon vader</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ dashIfEmpty(member.phone_father) }}</dd>
                    </div>
                </dl>

                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                    <Link :href="editIndexUrl" class="btn-action-edit btn-action-edit--lg">
                        <PencilSquareIcon class="h-5 w-5" />
                        Bewerken
                    </Link>
                    <button type="button" class="btn-action-delete btn-action-delete--lg" @click="deleteMember">
                        <TrashIcon class="h-5 w-5" />
                        Verwijderen
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
