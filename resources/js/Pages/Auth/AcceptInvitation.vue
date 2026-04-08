<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    invitation: { type: Object, required: true },
});

const form = useForm({
    first_name: '',
    last_name: '',
    address: '',
    postal_code: '',
    city: '',
    birthday: '',
    phone_number: '',
    bijzonderheden: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('invitations.complete', props.invitation.token));
}
</script>

<template>
    <GuestLayout>
        <Head title="Uitnodiging accepteren" />
        <form class="space-y-4" @submit.prevent="submit">
            <h1 class="text-lg font-semibold text-app-ink dark:text-app-ink-dark">Account aanmaken via uitnodiging</h1>
            <p class="text-sm text-app-muted dark:text-app-muted-dark">E-mail: <strong>{{ invitation.email }}</strong></p>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <InputLabel for="first_name" value="Voornaam" />
                    <TextInput id="first_name" v-model="form.first_name" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.first_name" />
                </div>
                <div>
                    <InputLabel for="last_name" value="Achternaam" />
                    <TextInput id="last_name" v-model="form.last_name" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>
                <div>
                    <InputLabel for="address" value="Adres" />
                    <TextInput id="address" v-model="form.address" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.address" />
                </div>
                <div>
                    <InputLabel for="postal_code" value="Postcode" />
                    <TextInput id="postal_code" v-model="form.postal_code" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.postal_code" />
                </div>
                <div>
                    <InputLabel for="city" value="Woonplaats" />
                    <TextInput id="city" v-model="form.city" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.city" />
                </div>
                <div>
                    <InputLabel for="birthday" value="Geboortedatum" />
                    <TextInput id="birthday" v-model="form.birthday" type="date" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.birthday" />
                </div>
                <div>
                    <InputLabel for="phone_number" value="Telefoonnummer" />
                    <TextInput id="phone_number" v-model="form.phone_number" class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.phone_number" />
                </div>
                <div>
                    <InputLabel for="password" value="Wachtwoord" />
                    <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
                <div>
                    <InputLabel for="password_confirmation" value="Herhaal wachtwoord" />
                    <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" required />
                </div>
            </div>

            <div>
                <InputLabel for="bijzonderheden" value="Bijzonderheden" />
                <textarea id="bijzonderheden" v-model="form.bijzonderheden" rows="3" class="mt-1 block w-full rounded-md border-slate-300 text-black shadow-sm focus:border-brand-blue focus:ring-brand-blue dark:border-slate-600 dark:bg-slate-900 dark:text-black" />
                <InputError class="mt-2" :message="form.errors.bijzonderheden" />
            </div>

            <PrimaryButton :disabled="form.processing">Account aanmaken</PrimaryButton>
        </form>
    </GuestLayout>
</template>
