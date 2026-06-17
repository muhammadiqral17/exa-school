<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ status: { type: String } });

const form = useForm({ email: '' });
const submit = () => form.post(route('password.email'));
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Password" />

        <div class="mb-6 text-sm text-gray-500 font-medium leading-relaxed">
            Lupa password? Tidak masalah. Masukkan email Anda dan kami akan mengirimkan tautan reset password.
        </div>

        <div v-if="status" class="mb-4 text-xs font-black text-green-700 p-4 bg-green-100 rounded-2xl border border-green-300 uppercase tracking-widest text-center">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="email" value="ALAMAT EMAIL" class="text-yellow-600 text-[10px] font-black tracking-[0.3em] ml-2" />
                <TextInput
                    id="email" type="email"
                    class="mt-1 block w-full bg-white border border-gray-200 text-gray-800 rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 focus:border-green-400 transition-all font-bold placeholder:text-gray-400 shadow-sm"
                    v-model="form.email" required autofocus autocomplete="username" placeholder="nama@sekolah.com" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <button
                class="w-full py-5 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-emerald-400 text-white font-black uppercase tracking-[0.3em] rounded-2xl shadow-lg shadow-green-500/25 transition-all active:scale-[0.98] disabled:opacity-50 text-sm"
                :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Kirim Link Reset Password
            </button>
        </form>
    </GuestLayout>
</template>
