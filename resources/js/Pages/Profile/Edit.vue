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
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="w-full"
                    />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <UpdatePasswordForm class="w-full" />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <DeleteUserForm class="w-full" />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <div class="w-full">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Account
                        </h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Log uit vanaf je accountpagina.
                        </p>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="mt-4 inline-flex items-center rounded-md border-2 border-gray-900 bg-gray-300 px-4 py-2 text-xs font-semibold uppercase tracking-widest !text-neutral-900 shadow-sm transition duration-150 ease-in-out hover:bg-gray-400 dark:border-gray-900 dark:bg-gray-400 dark:!text-neutral-900 dark:hover:bg-gray-500"
                        >
                            Uitloggen
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
