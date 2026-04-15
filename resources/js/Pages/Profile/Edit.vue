<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProfileAccountPanel from './Partials/ProfileAccountPanel.vue';
import ProfileAppearancePanel from './Partials/ProfileAppearancePanel.vue';
import ProfilePushNotificationsPanel from './Partials/ProfilePushNotificationsPanel.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, router } from '@inertiajs/vue3';
import { BellAlertIcon, BellSlashIcon, MoonIcon, SunIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    browserSessions: {
        type: Array,
        default: () => [],
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
    } catch (error) {
        pushError.value = error?.message || 'Kon pushmeldingen niet uitschakelen.';
    } finally {
        pushBusy.value = false;
    }
}

async function syncPushStateFromBrowser() {
    const supportIssue = detectPushSupportIssue();
    if (supportIssue) {
        pushEnabled.value = false;
        return;
    }

    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.getSubscription();
        pushEnabled.value = !!subscription;
    } catch {
        pushEnabled.value = false;
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
    pushSupportIssue.value = detectPushSupportIssue();
    void syncPushStateFromBrowser().finally(() => {
        syncPushKnobWithState();
    });
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
                    <ProfileAppearancePanel
                        :theme-track-dark="themeTrackDark"
                        :theme-knob-style="themeKnobStyle"
                        :theme-busy="themeBusy"
                        :theme-knob-icon="themeKnobIcon"
                        :theme-knob-icon-style="themeKnobIconStyle"
                        :on-theme-knob-mouse-down="onThemeKnobMouseDown"
                        :on-theme-knob-touch-start="onThemeKnobTouchStart"
                        :toggle-theme="toggleTheme"
                    />

                    <ProfilePushNotificationsPanel
                        :push-support-issue="pushSupportIssue"
                        :push-track-enabled="pushTrackEnabled"
                        :push-knob-style="pushKnobStyle"
                        :push-busy="pushBusy"
                        :push-enabled="pushEnabled"
                        :push-knob-icon="pushKnobIcon"
                        :push-knob-icon-style="pushKnobIconStyle"
                        :push-message="pushMessage"
                        :push-error="pushError"
                        :on-push-knob-mouse-down="onPushKnobMouseDown"
                        :on-push-knob-touch-start="onPushKnobTouchStart"
                        :set-push-enabled="setPushEnabled"
                    />
                </div>

                <ProfileAccountPanel
                    :browser-sessions="browserSessions"
                    :status="status"
                />

                <div
                    class="surface-brand-top rounded-2xl border border-red-300/60 bg-red-50/40 p-5 shadow-sm dark:border-red-500/40 dark:bg-red-950/20 sm:p-6"
                >
                    <DeleteUserForm class="w-full" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
