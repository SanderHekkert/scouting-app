<script setup>
import AppShellBackground from '@/Components/AppShellBackground.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import AuthShellCard from '@/Components/AuthShellCard.vue';
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

    <AppShellBackground>
        <div
            class="flex min-h-screen flex-col items-center justify-center px-4 py-10 sm:px-6 lg:px-8"
        >
            <Link
                href="/"
                class="mb-8 flex shrink-0 flex-col items-center gap-3 transition-opacity hover:opacity-90"
            >
                <ApplicationLogo
                    class="h-24 max-h-28 w-auto max-w-[14rem] drop-shadow-lg sm:h-28 sm:max-h-[7.5rem]"
                />
                <span
                    class="text-center text-xs font-medium uppercase tracking-[0.2em] text-white/90"
                >
                    Fridtjof Nansen Groep 12
                </span>
            </Link>

            <AuthShellCard class="mx-auto w-full max-w-md">
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
                    class="mb-6 rounded-lg border border-brand-green/30 bg-brand-green/10 px-4 py-3 text-sm font-medium text-brand-green dark:border-brand-green/40 dark:bg-brand-green/15 dark:text-app-ink-dark"
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
                            class="mt-1.5 block w-full border-app-border bg-white dark:border-app-border-dark dark:bg-app-canvas-dark"
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
                            class="mt-1.5 block w-full border-app-border bg-white dark:border-app-border-dark dark:bg-app-canvas-dark"
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
                        class="relative w-full overflow-hidden rounded-lg bg-brand-red px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-red/30 transition hover:bg-brand-red-dark focus:outline-none focus:ring-2 focus:ring-brand-blue/55 focus:ring-offset-2 focus:ring-offset-white disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-slate-900"
                        :disabled="form.processing"
                    >
                        <span class="relative z-10">Inloggen</span>
                    </button>
                </form>
            </AuthShellCard>

            <p class="mt-8 text-center text-xs text-white/70">
                © {{ new Date().getFullYear() }} Fridtjof Nansen 12
            </p>
        </div>
    </AppShellBackground>
</template>
