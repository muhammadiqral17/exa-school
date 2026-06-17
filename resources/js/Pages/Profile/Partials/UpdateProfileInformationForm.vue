<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    _method: 'patch',
    name: user.name,
    email: user.email,
    avatar: null,
});

const getAvatarUrl = (avatarPath) => {
    if (!avatarPath) return null;
    if (avatarPath.startsWith('http')) return avatarPath;
    const filename = avatarPath.includes('/') ? avatarPath.split('/').pop() : avatarPath;
    return `/foto-profile/${filename}`;
};

const avatarPreview = ref(getAvatarUrl(user.avatar));

const handleAvatarChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.avatar = file;
        avatarPreview.value = URL.createObjectURL(file);
    }
};
</script>

<template>
    <section>
        <header class="mb-8">
            <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tighter">Informasi <span class="text-green-600">Profil</span></h2>
            <p class="mt-1 text-sm text-gray-500 font-medium">Perbarui informasi profil dan alamat email akun Anda.</p>
        </header>

        <form @submit.prevent="form.post(route('profile.update'))" class="space-y-8" enctype="multipart/form-data">
            <!-- Avatar Upload -->
            <div class="flex items-center gap-6 bg-green-50/50 p-6 rounded-3xl border border-green-100">
                <div class="relative w-24 h-24 rounded-full overflow-hidden bg-white border-4 border-green-200 shrink-0 shadow-sm">
                    <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
                    <svg v-else class="w-full h-full text-gray-300 p-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </div>
                <div class="flex-1">
                    <InputLabel for="avatar" value="FOTO PROFIL" class="text-green-700 text-[10px] font-black tracking-[0.3em] mb-2" />
                    <input type="file" id="avatar" @change="handleAvatarChange" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-6 file:rounded-full file:border-0 file:text-xs file:font-black file:uppercase file:tracking-widest file:bg-green-500 file:text-white hover:file:bg-green-600 transition-all cursor-pointer" />
                    <InputError class="mt-2" :message="form.errors.avatar" />
                </div>
            </div>

            <!-- Read-only Info: Role, NIS, Class -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <InputLabel value="PERAN (ROLE)" class="text-gray-500 text-[10px] font-black tracking-[0.3em] ml-2 mb-2" />
                    <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-700 font-bold uppercase tracking-wider text-sm shadow-sm">{{ user.role === 'admin' ? 'Administrator' : (user.role === 'guru' ? 'Guru' : 'Siswa') }}</div>
                </div>
                <div v-if="user.nis_nik">
                    <InputLabel value="NIS / NIP" class="text-gray-500 text-[10px] font-black tracking-[0.3em] ml-2 mb-2" />
                    <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-700 font-bold uppercase tracking-wider text-sm shadow-sm">{{ user.nis_nik }}</div>
                </div>
                <div v-if="user.class">
                    <InputLabel value="KELAS" class="text-gray-500 text-[10px] font-black tracking-[0.3em] ml-2 mb-2" />
                    <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-700 font-bold uppercase tracking-wider text-sm shadow-sm">{{ user.class }}</div>
                </div>
            </div>

            <div>
                <InputLabel for="name" value="NAMA LENGKAP" class="text-green-700 text-[10px] font-black tracking-[0.3em] ml-2 mb-2" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="ALAMAT EMAIL" class="text-green-700 text-[10px] font-black tracking-[0.3em] ml-2 mb-2" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-200">
                    Alamat email Anda belum terverifikasi.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-yellow-500 underline hover:text-yellow-400"
                    >
                        Klik di sini untuk mengirim ulang email verifikasi.
                    </Link>
                </p>

                <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-400">
                    Tautan verifikasi baru telah dikirim ke alamat email Anda.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-4">
                <button :disabled="form.processing" class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-black uppercase tracking-widest rounded-xl transition-all disabled:opacity-50 shadow-md shadow-green-600/20">
                    Simpan Perubahan
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-400 font-bold">Tersimpan.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
