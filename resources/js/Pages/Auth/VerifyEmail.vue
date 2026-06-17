<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ status: { type: String } });
const form = useForm({});
const submit = () => form.post(route('verification.send'));
const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Email" />

        <div class="mb-6 text-sm text-gray-500 font-medium leading-relaxed">
            Terima kasih telah mendaftar! Sebelum memulai, harap verifikasi alamat email Anda dengan mengklik tautan yang telah kami kirimkan.
        </div>

        <div v-if="verificationLinkSent" class="mb-4 text-xs font-black text-green-700 p-4 bg-green-100 rounded-2xl border border-green-300 uppercase tracking-widest text-center">
            Tautan verifikasi baru telah dikirimkan ke alamat email Anda.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <button
                class="w-full py-5 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-emerald-400 text-white font-black uppercase tracking-[0.3em] rounded-2xl shadow-lg shadow-green-500/25 transition-all active:scale-[0.98] disabled:opacity-50 text-sm"
                :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Kirim Ulang Email Verifikasi
            </button>

            <Link :href="route('logout')" method="post" as="button"
                class="w-full py-4 text-center text-sm font-bold text-gray-400 hover:text-gray-600 uppercase tracking-widest transition-colors">
                Keluar
            </Link>
        </form>
    </GuestLayout>
</template>
