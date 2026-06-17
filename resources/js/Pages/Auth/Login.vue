<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

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
    remember: true,
    captcha_answer: '',
});

const num1 = ref(0);
const num2 = ref(0);
const captchaError = ref('');

const generateCaptcha = () => {
    num1.value = Math.floor(Math.random() * 10) + 1;
    num2.value = Math.floor(Math.random() * 10) + 1;
    form.captcha_answer = '';
};

onMounted(() => {
    generateCaptcha();
});

const submit = () => {
    if (parseInt(form.captcha_answer) !== num1.value + num2.value) {
        captchaError.value = 'Jawaban penjumlahan salah. Gagal masuk.';
        generateCaptcha();
        return;
    }
    captchaError.value = '';

    form.post(route('login'), {
        onFinish: () => {
            form.reset('password', 'captcha_answer');
            generateCaptcha();
        },
        onError: () => {
            generateCaptcha();
        }
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-6 text-xs font-black text-green-700 p-4 bg-green-100 rounded-2xl border border-green-300 uppercase tracking-widest text-center">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5" autocomplete="off">
            <div class="space-y-2">
                <InputLabel for="email" value="NIK/NIS/EMAIL" class="text-yellow-600 text-xs font-bold tracking-wider ml-1" />

                <TextInput
                    id="email"
                    type="text"
                    class="mt-1 block w-full bg-white border-none text-black rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 transition-all font-bold placeholder:text-gray-400"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="off"
                    placeholder="nama@sekolah.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="space-y-2">
                <div class="flex justify-between items-center ml-1">
                    <InputLabel for="password" value="KATA SANDI" class="text-yellow-600 text-xs font-bold tracking-wider" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-[10px] font-bold uppercase tracking-wider text-yellow-600/80 hover:text-yellow-500 transition-colors"
                    >
                        Lupa?
                    </Link>
                </div>

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full bg-white border-none text-black rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 transition-all font-bold placeholder:text-gray-400"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="space-y-2">
                <InputLabel for="captcha" :value="`VERIFIKASI: BERAPA HASIL DARI ${num1} + ${num2}?`" class="text-yellow-600 text-xs font-bold tracking-wider ml-1" />
                
                <TextInput
                    id="captcha"
                    type="number"
                    class="mt-1 block w-full bg-white border-none text-black rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 transition-all font-bold placeholder:text-gray-400"
                    v-model="form.captcha_answer"
                    required
                    placeholder="Masukkan jawaban angka"
                />
                
                <InputError class="mt-2" :message="captchaError" v-if="captchaError" />
            </div>

            <div class="flex items-center ml-1">
                <Checkbox name="remember" v-model:checked="form.remember" class="w-5 h-5 rounded-lg bg-white border-gray-300 text-green-500 focus:ring-offset-0 focus:ring-green-500/50" />
                <span class="ms-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Biarkan saya tetap masuk</span>
            </div>

            <div class="pt-4">
                <button
                    class="w-full py-4 bg-green-500 hover:bg-green-600 text-white font-bold uppercase tracking-[0.2em] rounded-2xl transition-all active:scale-[0.98] disabled:opacity-50 text-sm flex items-center justify-center border-none outline-none shadow-lg shadow-green-500/30 hover:shadow-green-500/50 hover:-translate-y-1"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    MASUK
                </button>
            </div>
            
            <div class="text-center">
                <p class="text-xs font-bold text-gray-400 tracking-wider">&copy; {{ new Date().getFullYear() }} ExaSchool</p>
            </div>
        </form>
    </GuestLayout>
</template>

