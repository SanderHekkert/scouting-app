<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ComputerDesktopIcon, DevicePhoneMobileIcon } from '@heroicons/vue/24/outline';
import { computed, nextTick, ref } from 'vue';

const props = defineProps({
    browserSessions: {
        type: Array,
        default: () => [],
    },
    status: {
        type: String,
        default: '',
    },
});

const confirmingLogout = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const hasOtherSessions = computed(() => props.browserSessions.some((session) => !session.is_current_device));

const confirmLogout = () => {
    confirmingLogout.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const logoutOtherBrowserSessions = () => {
    form.delete(route('profile.browser-sessions.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset('password'),
    });
};

const closeModal = () => {
    confirmingLogout.value = false;
    form.clearErrors();
    form.reset('password');
};

const formatLastActive = (lastActive) => {
    if (!lastActive) {
        return 'Onbekend';
    }

    const date = new Date(lastActive);
    if (Number.isNaN(date.getTime())) {
        return 'Onbekend';
    }

    return new Intl.DateTimeFormat('nl-NL', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
};
</script>

<template>
    <div class="surface-brand-top rounded-2xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-medium text-app-ink dark:text-app-ink-dark">
                    Account
                </h3>
                <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                    Bekijk en verwijder andere ingelogde apparaten.
                </p>
            </div>
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="inline-flex min-h-11 items-center rounded-md border-2 border-brand-blue bg-transparent px-4 py-2.5 text-sm font-semibold uppercase tracking-wide text-brand-blue-dark shadow-sm transition hover:bg-brand-blue/10 dark:border-brand-blue-light dark:text-brand-blue-light dark:hover:bg-brand-blue/20"
            >
                Uitloggen
            </Link>
        </div>

        <div class="mt-5 space-y-3">
            <div
                v-for="session in browserSessions"
                :key="session.id"
                class="rounded-xl border border-app-border/80 bg-app-canvas/60 p-3 dark:border-app-border-dark/80 dark:bg-app-canvas-dark/30"
            >
                <div class="flex items-start gap-3">
                    <component
                        :is="session.user_agent?.toLowerCase().includes('mobile') || session.user_agent?.toLowerCase().includes('android') || session.user_agent?.toLowerCase().includes('iphone') ? DevicePhoneMobileIcon : ComputerDesktopIcon"
                        class="mt-0.5 h-5 w-5 text-app-muted dark:text-app-muted-dark"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-app-ink dark:text-app-ink-dark">
                            {{ session.user_agent || 'Onbekend apparaat' }}
                        </p>
                        <p class="mt-1 text-xs text-app-muted dark:text-app-muted-dark">
                            IP: {{ session.ip_address || 'Onbekend' }} · Laatst actief: {{ formatLastActive(session.last_active) }}
                        </p>
                        <p
                            v-if="session.is_current_device"
                            class="mt-2 inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300"
                        >
                            Dit apparaat
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button
                type="button"
                class="inline-flex min-h-11 items-center rounded-md border border-brand-red/70 bg-brand-red px-4 py-2.5 text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-brand-red-dark disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="!hasOtherSessions"
                @click="confirmLogout"
            >
                Andere apparaten uitloggen
            </button>
            <p v-if="!hasOtherSessions" class="text-xs text-app-muted dark:text-app-muted-dark">
                Er zijn geen andere actieve apparaten gevonden.
            </p>
        </div>

        <p
            v-if="status === 'other-browser-sessions-logged-out'"
            class="mt-3 text-sm font-medium text-green-600 dark:text-green-400"
        >
            Andere ingelogde apparaten zijn uitgelogd.
        </p>
    </div>

    <Modal :show="confirmingLogout" @close="closeModal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-app-ink dark:text-app-ink-dark">
                Andere apparaten uitloggen?
            </h2>

            <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                Voer je wachtwoord in om alle andere actieve sessies te beëindigen.
            </p>

            <div class="mt-6">
                <InputLabel for="logout_other_password" value="Wachtwoord" />
                <TextInput
                    id="logout_other_password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                    @keyup.enter="logoutOtherBrowserSessions"
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <SecondaryButton @click="closeModal">
                    Annuleren
                </SecondaryButton>

                <button
                    type="button"
                    class="inline-flex min-h-11 items-center rounded-md border border-brand-red/70 bg-brand-red px-4 py-2.5 text-sm font-semibold uppercase tracking-wide text-white transition hover:bg-brand-red-dark disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="form.processing"
                    @click="logoutOtherBrowserSessions"
                >
                    Andere apparaten uitloggen
                </button>
            </div>
        </div>
    </Modal>
</template>
