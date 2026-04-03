<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Inloggen" />

    <div
        class="relative min-h-screen overflow-hidden bg-gradient-to-br from-slate-950 via-[#0c2847] to-slate-900"
    >
        <!-- Zachte merk-kleurige gloed -->
        <div
            class="pointer-events-none fixed inset-0"
            aria-hidden="true"
        >
            <div
                class="absolute -top-32 right-[-10%] h-[28rem] w-[28rem] rounded-full bg-brand-blue/20 blur-3xl"
            />
            <div
                class="absolute bottom-[-15%] left-[-5%] h-[22rem] w-[22rem] rounded-full bg-brand-red/18 blur-3xl"
            />
            <div
                class="absolute top-[40%] left-[20%] h-64 w-64 rounded-full bg-brand-green/12 blur-3xl"
            />
            <div
                class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_50%_-20%,rgba(0,104,183,0.12),transparent)]"
            />
        </div>

        <div
            class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-10 sm:px-6 lg:px-8"
        >
            <Link
                href="/"
                class="mb-8 flex shrink-0 flex-col items-center gap-3 transition-opacity hover:opacity-90"
            >
                <ApplicationLogo
                    class="h-24 max-h-28 w-auto max-w-[14rem] drop-shadow-lg sm:h-28 sm:max-h-[7.5rem]"
                />
                <span
                    class="text-center text-xs font-medium uppercase tracking-[0.2em] text-brand-yellow-soft/90"
                >
                    Fridtjof Nansen Groep 12
                </span>
            </Link>

            <div
                class="relative w-full max-w-md overflow-hidden rounded-2xl border border-app-border/80 bg-app-panel/95 shadow-2xl shadow-brand-blue/15 backdrop-blur-md dark:border-brand-blue/35 dark:bg-app-panel-dark/95"
            >
                <!-- Dunne strook in logo-kleuren boven de kaart -->
                <div
                    class="h-1 w-full bg-gradient-to-r from-brand-red via-brand-yellow to-brand-blue"
                    aria-hidden="true"
                />

                <div class="px-6 py-8 sm:px-10 sm:py-10">
                    <div class="mb-8 text-center">
                        <h1
                            class="text-2xl font-semibold tracking-tight text-app-ink dark:text-app-ink-dark"
                        >
                            Welkom terug
                        </h1>
                        <p
                            class="mt-2 text-sm text-app-muted dark:text-app-muted-dark"
                        >
                            Log in om verder te gaan
                        </p>
                    </div>

                    <div
                        v-if="status"
                        role="status"
                        class="mb-6 rounded-lg border border-brand-green/30 bg-brand-green/10 px-4 py-3 text-sm font-medium text-brand-green dark:border-brand-green/40 dark:bg-brand-green/15 dark:text-brand-yellow-soft"
                    >
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div>
                            <InputLabel
                                for="email"
                                value="E-mailadres"
                                class="text-app-muted dark:text-app-muted-dark"
                            />

                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1.5 block w-full border-app-border bg-app-panel dark:border-app-border-dark dark:bg-app-canvas-dark"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.email"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="password"
                                value="Wachtwoord"
                                class="text-app-muted dark:text-app-muted-dark"
                            />

                            <TextInput
                                id="password"
                                type="password"
                                class="mt-1.5 block w-full border-app-border bg-app-panel dark:border-app-border-dark dark:bg-app-canvas-dark"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                            />

                            <InputError
                                class="mt-2"
                                :message="form.errors.password"
                            />
                        </div>

                        <div
                            v-if="canResetPassword"
                            class="flex justify-end"
                        >
                            <Link
                                :href="route('password.request')"
                                class="text-sm font-medium text-brand-blue transition-colors hover:text-brand-blue-dark hover:underline dark:text-brand-blue-light"
                            >
                                Wachtwoord vergeten?
                            </Link>
                        </div>

                        <button
                            type="submit"
                            class="relative w-full overflow-hidden rounded-lg bg-brand-red px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-red/30 transition hover:bg-brand-red-dark focus:outline-none focus:ring-2 focus:ring-brand-blue/55 focus:ring-offset-2 focus:ring-offset-app-canvas disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-app-canvas-dark"
                            :disabled="form.processing"
                        >
                            <span class="relative z-10">Inloggen</span>
                        </button>
                    </form>
                </div>
            </div>

            <p class="mt-8 text-center text-xs text-app-muted/90 dark:text-app-muted-dark">
                © {{ new Date().getFullYear() }} Fridtjof Nansen 12
            </p>
        </div>
    </div>
</template>
