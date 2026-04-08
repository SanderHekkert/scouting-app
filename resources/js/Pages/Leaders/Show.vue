<script setup>
import EditableTextCell from '@/Components/EditableTextCell.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeftIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    leader: { type: Object, required: true },
});

const detailFieldSaving = ref(null);

function patchLeaderShowField(field, raw) {
    detailFieldSaving.value = field;
    router.patch(
        route('leaders.quick-update', props.leader.id),
        { [field]: raw ?? '' },
        {
            preserveScroll: true,
            onFinish: () => {
                detailFieldSaving.value = null;
            },
        },
    );
}

function isLeaderShowSaving(field) {
    return detailFieldSaving.value === field;
}

function formatBirthday(value) {
    if (value == null || value === '') return '–';
    const s = String(value).slice(0, 10);
    const parts = s.split('-');
    if (parts.length !== 3) return s;
    const [y, m, d] = parts;
    return `${d}-${m}-${y}`;
}

function leaderDisplayName(l) {
    const parts = [l?.first_name, l?.last_name].filter(Boolean);
    return parts.join(' ').trim() || '–';
}

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
                    class="inline-flex items-center gap-1 text-sm font-medium text-brand-red hover:text-brand-red-dark dark:text-brand-blue-light dark:hover:text-app-ink-dark"
                >
                    <ChevronLeftIcon class="h-5 w-5" />
                    Terug naar Leiding
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-lg space-y-4 text-app-ink dark:text-app-ink-dark">
            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-5">
                <h2 class="border-b border-brand-blue/35 pb-3 text-xl font-semibold text-app-ink dark:text-app-ink-dark">
                    {{ leaderDisplayName(leader) }}
                </h2>

                <dl class="mt-4 space-y-4">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Voornaam</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="leader.first_name || ''"
                                :multiline="false"
                                :saving="isLeaderShowSaving('first_name')"
                                @save="(v) => patchLeaderShowField('first_name', v)"
                            />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Achternaam</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="leader.last_name || ''"
                                :multiline="false"
                                :saving="isLeaderShowSaving('last_name')"
                                @save="(v) => patchLeaderShowField('last_name', v)"
                            />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Bijzonderheden</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="leader.bijzonderheden || ''"
                                multiline
                                :saving="isLeaderShowSaving('bijzonderheden')"
                                @save="(v) => patchLeaderShowField('bijzonderheden', v)"
                            />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Adres</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="leader.address || ''"
                                multiline
                                :saving="isLeaderShowSaving('address')"
                                @save="(v) => patchLeaderShowField('address', v)"
                            />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Postcode</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="leader.postal_code || ''"
                                :multiline="false"
                                :saving="isLeaderShowSaving('postal_code')"
                                @save="(v) => patchLeaderShowField('postal_code', v)"
                            />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Plaats</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="leader.city || ''"
                                :multiline="false"
                                :saving="isLeaderShowSaving('city')"
                                @save="(v) => patchLeaderShowField('city', v)"
                            />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Geboortedatum</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ formatBirthday(leader.birthday) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Telefoon</dt>
                        <dd class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="leader.phone_number || ''"
                                :multiline="false"
                                :saving="isLeaderShowSaving('phone_number')"
                                @save="(v) => patchLeaderShowField('phone_number', v)"
                            />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">E-mail</dt>
                        <dd class="mt-1 break-all text-sm text-app-ink dark:text-app-ink-dark">
                            <EditableTextCell
                                :text="leader.email || ''"
                                :multiline="false"
                                :saving="isLeaderShowSaving('email')"
                                @save="(v) => patchLeaderShowField('email', v)"
                            />
                        </dd>
                    </div>
                </dl>

                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                    <button type="button" class="btn-action-delete btn-action-delete--lg" title="Verwijderen" @click="deleteLeader">
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
