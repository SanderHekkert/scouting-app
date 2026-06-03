<script setup>
import { DocumentCheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    canCreateLeaders: { type: Boolean, default: false },
    showAddForm: { type: Boolean, default: false },
    form: { type: Object, required: true },
});

const emit = defineEmits(['submit']);
</script>

<template>
    <form
        v-if="props.canCreateLeaders"
        v-show="props.showAddForm"
        class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark"
        @submit.prevent="emit('submit')"
    >
        <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuwe leiding</h3>
        <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-start">
            <label for="add-leader-first-name" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Voornaam
            </label>
            <input
                id="add-leader-first-name"
                v-model="props.form.first_name"
                type="text"
                autocomplete="given-name"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark"
            />

            <label for="add-leader-last-name" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Achternaam
            </label>
            <input
                id="add-leader-last-name"
                v-model="props.form.last_name"
                type="text"
                autocomplete="family-name"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark"
            />

            <label for="add-leader-address" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Adres
            </label>
            <input
                id="add-leader-address"
                v-model="props.form.address"
                type="text"
                autocomplete="street-address"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark"
            />

            <label for="add-leader-postal" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Postcode
            </label>
            <input
                id="add-leader-postal"
                v-model="props.form.postal_code"
                type="text"
                autocomplete="postal-code"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark"
            />

            <label for="add-leader-city" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Plaats
            </label>
            <input
                id="add-leader-city"
                v-model="props.form.city"
                type="text"
                autocomplete="address-level2"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark"
            />

            <label for="add-leader-birthday" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Geboortedatum
            </label>
            <input
                id="add-leader-birthday"
                v-model="props.form.birthday"
                type="date"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
            />

            <label for="add-leader-phone" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Telefoonnummer
            </label>
            <input
                id="add-leader-phone"
                v-model="props.form.phone_number"
                type="text"
                autocomplete="tel"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark"
            />

            <label for="add-leader-emergency-contact" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Noodcontact
            </label>
            <input
                id="add-leader-emergency-contact"
                v-model="props.form.emergency_contact"
                type="text"
                autocomplete="tel"
                placeholder="Bijv. Ouder/verzorger + telefoon"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark"
            />

            <label for="add-leader-email" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                E-mail
            </label>
            <input
                id="add-leader-email"
                v-model="props.form.email"
                type="email"
                autocomplete="email"
                class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark"
            />

            <label for="add-leader-bijz" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                Bijzonderheden
            </label>
            <textarea
                id="add-leader-bijz"
                v-model="props.form.bijzonderheden"
                rows="3"
                placeholder="Allergiën, medicatie, andere aandachtspunten…"
                class="min-h-[5rem] min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark"
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
