<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftCircleIcon, CloudArrowUpIcon } from '@heroicons/vue/24/outline';
import { watch } from 'vue';

const props = defineProps({
    preview: { type: Object, default: null },
});

const form = useForm({
    file: null,
});

const confirmForm = useForm({
    token: props.preview?.token || '',
    section: props.preview?.section || '',
    first_name: props.preview?.first_name || '',
    last_name: props.preview?.last_name || '',
    roepnaam: props.preview?.first_name || '',
    address: props.preview?.address || '',
    postal_code: props.preview?.postal_code || '',
    city: props.preview?.city || '',
    birthday: props.preview?.birthday || '',
    phone_mother: props.preview?.phone_mother || '',
    phone_father: props.preview?.phone_father || '',
    email_parents: props.preview?.email_parents || '',
    bijzonderheden: props.preview?.bijzonderheden || '',
});

watch(
    () => props.preview,
    (preview) => {
        if (!preview) return;
        confirmForm.token = preview.token || '';
        confirmForm.section = preview.section || '';
        confirmForm.first_name = preview.first_name || '';
        confirmForm.last_name = preview.last_name || '';
        confirmForm.address = preview.address || '';
        confirmForm.postal_code = preview.postal_code || '';
        confirmForm.city = preview.city || '';
        confirmForm.birthday = preview.birthday || '';
        confirmForm.phone_mother = preview.phone_mother || '';
        confirmForm.phone_father = preview.phone_father || '';
        confirmForm.email_parents = preview.email_parents || '';
        confirmForm.bijzonderheden = preview.bijzonderheden || '';
    },
    { immediate: true },
);

function onFileChange(event) {
    form.file = event?.target?.files?.[0] || null;
}

function submit() {
    form.post(route('admin.health-forms.store'), {
        forceFormData: true,
    });
}

function confirmSubmit() {
    confirmForm.post(route('admin.health-forms.confirm'));
}
</script>

<template>
    <Head title="Gezondheidsformulier uploaden" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Gezondheidsformulier uploaden</h2>
                <Link :href="route('admin.health-forms.index')" class="inline-flex items-center gap-1 rounded border border-app-border px-3 py-2 text-sm text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:text-app-ink-dark">
                    <ArrowLeftCircleIcon class="h-5 w-5" />
                    Terug
                </Link>
            </div>
        </template>

        <div class="space-y-4">
            <form
                v-if="props.preview"
                class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark"
                @submit.prevent="confirmSubmit"
            >
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Controleer gegevens voor opslaan</h3>
                <div class="mt-4 grid gap-4 sm:grid-cols-[11rem_1fr] sm:items-start">
                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Speltak</label>
                    <select v-model="confirmForm.section" class="rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark">
                        <option value="bevers">Bevers</option>
                        <option value="dolfijnen">Dolfijnen</option>
                        <option value="zeeverkenners">Zeeverkenners</option>
                        <option value="wilde_vaart">Wilde Vaart</option>
                        <option value="loodsen">Loodsen</option>
                    </select>

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Roepnaam</label>
                    <input v-model="confirmForm.first_name" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Achternaam</label>
                    <input v-model="confirmForm.last_name" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Adres</label>
                    <input v-model="confirmForm.address" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Postcode</label>
                    <input v-model="confirmForm.postal_code" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Woonplaats</label>
                    <input v-model="confirmForm.city" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Geboortedatum</label>
                    <input v-model="confirmForm.birthday" type="date" class="rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Telefoon moeder</label>
                    <input v-model="confirmForm.phone_mother" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Telefoon vader</label>
                    <input v-model="confirmForm.phone_father" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">E-mailadres ouders</label>
                    <input v-model="confirmForm.email_parents" type="email" class="rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Bijzonderheden</label>
                    <textarea v-model="confirmForm.bijzonderheden" rows="3" class="rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div>
                        <button type="submit" class="inline-flex items-center gap-2 rounded bg-brand-blue px-4 py-2 text-sm font-semibold text-white hover:bg-brand-blue-dark disabled:opacity-60" :disabled="confirmForm.processing">
                            Definitief opslaan
                        </button>
                    </div>
                </div>
                <p v-if="confirmForm.errors.first_name" class="mt-2 text-sm text-red-500">{{ confirmForm.errors.first_name }}</p>
                <p v-if="confirmForm.errors.token" class="mt-1 text-sm text-red-500">{{ confirmForm.errors.token }}</p>
            </form>

            <form class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-[11rem_1fr] sm:items-start">
                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Bestand</label>
                    <label class="group flex cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-brand-blue/35 bg-brand-blue/5 p-6 text-center transition hover:bg-brand-blue/10">
                        <input type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" @change="onFileChange" />
                        <span class="inline-flex flex-col items-center gap-2 text-app-ink dark:text-app-ink-dark">
                            <CloudArrowUpIcon class="h-10 w-10 text-brand-blue" />
                            <span class="text-sm font-semibold">Klik om gezondheidsformulier te uploaden</span>
                            <span class="text-xs text-app-muted dark:text-app-muted-dark">{{ form.file?.name || 'PDF/JPG/PNG (max 10MB)' }}</span>
                        </span>
                    </label>

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div>
                        <button type="submit" class="inline-flex items-center gap-2 rounded bg-brand-blue px-4 py-2 text-sm font-semibold text-white hover:bg-brand-blue-dark disabled:opacity-60" :disabled="form.processing || !form.file">
                            <CloudArrowUpIcon class="h-5 w-5" />
                            Uploaden en preview laden
                        </button>
                    </div>
                </div>

                <div class="mt-5 rounded-lg border border-brand-blue/25 bg-brand-blue/5 p-4 text-sm text-app-ink dark:text-app-ink-dark">
                    Het systeem probeert automatisch uit het formulier te halen:
                    speltak, naam, adres, geboortedatum, telefoonnummers en bijzonderheden.
                    Daarna kun je alles controleren in de preview en pas dan definitief opslaan.
                </div>

                <p v-if="form.errors.file" class="mt-1 text-sm text-red-500">{{ form.errors.file }}</p>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
