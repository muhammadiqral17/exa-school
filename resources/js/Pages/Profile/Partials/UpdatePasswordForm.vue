<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header class="mb-8">
            <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tighter">Perbarui <span class="text-yellow-600">Kata Sandi</span></h2>
            <p class="mt-1 text-sm text-gray-500 font-medium">Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.</p>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-8">
            <div>
                <InputLabel for="current_password" value="KATA SANDI SAAT INI" class="text-yellow-600 text-[10px] font-black tracking-[0.3em] ml-2 mb-2" />
                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                />
                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password" value="KATA SANDI BARU" class="text-yellow-600 text-[10px] font-black tracking-[0.3em] ml-2 mb-2" />
                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="KONFIRMASI KATA SANDI" class="text-yellow-600 text-[10px] font-black tracking-[0.3em] ml-2 mb-2" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />
                <InputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>

            <div class="flex items-center gap-4 pt-4">
                <button :disabled="form.processing" class="px-8 py-3 bg-yellow-500 hover:bg-yellow-600 text-black font-black uppercase tracking-widest rounded-xl transition-all disabled:opacity-50 shadow-md shadow-yellow-500/20">
                    Perbarui Kata Sandi
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600 font-bold">Berhasil Diperbarui.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
