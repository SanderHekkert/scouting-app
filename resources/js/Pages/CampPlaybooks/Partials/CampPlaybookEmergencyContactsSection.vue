<script setup>
const props = defineProps({
    emergencyContacts: { type: Object, required: true },
});

const emit = defineEmits(['add-row', 'remove-row']);
const labels = {
    huisartsen: 'Huisartsen',
    ziekenhuizen: 'Ziekenhuizen',
    tandartsen: 'Tandartsen',
};
</script>

<template>
    <div class="space-y-3">
        <div
            v-for="(label, key) in labels"
            :key="`emergency-${key}`"
            class="rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900"
        >
            <h4 class="mb-2 text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ label }}</h4>
            <div class="space-y-2">
                <div
                    v-for="(entry, entryIdx) in props.emergencyContacts[key]"
                    :key="`emergency-${key}-${entryIdx}`"
                    class="rounded border border-app-border/80 p-2 dark:border-app-border-dark/80"
                >
                    <div class="mb-2 flex justify-end">
                        <button type="button" class="btn-action-delete" title="Rij verwijderen" @click="emit('remove-row', key, entryIdx)">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21.75H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.087-2.201a51.964 51.964 0 0 0-3.326 0c-1.176.037-2.087 1.022-2.087 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <input v-model="entry.name" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Naam" />
                        <input v-model="entry.address" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Adres" />
                        <input v-model="entry.postal_code" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Postcode" />
                        <input v-model="entry.city" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Plaats" />
                        <input v-model="entry.phone_010" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="010 nummer" />
                        <input v-model="entry.website" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Site" />
                        <textarea v-model="entry.extra_info" rows="3" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black sm:col-span-2 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Extra informatie" />
                    </div>
                </div>
            </div>
            <div class="mt-2">
                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Rij toevoegen" aria-label="Rij toevoegen" @click="emit('add-row', key)">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
