<script setup>
import SpeltakSubnav from '@/Components/SpeltakSubnav.vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon, MagnifyingGlassIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    membersTab: { type: String, required: true },
    memberSearchQuery: { type: String, default: '' },
    members: { type: Array, default: () => [] },
    filteredMembers: { type: Array, default: () => [] },
    sortedDolfijnenMembers: { type: Array, default: () => [] },
    sortedFilteredMembers: { type: Array, default: () => [] },
    speltakLabel: { type: String, required: true },
    isBestuurSection: { type: Boolean, default: false },
    isBeversSection: { type: Boolean, default: false },
    canUpdateMembers: { type: Boolean, default: false },
    canDeleteMembers: { type: Boolean, default: false },
    rowHighlightMemberId: { type: Number, default: null },
    memberDisplayName: { type: Function, required: true },
    yesNo: { type: Function, required: true },
    formatBirthday: { type: Function, required: true },
});

const emit = defineEmits(['update:memberSearchQuery', 'edit-member', 'delete-member']);
</script>

<template>
    <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
        <SpeltakSubnav />

        <div class="mb-3 flex w-full flex-col gap-3 border-b border-brand-blue/35 pb-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-app-ink dark:text-app-ink-dark">
                    {{ props.membersTab === 'dolfijnen' ? 'Overzicht' : 'Bijzonderheden' }}
                </h3>
                <p
                    v-if="props.membersTab === 'dolfijnen'"
                    class="mt-0.5 text-xs text-app-muted dark:text-app-muted-dark"
                />
            </div>
            <div class="flex w-full max-w-sm items-center gap-2 self-end sm:ms-auto">
                <MagnifyingGlassIcon
                    class="h-5 w-5 shrink-0 text-app-muted dark:text-app-muted-dark"
                    aria-hidden="true"
                />
                <label class="sr-only" for="members-page-search">Zoeken in alle contactvelden</label>
                <input
                    id="members-page-search"
                    :value="props.memberSearchQuery"
                    type="search"
                    autocomplete="off"
                    :placeholder="
                        props.membersTab === 'dolfijnen'
                            ? 'Zoek op naam, adres, telefoon…'
                            : 'Zoek op naam, adres, telefoon, bijzonderheden…'
                    "
                    class="min-w-0 flex-1 rounded border border-app-border bg-white px-3 py-2 text-sm text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    @input="emit('update:memberSearchQuery', $event.target.value)"
                />
            </div>
        </div>

        <p
            v-if="props.membersTab === 'bijzonderheden'"
            class="mb-3 text-xs text-app-muted dark:text-app-muted-dark"
        >
            Allergiën, medicatie, dieet en andere aandachtspunten. Dubbelklik in een cel om te bewerken.
            Kinderen met ingevulde bijzonderheden staan bovenaan. Voor leiding: menu Leiding.
        </p>

        <div v-if="!props.members?.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
            Nog geen {{ props.speltakLabel }}.
        </div>
        <div v-else-if="!props.filteredMembers.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
            Geen resultaten voor deze zoekopdracht.
        </div>

        <div v-else-if="props.membersTab === 'dolfijnen'" class="space-y-2 md:space-y-0">
            <div class="space-y-2 md:hidden">
                <div
                    v-for="member in props.sortedDolfijnenMembers"
                    :key="`m-mob-${member.id}`"
                    class="surface-brand-top rounded-xl border border-brand-blue/30 bg-app-panel px-4 py-3 text-app-ink shadow-sm dark:bg-app-panel-dark/95 dark:text-app-ink-dark"
                >
                    <Link
                        :href="route('members.show', member.id)"
                        class="flex items-center justify-between gap-3 rounded-lg active:bg-brand-blue/15"
                    >
                        <span class="flex min-w-0 items-center gap-2 truncate">
                            <span class="truncate font-medium">{{ props.memberDisplayName(member) }}</span>
                        </span>
                        <ChevronRightIcon class="h-5 w-5 shrink-0 text-app-muted dark:text-app-muted-dark" aria-hidden="true" />
                    </Link>
                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-brand-blue/25 pt-3 dark:border-brand-blue/35" @click.stop>
                        <span class="text-xs font-semibold text-app-muted dark:text-app-muted-dark">Geïnstalleerd</span>
                        <span class="rounded bg-brand-blue/10 px-2 py-0.5 text-xs font-semibold text-app-ink dark:text-app-ink-dark">{{ props.yesNo(member.installed) }}</span>
                    </div>
                    <div v-if="!props.isBestuurSection && !props.isBeversSection" class="mt-2 flex flex-wrap items-center gap-2" @click.stop>
                        <span class="text-xs font-semibold text-app-muted dark:text-app-muted-dark">Gedoopt</span>
                        <span class="rounded bg-brand-blue/10 px-2 py-0.5 text-xs font-semibold text-app-ink dark:text-app-ink-dark">{{ props.yesNo(member.gedoopt) }}</span>
                    </div>
                </div>
            </div>
            <div class="hidden overflow-x-auto rounded-lg border border-brand-blue/25 md:block">
                <table class="w-full min-w-[50rem] border-collapse text-left text-sm text-app-ink lg:min-w-[58rem] dark:text-app-ink-dark">
                    <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5">Geïnstalleerd</th>
                            <th v-if="!props.isBestuurSection && !props.isBeversSection" scope="col" class="whitespace-nowrap px-3 py-2.5">Gedoopt</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5">Naam</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5">Verjaardag</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5">Leeftijd</th>
                            <th scope="col" class="min-w-[10rem] px-3 py-2.5">Adres</th>
                            <th scope="col" class="min-w-[9rem] px-3 py-2.5">Telefoon moeder</th>
                            <th scope="col" class="min-w-[9rem] px-3 py-2.5">Telefoon vader</th>
                            <th scope="col" class="min-w-[11rem] whitespace-nowrap px-3 py-2.5 text-end sm:text-start">
                                Acties
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-blue/25">
                        <tr
                            v-for="member in props.sortedDolfijnenMembers"
                            :id="`member-row-${member.id}`"
                            :key="member.id"
                            class="bg-brand-blue/5 transition-colors hover:bg-brand-blue/12 dark:bg-app-panel-dark/50 dark:hover:bg-brand-blue/15"
                            :class="{ '!bg-brand-blue/15 dark:!bg-app-canvas-dark/90': props.rowHighlightMemberId === member.id }"
                        >
                            <td class="whitespace-nowrap px-3 py-2.5 align-top text-app-ink dark:text-app-ink-dark">
                                {{ props.yesNo(member.installed) }}
                            </td>
                            <td v-if="!props.isBestuurSection && !props.isBeversSection" class="whitespace-nowrap px-3 py-2.5 align-top text-app-ink dark:text-app-ink-dark">
                                {{ props.yesNo(member.gedoopt) }}
                            </td>
                            <td class="max-w-[16rem] px-3 py-2.5 align-top">{{ props.memberDisplayName(member) }}</td>
                            <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums text-app-ink dark:text-app-ink-dark">
                                {{ member.birthday ? props.formatBirthday(member.birthday) : '–' }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums text-app-ink dark:text-app-ink-dark">
                                {{ member.age ?? '–' }}
                            </td>
                            <td class="px-3 py-2.5 align-top">{{ member.address || '–' }}</td>
                            <td class="px-3 py-2.5 align-top tabular-nums text-app-ink dark:text-app-ink-dark">{{ member.phone_mother || '–' }}</td>
                            <td class="px-3 py-2.5 align-top tabular-nums text-app-ink dark:text-app-ink-dark">{{ member.phone_father || '–' }}</td>
                            <td class="px-3 py-2.5 align-top">
                                <button v-if="props.canUpdateMembers" type="button" class="btn-action-edit me-2" title="Bewerken" @click="emit('edit-member', member)">
                                    <PencilSquareIcon class="h-4 w-4 shrink-0" />
                                </button>
                                <button v-if="props.canDeleteMembers" type="button" class="btn-action-delete" title="Verwijderen" @click="emit('delete-member', member)">
                                    <TrashIcon class="h-4 w-4 shrink-0" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else-if="props.membersTab === 'bijzonderheden'" class="space-y-2">
            <div class="space-y-2 md:hidden">
                <div
                    v-for="member in props.sortedFilteredMembers"
                    :key="`bijz-mob-${member.id}`"
                    class="surface-brand-top rounded-xl border border-brand-blue/30 bg-app-panel p-4 shadow-sm dark:bg-app-panel-dark/95"
                >
                    <p class="font-medium text-app-ink dark:text-app-ink-dark">{{ props.memberDisplayName(member) }}</p>
                    <div class="mt-2 text-sm leading-snug">
                        {{ member.bijzonderheden || '–' }}
                    </div>
                </div>
            </div>
            <div class="hidden overflow-x-auto rounded-lg border border-brand-blue/25 md:block">
                <table class="w-full min-w-[24rem] border-collapse text-left text-sm text-app-ink dark:text-app-ink-dark">
                    <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                            <th scope="col" class="min-w-[8rem] px-3 py-2.5">Naam</th>
                            <th scope="col" class="min-w-[16rem] px-3 py-2.5">Bijzonderheden</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-blue/25">
                        <tr
                            v-for="member in props.sortedFilteredMembers"
                            :id="`member-row-${member.id}`"
                            :key="`bijz-${member.id}`"
                            class="bg-brand-blue/5 transition-colors hover:bg-brand-blue/12 dark:bg-app-panel-dark/50 dark:hover:bg-brand-blue/15"
                            :class="{ '!bg-brand-blue/15 dark:!bg-app-canvas-dark/90': props.rowHighlightMemberId === member.id }"
                        >
                            <td class="px-3 py-2.5 align-top font-medium text-app-ink dark:text-app-ink-dark">
                                {{ props.memberDisplayName(member) }}
                            </td>
                            <td class="px-3 py-2.5 align-top break-words leading-snug text-app-ink dark:text-app-ink-dark">
                                {{ member.bijzonderheden || '–' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
