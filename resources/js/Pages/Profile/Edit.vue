<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

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
    <Head title="Pengaturan Profil" />

    <AuthenticatedLayout>
        <template #header>
            Pengaturan Profil
        </template>

        <div class="space-y-6 sm:space-y-12 max-w-4xl mx-auto">
            <div class="bg-white/80 backdrop-blur-md border border-green-200/60 shadow-lg shadow-green-500/10 p-6 sm:p-10 rounded-[2rem] sm:rounded-[3rem] relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-green-500/10 blur-[100px] -mr-32 -mt-32"></div>
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                />
            </div>

            <div class="bg-white/80 backdrop-blur-md border border-yellow-200/60 shadow-lg shadow-yellow-500/10 p-6 sm:p-10 rounded-[2rem] sm:rounded-[3rem] relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-yellow-500/10 blur-[100px] -mr-32 -mt-32"></div>
                <UpdatePasswordForm />
            </div>

            <div class="bg-white/80 backdrop-blur-md border border-red-200/60 shadow-lg shadow-red-500/10 p-6 sm:p-10 rounded-[2rem] sm:rounded-[3rem] relative overflow-hidden">
                <DeleteUserForm />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
