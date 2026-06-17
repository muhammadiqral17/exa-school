<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Pendaftaran Akun" />

        <div class="mb-10 text-center">
            <h2 class="text-2xl font-black text-white uppercase tracking-tighter">Bergabung dengan <span class="text-green-500">ExaSchool</span></h2>
            <p class="text-gray-500 text-xs mt-2 uppercase tracking-widest font-bold">Mulai perjalanan akademik modern Anda</p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="name" value="NAMA LENGKAP" class="text-yellow-500 text-[10px] font-black tracking-[0.3em] ml-2" />

                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full bg-white border-none text-black rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 transition-all font-bold"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Masukkan nama lengkap"
                    />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="ALAMAT EMAIL" class="text-yellow-500 text-[10px] font-black tracking-[0.3em] ml-2" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full bg-white border-none text-black rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 transition-all font-bold"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    placeholder="email@instansi.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <InputLabel for="password" value="KATA SANDI" class="text-yellow-500 text-[10px] font-black tracking-[0.3em] ml-2" />

                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full bg-white border-none text-black rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 transition-all font-bold"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />

                    <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="KONFIRMASI" class="text-yellow-500 text-[10px] font-black tracking-[0.3em] ml-2" />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1 block w-full bg-white border-none text-black rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 transition-all font-bold"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />

                    <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>
            </div>

            <div class="pt-6 space-y-4">
                <button
                    class="w-full py-5 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 text-black font-black uppercase tracking-[0.3em] rounded-2xl shadow-[0_0_30px_rgba(34,197,94,0.3)] transition-all active:scale-[0.98] disabled:opacity-50 text-sm flex items-center justify-center border-none"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    DAFTAR SEKARANG
                </button>

                <div class="text-center">
                    <Link
                        :href="route('login')"
                        class="text-[10px] font-black uppercase tracking-widest text-yellow-500/60 hover:text-yellow-500 transition-colors"
                    >
                        Sudah punya akun? Masuk di sini
                    </Link>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
