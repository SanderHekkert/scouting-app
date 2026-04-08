<script setup>
import EditableTextCell from '@/Components/EditableTextCell.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronLeftIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    member: { type: Object, required: true },
});
const page = usePage();
const sectionLabelMap = {
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    loodsen: 'Loodsen',
    bevers: 'Bevers',
    wilde_vaart: 'Wilde Vaart',
    bestuur: 'Bestuur',
};
const speltakLabel = ref(sectionLabelMap[page.props.auth?.active_section] || 'Dolfijnen');

const installedSaving = ref(false);

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

function installedDetailToggleClass(isJa) {
    const on = isJa ? Boolean(props.member.installed) : !props.member.installed;
    if (on) {
        return isJa
            ? 'bg-emerald-700 text-white ring-2 ring-emerald-400/80'
            : 'bg-rose-900/80 text-rose-100 ring-2 ring-rose-500/70';
    }
    return 'border border-brand-blue/40 bg-app-panel text-app-ink hover:bg-brand-blue/10 dark:border-brand-blue/45 dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/20';
}

function setInstalled(value) {
    if (Boolean(props.member.installed) === value) {
        return;
    }
    installedSaving.value = true;
    router.patch(
        route('members.update-installed', props.member.id),
        { installed: value },
        {
            preserveScroll: true,
            onFinish: () => {
                installedSaving.value = false;
            },
        },
    );
}

function deleteMember() {
    if (!confirm('Dit contact verwijderen?')) return;
    router.delete(route('members.destroy', props.member.id));
}

const detailFieldSaving = ref(null);

function normalizeMemberQuickPayload(field, raw) {
    if (field === 'age') {
        const s = String(raw ?? '').trim();
        if (s === '') {
            return { age: null };
        }
        const n = Number.parseInt(s, 10);
        return { age: Number.isNaN(n) ? null : n };
    }
    if (field === 'birthday') {
        const s = String(raw ?? '').trim();
        return { birthday: s === '' ? null : s };
    }
    return { [field]: raw ?? '' };
}

function patchShowField(field, raw) {
    const payload = normalizeMemberQuickPayload(field, raw);
    const k = Object.keys(payload)[0];
    detailFieldSaving.value = k;
    router.patch(route('members.quick-update', props.member.id), payload, {
        preserveScroll: true,
        onFinish: () => {
            detailFieldSaving.value = null;
        },
    });
}

function isShowSaving(field) {
    return detailFieldSaving.value === field;
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
                    Terug naar {{ speltakLabel }}
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
                        <dd class="mt-1">
                            <div
                                class="flex flex-wrap gap-2"
                                :class="{ 'pointer-events-none opacity-60': installedSaving }"
                            >
                                <button
                                    type="button"
                                    class="rounded px-3 py-1.5 text-xs font-semibold transition disabled:opacity-50"
                                    :class="installedDetailToggleClass(true)"
                                    :disabled="installedSaving"
                                    @click="setInstalled(true)"
                                >
                                    Ja
                                </button>
                                <button
                                    type="button"
                                    class="rounded px-3 py-1.5 text-xs font-semibold transition disabled:opacity-50"
                                    :class="installedDetailToggleClass(false)"
                                    :disabled="installedSaving"
                                    @click="setInstalled(false)"
                                >
                                    Nee
                                </button>
                            </div>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Actief</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ yesNo(member.active) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Bijzonderheden</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="member.bijzonderheden || ''"
                                multiline
                                :saving="isShowSaving('bijzonderheden')"
                                @save="(v) => patchShowField('bijzonderheden', v)"
                            />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Voornaam</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="member.first_name || ''"
                                :multiline="false"
                                :saving="isShowSaving('first_name')"
                                @save="(v) => patchShowField('first_name', v)"
                            />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Achternaam</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="member.last_name || ''"
                                :multiline="false"
                                :saving="isShowSaving('last_name')"
                                @save="(v) => patchShowField('last_name', v)"
                            />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Verjaardag</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ formatBirthday(member.birthday) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Leeftijd</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="member.age != null ? String(member.age) : ''"
                                :multiline="false"
                                :saving="isShowSaving('age')"
                                @save="(v) => patchShowField('age', v)"
                            />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Adres</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="member.address || ''"
                                multiline
                                :saving="isShowSaving('address')"
                                @save="(v) => patchShowField('address', v)"
                            />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Telefoon moeder</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="member.phone_mother || ''"
                                :multiline="false"
                                :saving="isShowSaving('phone_mother')"
                                @save="(v) => patchShowField('phone_mother', v)"
                            />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Telefoon vader</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="member.phone_father || ''"
                                :multiline="false"
                                :saving="isShowSaving('phone_father')"
                                @save="(v) => patchShowField('phone_father', v)"
                            />
                        </dd>
                    </div>
                </dl>

                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                    <button type="button" class="btn-action-delete btn-action-delete--lg" title="Verwijderen" @click="deleteMember">
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
