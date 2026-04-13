<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { BellAlertIcon, BellSlashIcon, MoonIcon, SunIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, onUnmounted, ref } from 'vue';

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
    currentTheme: {
        type: String,
        default: 'light',
    },
});

const pushEnabled = ref(!!props.push?.isSubscribed);
const pushBusy = ref(false);
const pushError = ref('');
const pushMessage = ref('');
const pushSupportIssue = ref('');
const themeBusy = ref(false);
const isDark = ref(props.currentTheme === 'dark');
const themeDragActive = ref(false);
const themeKnobX = ref(0);
const themeDragStartPointerX = ref(0);
const themeDragStartKnobX = ref(0);
const THEME_KNOB_MIN_X = 4;
const THEME_KNOB_MAX_X = 172;
const pushDragActive = ref(false);
const pushKnobX = ref(0);
const pushDragStartPointerX = ref(0);
const pushDragStartKnobX = ref(0);
const PUSH_KNOB_MIN_X = 4;
const PUSH_KNOB_MAX_X = 172;


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

function detectPushSupportIssue() {
    if (typeof window === 'undefined') {
        return 'Pushmeldingen zijn hier niet beschikbaar.';
    }

    if (!window.isSecureContext) {
        return 'Pushmeldingen zijn hier niet beschikbaar. Neem contact op met de beheerder.';
    }

    if (!('Notification' in window)) {
        return 'Deze browser ondersteunt geen notificatie-API.';
    }

    if (!('serviceWorker' in navigator)) {
        return 'Service Workers zijn niet beschikbaar in deze browser/context.';
    }

    if (!('PushManager' in window)) {
        return 'Push API wordt niet ondersteund in deze browser.';
    }

    return '';
}

