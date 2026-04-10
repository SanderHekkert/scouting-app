<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    push: {
        type: Object,
        default: () => ({ vapidPublicKey: '', isSubscribed: false }),
    },
});

const pushEnabled = ref(!!props.push?.isSubscribed);
const pushBusy = ref(false);
const pushError = ref('');
const pushMessage = ref('');


function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

async function enablePush() {
    pushError.value = '';
    pushMessage.value = '';
    pushBusy.value = true;

    try {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            throw new Error('Push notificaties worden niet ondersteund op dit apparaat/browser.');
        }
        if (!props.push?.vapidPublicKey) {
            throw new Error('VAPID key ontbreekt op de server.');
        }

        const registration = await navigator.serviceWorker.ready;
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            throw new Error('Meldingen zijn niet toegestaan.');
        }

        const existing = await registration.pushManager.getSubscription();
        const subscription = existing || await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(props.push.vapidPublicKey),
        });

        const json = subscription.toJSON();
        const response = await fetch(route('push.subscriptions.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify(json),
        });

        if (!response.ok) {
            throw new Error('Opslaan van push-abonnement mislukt.');
        }

        pushEnabled.value = true;
        pushMessage.value = 'Pushmeldingen staan aan op dit apparaat.';
    } catch (error) {
        pushError.value = error?.message || 'Kon pushmeldingen niet activeren.';
    } finally {
        pushBusy.value = false;
    }
}

async function disablePush() {
    pushError.value = '';
    pushMessage.value = '';
    pushBusy.value = true;

    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        if (!subscription) {
            pushEnabled.value = false;
            return;
        }

        await fetch(route('push.subscriptions.destroy'), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({ endpoint: subscription.endpoint }),
        });

        await subscription.unsubscribe();
        pushEnabled.value = false;
        pushMessage.value = 'Pushmeldingen uitgezet op dit apparaat.';
    } catch (error) {
        pushError.value = error?.message || 'Kon pushmeldingen niet uitschakelen.';
    } finally {
        pushBusy.value = false;
    }
}

</script>

<template>
    <Head title="Profiel" />

    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-1">
                <h2 class="text-xl font-semibold leading-tight text-app-ink dark:text-app-ink-dark">
                    Profiel
                </h2>
                <p class="text-sm text-app-muted dark:text-app-muted-dark">
                    Beheer je accountgegevens, beveiliging en meldingen.
                </p>
            </div>
        </template>

        <div class="pb-6">
            <div class="mx-auto max-w-6xl space-y-5">
                <div
                    class="surface-brand-top-lg rounded-2xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:p-7"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="w-full"
                    />
                </div>

                <div
                    class="surface-brand-top-lg rounded-2xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:p-7"
                >
                    <UpdatePasswordForm class="w-full" />
                </div>

                <div
                    class="surface-brand-top-lg rounded-2xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:p-7"
                >
                    <DeleteUserForm class="w-full" />
                </div>

                <div class="grid gap-5 lg:grid-cols-2">
                    <div
                        class="surface-brand-top-lg rounded-2xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:p-7"
                    >
                        <div class="w-full">
                            <h3 class="text-lg font-medium text-app-ink dark:text-app-ink-dark">
                                Pushmeldingen
                            </h3>
                            <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                                iPhone: werkt zodra de app op het beginscherm staat. Android: werkt direct in Chrome/Edge na toestemming.
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="rounded bg-brand-blue px-4 py-2 text-sm font-semibold text-white hover:bg-brand-blue-dark disabled:opacity-50"
                                    :disabled="pushBusy || pushEnabled"
                                    @click="enablePush"
                                >
                                    Push aanzetten
                                </button>
                                <button
                                    type="button"
                                    class="rounded border border-app-border px-4 py-2 text-sm font-semibold text-app-ink hover:bg-app-canvas disabled:opacity-50 dark:border-app-border-dark dark:text-app-ink-dark dark:hover:bg-app-canvas-dark"
                                    :disabled="pushBusy || !pushEnabled"
                                    @click="disablePush"
                                >
                                    Push uitzetten
                                </button>
                            </div>

                            <p v-if="pushMessage" class="mt-3 text-sm text-emerald-700 dark:text-emerald-300">{{ pushMessage }}</p>
                            <p v-if="pushError" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ pushError }}</p>
                        </div>
                    </div>

                    <div
                        class="surface-brand-top-lg rounded-2xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:p-7"
                    >
                        <div class="w-full">
                            <h3 class="text-lg font-medium text-app-ink dark:text-app-ink-dark">
                                Account
                            </h3>
                            <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                                Klaar? Log veilig uit op dit apparaat.
                            </p>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="mt-4 inline-flex items-center rounded-md border-2 border-brand-blue bg-transparent px-4 py-2 text-xs font-semibold uppercase tracking-widest text-brand-blue-dark shadow-sm transition duration-150 ease-in-out hover:bg-brand-blue/10 dark:border-brand-blue-light dark:text-brand-blue-light dark:hover:bg-brand-blue/15"
                            >
                                Uitloggen
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
