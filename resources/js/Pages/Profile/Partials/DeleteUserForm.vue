<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tighter">Hapus <span class="text-red-600">Akun</span></h2>
            <p class="mt-1 text-sm text-gray-500 font-medium">Tindakan ini akan menghapus semua sumber daya dan datanya secara permanen.</p>
        </header>

        <DangerButton @click="confirmUserDeletion" class="px-8 py-4 rounded-2xl uppercase tracking-widest font-black">
            Hapus Akun Permanen
        </DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-10 bg-white text-gray-800 rounded-[3rem] border border-red-200/60 shadow-xl shadow-red-500/10">
                <h2 class="text-2xl font-black uppercase tracking-tighter mb-4 text-gray-800">Apakah Anda yakin?</h2>
                <p class="text-gray-500 text-sm mb-8 leading-relaxed">Masukkan kata sandi Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun secara permanen.</p>

                <div class="space-y-4">
                    <InputLabel for="password" value="KATA SANDI KONFIRMASI" class="text-red-600 text-[10px] font-black tracking-[0.3em] ml-2 mb-2" />
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="block w-full"
                        placeholder="••••••••"
                        @keyup.enter="deleteUser"
                    />
                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-10 flex justify-end gap-4">
                    <SecondaryButton @click="closeModal" class="rounded-xl px-6 py-3 uppercase tracking-widest text-[10px] font-bold"> Batal </SecondaryButton>
                    <DangerButton
                        class="rounded-xl px-6 py-3 uppercase tracking-widest text-[10px] font-bold"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Hapus Sekarang
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
