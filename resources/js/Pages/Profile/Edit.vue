<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-app-ink dark:text-app-ink-dark"
            >
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div
                    class="surface-brand-top-lg border border-app-border bg-app-panel p-4 shadow sm:rounded-lg sm:p-8 dark:border-app-border-dark dark:bg-app-panel-dark"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="w-full"
                    />
                </div>

                <div
                    class="surface-brand-top-lg border border-app-border bg-app-panel p-4 shadow sm:rounded-lg sm:p-8 dark:border-app-border-dark dark:bg-app-panel-dark"
                >
                    <UpdatePasswordForm class="w-full" />
                </div>

                <div
                    class="surface-brand-top-lg border border-app-border bg-app-panel p-4 shadow sm:rounded-lg sm:p-8 dark:border-app-border-dark dark:bg-app-panel-dark"
                >
                    <DeleteUserForm class="w-full" />
                </div>

                <div
                    class="surface-brand-top-lg border border-app-border bg-app-panel p-4 shadow sm:rounded-lg sm:p-8 dark:border-app-border-dark dark:bg-app-panel-dark"
                >
                    <div class="w-full">
                        <h3 class="text-lg font-medium text-app-ink dark:text-app-ink-dark">
                            Account
                        </h3>
                        <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                            Log uit vanaf je accountpagina.
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
    </AuthenticatedLayout>
</template>
