<script setup>
import { Link } from '@inertiajs/vue3';
import { CheckBadgeIcon, ChevronRightIcon, MagnifyingGlassIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    leaders: { type: Array, default: () => [] },
    filteredLeaders: { type: Array, default: () => [] },
    leaderSearchQuery: { type: String, default: '' },
    isBestuurSection: { type: Boolean, default: false },
    canUpdateLeaders: { type: Boolean, default: false },
    canDeleteLeaders: { type: Boolean, default: false },
    leaderListName: { type: Function, required: true },
    leaderHasBijzonderheden: { type: Function, required: true },
    leaderFullAddress: { type: Function, required: true },
    yesNo: { type: Function, required: true },
    formatBirthday: { type: Function, required: true },
    leaderAge: { type: Function, required: true },
});

const emit = defineEmits(['update:leaderSearchQuery', 'edit-leader', 'delete-leader']);
</script>

<template>
    <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
        <div class="mb-3 flex w-full flex-col gap-3 border-b border-brand-blue/35 pb-2 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-lg font-semibold text-app-ink dark:text-app-ink-dark">Overzicht</h3>
            <div class="flex w-full max-w-sm items-center gap-2 self-end sm:ms-auto">
                <MagnifyingGlassIcon
                    class="h-5 w-5 shrink-0 text-app-muted dark:text-app-muted-dark"
                    aria-hidden="true"
                />
                <label class="sr-only" for="leaders-page-search">Zoeken in alle leidingvelden</label>
                <input
                    id="leaders-page-search"
                    :value="props.leaderSearchQuery"
                    type="search"
                    autocomplete="off"
                    placeholder="Zoek op naam, adres, e-mail, bijzonderheden…"
                    class="min-w-0 flex-1 rounded border border-app-border bg-white px-3 py-2 text-sm text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    @input="emit('update:leaderSearchQuery', $event.target.value)"
                />
            </div>
        </div>
        <div v-if="!props.leaders?.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
            Nog geen leiding. Voeg iemand toe met de knop hierboven.
        </div>
        <div v-else-if="!props.filteredLeaders.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
            Geen resultaten voor deze zoekopdracht.
        </div>
        <div v-else class="space-y-2 md:space-y-0">
            <div class="space-y-2 md:hidden">
                <div
                    v-for="leader in props.filteredLeaders"
                    :key="`l-mob-${leader.id}`"
                    class="surface-brand-top flex flex-col rounded-xl border border-brand-blue/30 bg-app-panel px-4 py-3 text-app-ink shadow-sm dark:bg-app-panel-dark/95 dark:text-app-ink-dark"
                >
                    <Link
                        :href="route('leaders.show', leader.id)"
                        class="flex items-start justify-between gap-3 rounded-lg active:bg-brand-blue/15"
                    >
                        <span class="flex min-w-0 items-center gap-2">
                            <span
                                v-if="props.leaderHasBijzonderheden(leader)"
                                class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-red"
                                title="Heeft bijzonderheden"
                            />
                            <span class="flex min-w-0 items-center gap-1">
                                <span class="min-w-0 truncate font-medium leading-snug">{{ props.leaderListName(leader) }}</span>
                                <CheckBadgeIcon v-if="leader.email_verified" class="h-4 w-4 shrink-0 text-emerald-600" title="E-mail geverifieerd" />
                            </span>
                        </span>
                        <ChevronRightIcon class="mt-0.5 h-5 w-5 shrink-0 text-app-muted dark:text-app-muted-dark" aria-hidden="true" />
                    </Link>
                    <div class="mt-1 text-xs text-app-muted dark:text-app-muted-dark">
                        {{ props.leaderFullAddress(leader) }}
                    </div>
                    <div class="mt-2 border-t border-brand-blue/25 pt-2 text-sm dark:border-brand-blue/35">
                        <p><span class="font-medium">Geïnstalleerd:</span> {{ props.yesNo(leader.installed) }}</p>
                        <p v-if="!props.isBestuurSection"><span class="font-medium">Gedoopt:</span> {{ props.yesNo(leader.gedoopt) }}</p>
                        <p><span class="font-medium">Bijzonderheden:</span> {{ leader.bijzonderheden || '–' }}</p>
                        <p><span class="font-medium">Geboortedatum:</span> {{ props.formatBirthday(leader.birthday) }}</p>
                        <p><span class="font-medium">Leeftijd:</span> {{ props.leaderAge(leader.birthday) }}</p>
                        <p><span class="font-medium">Telefoon:</span> {{ leader.phone_number || '–' }}</p>
                        <p><span class="font-medium">Noodcontact:</span> {{ leader.emergency_contact || '–' }}</p>
                        <p><span class="font-medium">E-mail:</span> {{ leader.email || '–' }}</p>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <button v-if="props.canUpdateLeaders" type="button" class="btn-action-edit" title="Bewerken" @click.stop="emit('edit-leader', leader)">
                            <PencilSquareIcon class="h-4 w-4 shrink-0" />
                        </button>
                        <button v-if="props.canDeleteLeaders" type="button" class="btn-action-delete" title="Verwijderen" @click.stop="emit('delete-leader', leader)">
                            <TrashIcon class="h-4 w-4 shrink-0" />
                        </button>
                    </div>
                </div>
            </div>
            <div class="hidden overflow-x-auto rounded-lg border border-brand-blue/25 md:block">
                <table class="w-full min-w-[56rem] border-collapse text-left text-sm text-app-ink lg:min-w-[68rem] dark:text-app-ink-dark">
                    <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5">Naam</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5">Geïnstalleerd</th>
                            <th v-if="!props.isBestuurSection" scope="col" class="whitespace-nowrap px-3 py-2.5">Gedoopt</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5">Volledig adres</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5">Bijzonderheden</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5">Geboortedatum</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5">Leeftijd</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5">Telefoonnummer</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5">Noodcontact</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5">E-mail</th>
                            <th scope="col" class="min-w-[11rem] whitespace-nowrap px-3 py-2.5 text-end sm:text-start">
                                Acties
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-blue/25">
                        <tr
                            v-for="leader in props.filteredLeaders"
                            :id="`leader-row-${leader.id}`"
                            :key="leader.id"
                            class="bg-brand-blue/5 transition-colors hover:bg-brand-blue/12 dark:bg-app-panel-dark/50 dark:hover:bg-brand-blue/15"
                        >
                            <td class="max-w-[12rem] px-3 py-2.5 align-top">
                                <div class="flex items-center gap-1">
                                    <span class="truncate">{{ props.leaderListName(leader) }}</span>
                                    <CheckBadgeIcon v-if="leader.email_verified" class="h-4 w-4 shrink-0 text-emerald-600" title="E-mail geverifieerd" />
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5 align-top">{{ props.yesNo(leader.installed) }}</td>
                            <td v-if="!props.isBestuurSection" class="whitespace-nowrap px-3 py-2.5 align-top">{{ props.yesNo(leader.gedoopt) }}</td>
                            <td class="max-w-[14rem] px-3 py-2.5 align-top">
                                {{ props.leaderFullAddress(leader) }}
                            </td>
                            <td class="max-w-[14rem] px-3 py-2.5 align-top">
                                <span class="line-clamp-2">{{ leader.bijzonderheden || '–' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums">
                                {{ props.formatBirthday(leader.birthday) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums">
                                {{ props.leaderAge(leader.birthday) }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums text-app-ink dark:text-app-ink-dark">
                                {{ leader.phone_number || '–' }}
                            </td>
                            <td class="max-w-[16rem] px-3 py-2.5 align-top">
                                {{ leader.emergency_contact || '–' }}
                            </td>
                            <td class="max-w-[16rem] break-all px-3 py-2.5 align-top">
                                {{ leader.email || '–' }}
                            </td>
                            <td class="px-3 py-2.5 align-top">
                                <button v-if="props.canUpdateLeaders" type="button" class="btn-action-edit me-2" title="Bewerken" @click="emit('edit-leader', leader)">
                                    <PencilSquareIcon class="h-4 w-4 shrink-0" />
                                </button>
                                <button v-if="props.canDeleteLeaders" type="button" class="btn-action-delete" title="Verwijderen" @click="emit('delete-leader', leader)">
                                    <TrashIcon class="h-4 w-4 shrink-0" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