async function enablePush() {
    pushError.value = '';
    pushMessage.value = '';
    pushSupportIssue.value = '';
    pushBusy.value = true;

    try {
        const supportIssue = detectPushSupportIssue();
        if (supportIssue) {
            pushSupportIssue.value = supportIssue;
            throw new Error(supportIssue);
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

async function setPushEnabled(nextEnabled) {
    if (pushBusy.value) return;
    if (nextEnabled === pushEnabled.value) return;
    if (nextEnabled) {
        await enablePush();
    } else {
        await disablePush();
    }
    syncPushKnobWithState();
}

function applyThemeClass(dark) {
    if (typeof document === 'undefined') return;
    document.documentElement.classList.toggle('dark', !!dark);
}

function setTheme(nextDark) {
    if (themeBusy.value) return;
    if (nextDark === isDark.value) return;
    isDark.value = nextDark;
    applyThemeClass(nextDark);
    themeBusy.value = true;
    router.patch(route('profile.theme.update'), {
        theme_preference: nextDark ? 'dark' : 'light',
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            isDark.value = !nextDark;
            applyThemeClass(!nextDark);
        },
        onFinish: () => {
            themeBusy.value = false;
        },
    });
}

function toggleTheme() {
    setTheme(!isDark.value);
}

function clampThemeKnob(value) {
    return Math.min(Math.max(value, THEME_KNOB_MIN_X), THEME_KNOB_MAX_X);
}

function syncThemeKnobWithState() {
    themeKnobX.value = isDark.value ? THEME_KNOB_MAX_X : THEME_KNOB_MIN_X;
}

function clampPushKnob(value) {
    return Math.min(Math.max(value, PUSH_KNOB_MIN_X), PUSH_KNOB_MAX_X);
}

function syncPushKnobWithState() {
    pushKnobX.value = pushEnabled.value ? PUSH_KNOB_MAX_X : PUSH_KNOB_MIN_X;
}

function startThemeDrag(clientX) {
    if (themeBusy.value) return;
    themeDragActive.value = true;
    themeDragStartPointerX.value = clientX;
    themeDragStartKnobX.value = themeKnobX.value;
}

function moveThemeDrag(clientX) {
    if (!themeDragActive.value) return;
    const delta = clientX - themeDragStartPointerX.value;
    themeKnobX.value = clampThemeKnob(themeDragStartKnobX.value + delta);
}

function endThemeDrag() {
    if (!themeDragActive.value) return;
    themeDragActive.value = false;

    const midpoint = (THEME_KNOB_MIN_X + THEME_KNOB_MAX_X) / 2;
    const nextDark = themeKnobX.value >= midpoint;

    if (nextDark !== isDark.value) {
        setTheme(nextDark);
    } else {
        syncThemeKnobWithState();
    }
}

function startPushDrag(clientX) {
    if (pushBusy.value) return;
    pushDragActive.value = true;
    pushDragStartPointerX.value = clientX;
    pushDragStartKnobX.value = pushKnobX.value;
}

function movePushDrag(clientX) {
    if (!pushDragActive.value) return;
    const delta = clientX - pushDragStartPointerX.value;
    pushKnobX.value = clampPushKnob(pushDragStartKnobX.value + delta);
}

function endPushDrag() {
    if (!pushDragActive.value) return;
    pushDragActive.value = false;
    const midpoint = (PUSH_KNOB_MIN_X + PUSH_KNOB_MAX_X) / 2;
    const nextEnabled = pushKnobX.value >= midpoint;
    if (nextEnabled !== pushEnabled.value) {
        void setPushEnabled(nextEnabled);
    } else {
        syncPushKnobWithState();
    }
}

function onThemeKnobMouseDown(event) {
    startThemeDrag(event.clientX);
}

function onThemeKnobTouchStart(event) {
    const touch = event.touches?.[0];
    if (!touch) return;
    startThemeDrag(touch.clientX);
}

function onPushKnobMouseDown(event) {
    startPushDrag(event.clientX);
}

function onPushKnobTouchStart(event) {
    const touch = event.touches?.[0];
    if (!touch) return;
    startPushDrag(touch.clientX);
}

function onGlobalMouseMove(event) {
    moveThemeDrag(event.clientX);
    movePushDrag(event.clientX);
}

function onGlobalTouchMove(event) {
    const touch = event.touches?.[0];
    if (!touch) return;
    moveThemeDrag(touch.clientX);
    movePushDrag(touch.clientX);
}

function onGlobalPointerUp() {
    endThemeDrag();
    endPushDrag();
}

const themeTrackDark = computed(() => {
    if (!themeDragActive.value) {
        return isDark.value;
    }
    const midpoint = (THEME_KNOB_MIN_X + THEME_KNOB_MAX_X) / 2;
    return themeKnobX.value >= midpoint;
});

const themeKnobStyle = computed(() => ({
    transform: `translateX(${themeKnobX.value}px)`,
}));
const themeKnobIcon = computed(() => (themeTrackDark.value ? MoonIcon : SunIcon));
const themeKnobIconStyle = computed(() => {
    if (!themeDragActive.value) {
        return { transform: 'rotate(0deg)' };
    }
    const ratio = (themeKnobX.value - THEME_KNOB_MIN_X) / (THEME_KNOB_MAX_X - THEME_KNOB_MIN_X);
    return { transform: `rotate(${ratio * 360}deg)` };
});

const pushTrackEnabled = computed(() => {
    if (!pushDragActive.value) {
        return pushEnabled.value;
    }
    const midpoint = (PUSH_KNOB_MIN_X + PUSH_KNOB_MAX_X) / 2;
    return pushKnobX.value >= midpoint;
});

const pushKnobStyle = computed(() => ({
    transform: `translateX(${pushKnobX.value}px)`,
}));
const pushKnobIcon = computed(() => (pushTrackEnabled.value ? BellAlertIcon : BellSlashIcon));
const pushKnobIconStyle = computed(() => {
    if (!pushDragActive.value) {
        return { transform: 'rotate(0deg)' };
    }
    const ratio = (pushKnobX.value - PUSH_KNOB_MIN_X) / (PUSH_KNOB_MAX_X - PUSH_KNOB_MIN_X);
    return { transform: `rotate(${ratio * 360}deg)` };
});

onMounted(() => {
    applyThemeClass(isDark.value);
    syncThemeKnobWithState();
    syncPushKnobWithState();
    pushSupportIssue.value = detectPushSupportIssue();
    window.addEventListener('mousemove', onGlobalMouseMove);
    window.addEventListener('mouseup', onGlobalPointerUp);
    window.addEventListener('touchmove', onGlobalTouchMove, { passive: true });
    window.addEventListener('touchend', onGlobalPointerUp, { passive: true });
    window.addEventListener('touchcancel', onGlobalPointerUp, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('mousemove', onGlobalMouseMove);
    window.removeEventListener('mouseup', onGlobalPointerUp);
    window.removeEventListener('touchmove', onGlobalTouchMove);
    window.removeEventListener('touchend', onGlobalPointerUp);
    window.removeEventListener('touchcancel', onGlobalPointerUp);
});

</script>

<template>
    <Head title="Profiel" />

    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-1">
                <h2 class="text-xl font-semibold leading-tight text-app-ink dark:text-app-ink-dark">
                    Profiel
                </h2>
            </div>
        </template>

        <div class="pb-6">
            <div class="mx-auto max-w-6xl space-y-4">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div
                        class="surface-brand-top rounded-2xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:p-6"
                    >
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="w-full"
                        />
                    </div>

                    <div
                        class="surface-brand-top rounded-2xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:p-6"
                    >
                        <UpdatePasswordForm class="w-full" />
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div
                        class="surface-brand-top rounded-2xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:p-6"
                    >
                        <div class="w-full">
                            <h3 class="text-lg font-medium text-app-ink dark:text-app-ink-dark">
                                Weergave
                            </h3>
                            <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                                Schakel tussen lichte en donkere modus.
                            </p>
                            <div class="mt-4 flex items-center justify-between rounded-xl border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-app-canvas-dark">
                                <div class="flex items-center gap-2 text-sm font-medium text-app-ink dark:text-app-ink-dark">
                                    <SunIcon class="h-5 w-5 text-amber-500" />
                                    <span>Licht</span>
                                </div>
                                <div
                                    class="relative inline-flex h-10 w-52 items-center rounded-xl transition"
                                    :class="themeTrackDark ? 'bg-brand-blue' : 'bg-slate-300 dark:bg-slate-600'"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 cursor-grab items-center justify-center rounded-full bg-white text-slate-700 shadow transition-transform active:cursor-grabbing focus:outline-none focus:ring-2 focus:ring-brand-blue/40 disabled:opacity-60"
                                        :style="themeKnobStyle"
                                        :disabled="themeBusy"
                                        title="Sleep om te wisselen"
                                        aria-label="Sleep om te wisselen"
                                        @mousedown.prevent="onThemeKnobMouseDown"
                                        @touchstart.prevent="onThemeKnobTouchStart"
                                        @keydown.enter.prevent="toggleTheme"
                                        @keydown.space.prevent="toggleTheme"
                                    >
                                        <component :is="themeKnobIcon" class="h-4 w-4 transition-transform" :style="themeKnobIconStyle" />
                                    </button>
                                </div>
                                <div class="flex items-center gap-2 text-sm font-medium text-app-ink dark:text-app-ink-dark">
                                    <span>Donker</span>
                                    <MoonIcon class="h-5 w-5 text-indigo-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="surface-brand-top rounded-2xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:p-6"
                    >
                        <div class="w-full">
                            <h3 class="text-lg font-medium text-app-ink dark:text-app-ink-dark">
                                Pushmeldingen
                            </h3>
                            <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                                Meldingen voor dit apparaat.
                            </p>
                            <p v-if="pushSupportIssue" class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                                {{ pushSupportIssue }}
                            </p>

                            <div class="mt-4 flex items-center justify-between rounded-xl border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-app-canvas-dark">
                                <div class="flex items-center gap-2 text-sm font-medium text-app-ink dark:text-app-ink-dark">
                                    <BellSlashIcon class="h-5 w-5 text-slate-500 dark:text-slate-300" />
                                    <span>Uit</span>
                                </div>
                                <div
                                    class="relative inline-flex h-10 w-52 items-center rounded-xl transition"
                                    :class="pushTrackEnabled ? 'bg-emerald-600' : 'bg-slate-300 dark:bg-slate-600'"
                                >
                                    <button
                                        type="button"
                                        class="inline-flex h-8 w-8 cursor-grab items-center justify-center rounded-full bg-white text-slate-700 shadow transition-transform active:cursor-grabbing focus:outline-none focus:ring-2 focus:ring-emerald-500/40 disabled:opacity-60"
                                        :style="pushKnobStyle"
                                        :disabled="pushBusy || !!pushSupportIssue"
                                        title="Sleep om push te wisselen"
                                        aria-label="Sleep om push te wisselen"
                                        @mousedown.prevent="onPushKnobMouseDown"
                                        @touchstart.prevent="onPushKnobTouchStart"
                                        @keydown.enter.prevent="setPushEnabled(!pushEnabled)"
                                        @keydown.space.prevent="setPushEnabled(!pushEnabled)"
                                    >
                                        <component :is="pushKnobIcon" class="h-4 w-4 transition-transform" :style="pushKnobIconStyle" />
                                    </button>
                                </div>
                                <div class="flex items-center gap-2 text-sm font-medium text-app-ink dark:text-app-ink-dark">
                                    <span>Aan</span>
                                    <BellAlertIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-300" />
                                </div>
                            </div>

                            <p v-if="pushMessage" class="mt-3 text-sm text-emerald-700 dark:text-emerald-300">{{ pushMessage }}</p>
                            <p v-if="pushError" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ pushError }}</p>
                        </div>
                    </div>
                </div>

                <div
                    class="surface-brand-top rounded-2xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:p-6"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-medium text-app-ink dark:text-app-ink-dark">
                                Account
                            </h3>
                            <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                                Log uit op dit apparaat.
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
                </div>

                <div
                    class="surface-brand-top rounded-2xl border border-red-300/60 bg-red-50/40 p-5 shadow-sm dark:border-red-500/40 dark:bg-red-950/20 sm:p-6"
                >
                    <DeleteUserForm class="w-full" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
