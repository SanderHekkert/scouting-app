<script setup>
import { DocumentCheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    canCreateMembers: { type: Boolean, default: false },
    showAddForm: { type: Boolean, default: false },
    form: { type: Object, required: true },
    isBestuurSection: { type: Boolean, default: false },
    isBeversSection: { type: Boolean, default: false },
});

const emit = defineEmits(['submit']);
</script>

<template>
    <form
        v-if="props.canCreateMembers"
        v-show="props.showAddForm"
        class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark"
        @submit.prevent="emit('submit')"
    >
        <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuw contact</h3>
        <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-start">
            <label for="add-member-installed" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Geïnstalleerd
            </label>
            <select
                id="add-member-installed"
                v-model="props.form.installed"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
            >
                <option :value="true">Ja</option>
                <option :value="false">Nee</option>
            </select>

            <template v-if="!props.isBestuurSection && !props.isBeversSection">
                <label for="add-member-gedoopt" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                    Gedoopt
                </label>
                <select
                    id="add-member-gedoopt"
                    v-model="props.form.gedoopt"
                    class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                >
                    <option :value="true">Ja</option>
                    <option :value="false">Nee</option>
                </select>
            </template>

            <label for="add-member-first-name" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Voornaam
            </label>
            <input
                id="add-member-first-name"
                v-model="props.form.first_name"
                type="text"
                autocomplete="given-name"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
            />

            <label for="add-member-last-name" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Achternaam <span class="font-normal text-app-muted dark:text-app-muted-dark">(optioneel)</span>
            </label>
            <input
                id="add-member-last-name"
                v-model="props.form.last_name"
                type="text"
                autocomplete="family-name"
                placeholder="Optioneel"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
            />

            <label for="add-member-birthday" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Geboortedatum
            </label>
            <input
                id="add-member-birthday"
                v-model="props.form.birthday"
                type="date"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
            />

            <label for="add-member-phone-mother" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Telefoon moeder
            </label>
            <input
                id="add-member-phone-mother"
                v-model="props.form.phone_mother"
                type="text"
                autocomplete="tel"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
            />

            <label for="add-member-phone-father" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Telefoon vader
            </label>
            <input
                id="add-member-phone-father"
                v-model="props.form.phone_father"
                type="text"
                autocomplete="tel"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
            />

            <label for="add-member-address" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Adres
            </label>
            <input
                id="add-member-address"
                v-model="props.form.address"
                type="text"
                autocomplete="street-address"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
            />

            <label for="add-member-postal-code" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Postcode
            </label>
            <input
                id="add-member-postal-code"
                v-model="props.form.postal_code"
                type="text"
                autocomplete="postal-code"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
            />

            <label for="add-member-city" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Plaats
            </label>
            <input
                id="add-member-city"
                v-model="props.form.city"
                type="text"
                autocomplete="address-level2"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
            />

            <label for="add-member-email-parents" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                E-mail ouders
            </label>
            <input
                id="add-member-email-parents"
                v-model="props.form.email_parents"
                type="email"
                autocomplete="email"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
            />

            <label for="add-member-bijzonderheden" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Bijzonderheden
            </label>
            <textarea
                id="add-member-bijzonderheden"
                v-model="props.form.bijzonderheden"
                rows="3"
                placeholder="Allergiën, medicatie, dieet, andere aandachtspunten…"
                class="min-h-[5rem] min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
            />

            <span class="hidden sm:block" aria-hidden="true" />
            <div>
                <button
                    type="submit"
                    class="btn-action-save"
                    :disabled="props.form.processing"
                    title="Opslaan"
                    aria-label="Opslaan"
                >
                    <DocumentCheckIcon class="h-5 w-5" />
                </button>
            </div>
        </div>
        <p v-for="err in Object.values(props.form.errors)" :key="String(err)" class="text-sm text-red-400">
            {{ err }}
        </p>
    </form>
</template>
