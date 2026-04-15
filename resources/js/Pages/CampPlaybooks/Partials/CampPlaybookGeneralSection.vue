<script setup>
import { TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    form: { type: Object, required: true },
    coverPreviewUrl: { type: String, default: '' },
    setCampLocation: { type: Function, required: true },
    onCoverPhotoSelected: { type: Function, required: true },
    removeCoverPhoto: { type: Function, required: true },
});
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2">
        <div class="space-y-1">
            <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Jaartal</label>
            <input
                v-model="props.form.camp_year"
                type="number"
                min="2020"
                max="2100"
                class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                placeholder="Jaar"
                required
            />
        </div>
        <div class="space-y-1 sm:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Titel</label>
            <input
                v-model="props.form.title"
                type="text"
                class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark"
                placeholder="Titel (bijv. Hollywood Kamp)"
                required
            />
        </div>
        <div class="space-y-2 sm:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Kamptype</label>
            <div class="inline-flex items-center rounded-full border border-app-border bg-slate-100 p-1 dark:border-app-border-dark dark:bg-slate-800">
                <button
                    type="button"
                    class="rounded-full px-4 py-1.5 text-xs font-semibold transition"
                    :class="props.form.camp_location === 'clubhuis' ? 'bg-brand-blue text-white shadow-sm' : 'text-app-ink dark:text-app-ink-dark'"
                    @click="props.setCampLocation('clubhuis')"
                >
                    Clubhuis
                </button>
                <button
                    type="button"
                    class="rounded-full px-4 py-1.5 text-xs font-semibold transition"
                    :class="props.form.camp_location === 'fram' ? 'bg-brand-blue text-white shadow-sm' : 'text-app-ink dark:text-app-ink-dark'"
                    @click="props.setCampLocation('fram')"
                >
                    Fram
                </button>
            </div>
        </div>
        <div class="space-y-1">
            <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Plaats</label>
            <input
                v-model="props.form.camp_place"
                type="text"
                class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark"
                placeholder="Bijv. Rotterdam"
            />
        </div>
        <div class="space-y-1 sm:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Datum (daterange)</label>
            <div class="flex items-center gap-2 rounded border border-app-border bg-white px-2 py-2 dark:border-app-border-dark dark:bg-slate-900">
                <input
                    v-model="props.form.camp_date_start"
                    type="date"
                    :max="props.form.camp_date_end || undefined"
                    class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                />
                <span class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">t/m</span>
                <input
                    v-model="props.form.camp_date_end"
                    type="date"
                    :min="props.form.camp_date_start || undefined"
                    class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                />
            </div>
        </div>
        <div class="space-y-2 sm:col-span-2">
            <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Cover foto (voorpagina PDF)</label>
            <div class="rounded border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900">
                <div v-if="props.coverPreviewUrl" class="mb-3">
                    <img :src="props.coverPreviewUrl" alt="Cover preview" class="h-44 w-full rounded object-cover" />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <input type="file" accept="image/*" class="block w-full text-sm text-app-ink file:mr-3 file:rounded file:border-0 file:bg-brand-blue/10 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-blue dark:text-app-ink-dark dark:file:bg-brand-blue/20 dark:file:text-brand-blue" @change="props.onCoverPhotoSelected" />
                    <button v-if="props.coverPreviewUrl || props.form.existing_cover_photo_url || props.form.cover_photo" type="button" class="btn-action-delete" title="Cover foto verwijderen" aria-label="Cover foto verwijderen" @click="props.removeCoverPhoto">
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
