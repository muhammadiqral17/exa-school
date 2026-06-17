<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    subjects: Array, 
});

const standardSubjects = [
    { category: 'AGAMA', items: [
        { name: "FIQIH (FIQ)", code: "FIQ" },
        { name: "AKIDAH AKHLAK (AA)", code: "AA" },
        { name: "AL-QUR'AN DAN HADIST (QH)", code: "QH" },
        { name: "BAHASA ARAB (BA)", code: "BA" },
        { name: "SEJARAH KEBUDAYAAN ISLAM (SKI)", code: "SKI" },
    ]},
    { category: 'UMUM', items: [
        { name: "MATEMATIKA (MTK)", code: "MTK" },
        { name: "ILMU PENGETAHUAN ALAM (IPA)", code: "IPA" },
        { name: "ILMU PENGETAHUAN SOSIAL (IPS)", code: "IPS" },
        { name: "BAHASA INDONESIA (BI)", code: "BI" },
        { name: "BAHASA INGGRIS (B.ING)", code: "B.ING" },
        { name: "PENDIDIKAN JASMANI OLAHRAGA DAN KESEHATAN (PENJAS)", code: "PENJAS" },
        { name: "PENDIDIKAN PANCASILA DAN KEWARGANEGARAAN (PPKN)", code: "PPKN" },
        { name: "SENI BUDAYA (SBK)", code: "SBK" },
        { name: "TEKNOLOGI INFORMASI DAN KOMUNIKASI (TIK)", code: "TIK" },
    ]}
];

const form = useForm({
    name: '', 
    email: '', 
    password: '', 
    password_confirmation: '', 
    role: 'siswa', 
    nis_nik: '', 
    class: '',
    classes: [''], // For multiple teaching classes
    tahun_ajaran: '',
    manual_subjects: [{ name: '', code: '', is_manual: false }], 
});

const handleSubjectChange = (index) => {
    const subject = form.manual_subjects[index];
    if (subject.name === 'MANUAL') {
        subject.is_manual = true;
        subject.name = '';
        subject.code = '';
    } else {
        subject.is_manual = false;
        // Find the code from standardSubjects
        let found = null;
        standardSubjects.forEach(cat => {
            const item = cat.items.find(i => i.name === subject.name);
            if (item) found = item;
        });
        if (found) {
            subject.code = found.code;
        }
    }
};

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const page = usePage();
const flashSuccess = ref(page.props.flash?.success || '');

watch(() => page.props.flash?.success, (newVal) => {
    flashSuccess.value = newVal || '';
    if (newVal) setTimeout(() => { flashSuccess.value = ''; }, 5000);
});

const addSubjectField = () => {
    if (form.manual_subjects.length < 5) {
        form.manual_subjects.push({ name: '', code: '', is_manual: false });
    }
};

const removeSubjectField = (index) => {
    if (form.manual_subjects.length > 1) {
        form.manual_subjects.splice(index, 1);
    }
};

const addClassField = () => {
    if (form.classes.length < 10) {
        form.classes.push('');
    }
};

const removeClassField = (index) => {
    if (form.classes.length > 1) {
        form.classes.splice(index, 1);
    }
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        class: data.role === 'guru' ? data.classes.filter(c => c).join(', ') : data.class,
        manual_subjects: data.role === 'guru' ? data.manual_subjects : null
    })).post(route('admin.users.store'), {
        onSuccess: () => {
            form.reset('password', 'password_confirmation', 'name', 'email', 'nis_nik', 'class', 'tahun_ajaran');
            form.manual_subjects = [{ name: '', code: '', is_manual: false }];
            form.classes = [''];
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Manajemen User" />
        <template #header>
            Manajemen User: <span class="text-gradient-gy capitalize">Admin</span>
        </template>

        <div class="max-w-4xl mx-auto space-y-8 pb-20">
            
            <div v-if="flashSuccess" class="success-card p-6 rounded-2xl border-l-4 border-green-500 flex items-center gap-4 animate-fade-in-down">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <div>
                    <h3 class="text-green-700 font-bold uppercase tracking-widest text-sm">Berhasil</h3>
                    <p class="text-gray-700 font-medium">{{ flashSuccess }}</p>
                </div>
            </div>

            <div class="form-card p-8 sm:p-12 rounded-[2.5rem] relative overflow-hidden">
                <div class="absolute top-0 right-0 w-80 h-80 bg-green-300/20 blur-[100px] -mr-40 -mt-40 pointer-events-none"></div>
                
                <div class="mb-10 text-center sm:text-left">
                    <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tighter">Registrasi <span class="text-green-600">Manual</span></h2>
                    <p class="text-gray-400 text-xs mt-2 uppercase tracking-widest font-bold">Tambahkan Admin, Guru atau Siswa ke dalam sistem ExaSchool</p>
                </div>

                <form @submit.prevent="submit" class="space-y-8 relative z-10">
                    
                    <!-- Role Selection -->
                    <div class="space-y-4">
                        <InputLabel value="PERAN PENGGUNA (ROLE)" class="text-yellow-600 text-[10px] font-black tracking-[0.3em] ml-2" />
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <label class="relative cursor-pointer transition-all">
                                <input type="radio" v-model="form.role" value="siswa" class="sr-only peer" name="role">
                                <div class="p-6 rounded-2xl border bg-white border-gray-200 peer-checked:bg-green-50 peer-checked:border-green-500 hover:border-green-300 transition-all text-center shadow-sm h-full">
                                    <div class="w-10 h-10 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                    </div>
                                    <span class="block text-[10px] font-black uppercase tracking-widest text-gray-700">Siswa</span>
                                </div>
                            </label>
                            
                            <label class="relative cursor-pointer transition-all">
                                <input type="radio" v-model="form.role" value="guru" class="sr-only peer" name="role">
                                <div class="p-6 rounded-2xl border bg-white border-gray-200 peer-checked:bg-yellow-50 peer-checked:border-yellow-500 hover:border-yellow-300 transition-all text-center shadow-sm h-full">
                                    <div class="w-10 h-10 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" /></svg>
                                    </div>
                                    <span class="block text-[10px] font-black uppercase tracking-widest text-gray-700">Guru</span>
                                </div>
                            </label>

                            <label class="relative cursor-pointer transition-all">
                                <input type="radio" v-model="form.role" value="admin" class="sr-only peer" name="role">
                                <div class="p-6 rounded-2xl border bg-white border-gray-200 peer-checked:bg-blue-50 peer-checked:border-blue-500 hover:border-blue-300 transition-all text-center shadow-sm h-full">
                                    <div class="w-10 h-10 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                    </div>
                                    <span class="block text-[10px] font-black uppercase tracking-widest text-gray-700">Admin</span>
                                </div>
                            </label>
                        </div>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>

                    <!-- Manual Subject Input (Only for Guru) -->
                    <div v-if="form.role === 'guru'" class="space-y-4 animate-fade-in-down p-6 bg-yellow-50/50 rounded-[2rem] border border-yellow-200/50">
                        <div class="flex justify-between items-center ml-2">
                            <InputLabel value="INPUT MATA PELAJARAN (1-5)" class="text-yellow-600 text-[10px] font-black tracking-[0.3em]" />
                            <button type="button" @click="addSubjectField" v-if="form.manual_subjects.length < 5" class="text-[10px] font-black text-green-600 hover:text-green-700 flex items-center gap-1 uppercase tracking-widest">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Tambah Baris
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div v-for="(subject, index) in form.manual_subjects" :key="index" class="space-y-1 animate-fade-in-down">
                                <div class="flex flex-col sm:flex-row gap-3 items-start">
                                    <div class="flex-1 w-full">
                                        <select v-if="!subject.is_manual" v-model="subject.name" @change="handleSubjectChange(index)"
                                            class="w-full bg-white border-gray-200 text-xs font-bold rounded-xl py-3 px-4 focus:ring-green-500/20" required>
                                            <option value="" disabled>Pilih Mata Pelajaran</option>
                                            <optgroup v-for="cat in standardSubjects" :key="cat.category" :label="`KATEGORI ${cat.category}`">
                                                <option v-for="item in cat.items" :key="item.name" :value="item.name">{{ item.name }}</option>
                                            </optgroup>
                                            <option value="MANUAL" class="text-green-600 font-black">+ INPUT MANUAL (LAINNYA)</option>
                                        </select>
                                        <div v-else class="flex gap-2 w-full">
                                            <TextInput type="text" v-model="subject.name" 
                                                class="flex-1 bg-white border-green-400 text-xs font-bold rounded-xl py-3 px-4 focus:ring-green-500/20" 
                                                placeholder="Nama Mapel Manual..." required />
                                            <button type="button" @click="subject.is_manual = false; subject.name = ''; subject.code = ''" class="text-[10px] font-bold text-gray-400 hover:text-red-500">Batal</button>
                                        </div>
                                    </div>
                                    <div class="w-full sm:w-32">
                                        <TextInput type="text" v-model="subject.code" 
                                            class="w-full bg-white border-gray-200 text-xs font-bold rounded-xl py-3 px-4 focus:ring-green-500/20" 
                                            placeholder="Kode (e.g. MTK-01)" required />
                                    </div>
                                    <button type="button" @click="removeSubjectField(index)" v-if="form.manual_subjects.length > 1" class="p-3 text-red-400 hover:text-red-600 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                                <div class="flex gap-3 px-1">
                                    <InputError class="flex-1" :message="form.errors[`manual_subjects.${index}.name`]" />
                                    <InputError class="w-full sm:w-32" :message="form.errors[`manual_subjects.${index}.code`]" />
                                    <div v-if="form.manual_subjects.length > 1" class="hidden sm:block w-11"></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-400 font-bold ml-2 italic">*Mata pelajaran akan otomatis terdaftar dan terhubung ke guru ini.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="nis_nik" :value="form.role === 'siswa' ? 'NIS (NOMOR INDUK SISWA)' : (form.role === 'guru' ? 'NIK (NOMOR INDUK KEPENDUDUKAN)' : 'ID ADMIN')" class="text-yellow-600 text-[10px] font-black tracking-[0.3em] ml-2" />
                            <TextInput id="nis_nik" type="text"
                                class="mt-1 block w-full bg-white border border-gray-200 text-gray-800 rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 focus:border-green-400 transition-all font-bold placeholder:text-gray-400 shadow-sm"
                                v-model="form.nis_nik" :placeholder="form.role === 'siswa' ? 'Contoh: 123456789' : 'Contoh: 198001012005011001'" />
                            <InputError class="mt-2" :message="form.errors.nis_nik" />
                        </div>

                        <div>
                            <InputLabel for="tahun_ajaran" value="TAHUN AJARAN" class="text-yellow-600 text-[10px] font-black tracking-[0.3em] ml-2" />
                            <TextInput id="tahun_ajaran" type="text"
                                class="mt-1 block w-full bg-white border border-gray-200 text-gray-800 rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 focus:border-green-400 transition-all font-bold placeholder:text-gray-400 shadow-sm"
                                v-model="form.tahun_ajaran" placeholder="Contoh: 2023/2024" />
                            <InputError class="mt-2" :message="form.errors.tahun_ajaran" />
                        </div>
                    </div>

                        <div v-if="form.role !== 'admin'">
                            <div class="flex justify-between items-center ml-2 mb-1">
                                <InputLabel for="class" :value="form.role === 'guru' ? 'KELAS MENGAJAR' : 'KELAS'" class="text-yellow-600 text-[10px] font-black tracking-[0.3em]" />
                                <button v-if="form.role === 'guru'" type="button" @click="addClassField" class="text-[10px] font-black text-green-600 hover:text-green-700 flex items-center gap-1 uppercase tracking-widest">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    Tambah Kelas
                                </button>
                            </div>

                            <div v-if="form.role === 'guru'" class="space-y-3">
                                <div v-for="(cls, index) in form.classes" :key="index" class="flex gap-2 items-center animate-fade-in-down">
                                    <select v-model="form.classes[index]" 
                                        class="flex-1 bg-white border border-gray-200 text-gray-800 rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 focus:border-green-400 transition-all font-bold shadow-sm">
                                        <option value="" disabled>Pilih Kelas</option>
                                        <option value="7">Kelas 7</option>
                                        <option value="8">Kelas 8</option>
                                        <option value="9">Kelas 9</option>
                                        <option value="7A">7A</option><option value="7B">7B</option><option value="7C">7C</option><option value="7D">7D</option>
                                        <option value="8A">8A</option><option value="8B">8B</option><option value="8C">8C</option><option value="8D">8D</option>
                                        <option value="9A">9A</option><option value="9B">9B</option><option value="9C">9C</option><option value="9D">9D</option>
                                    </select>
                                    <button type="button" @click="removeClassField(index)" v-if="form.classes.length > 1" class="p-3 text-red-400 hover:text-red-600 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>

                            <select v-else id="class" v-model="form.class" 
                                class="mt-1 block w-full bg-white border border-gray-200 text-gray-800 rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 focus:border-green-400 transition-all font-bold shadow-sm">
                                <option value="" disabled>Pilih Kelas</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.class" />
                        </div>

                    <div>
                        <InputLabel for="name" value="NAMA LENGKAP" class="text-yellow-600 text-[10px] font-black tracking-[0.3em] ml-2" />
                        <TextInput id="name" type="text"
                            class="mt-1 block w-full bg-white border border-gray-200 text-gray-800 rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 focus:border-green-400 transition-all font-bold placeholder:text-gray-400 shadow-sm"
                            v-model="form.name" required autofocus autocomplete="name" placeholder="Contoh: Budi Santoso" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="ALAMAT EMAIL" class="text-yellow-600 text-[10px] font-black tracking-[0.3em] ml-2" />
                        <TextInput id="email" type="email"
                            class="mt-1 block w-full bg-white border border-gray-200 text-gray-800 rounded-2xl py-4 px-6 focus:ring-4 focus:ring-green-500/20 focus:border-green-400 transition-all font-bold placeholder:text-gray-400 shadow-sm"
                            v-model="form.email" required autocomplete="username" placeholder="budi@sekolah.com" />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="password" value="KATA SANDI" class="text-yellow-600 text-[10px] font-black tracking-[0.3em] ml-2" />
                            <div class="relative">
                                <TextInput id="password" :type="showPassword ? 'text' : 'password'"
                                    class="mt-1 block w-full bg-white border border-gray-200 text-gray-800 rounded-2xl py-4 pl-6 pr-14 focus:ring-4 focus:ring-green-500/20 focus:border-green-400 transition-all font-bold placeholder:text-gray-400 shadow-sm"
                                    v-model="form.password" required autocomplete="new-password" placeholder="••••••••" />
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-4 flex items-center mt-1 text-gray-400 hover:text-green-600 transition-colors">
                                    <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                </button>
                            </div>
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div>
                            <InputLabel for="password_confirmation" value="KONFIRMASI SANDI" class="text-yellow-600 text-[10px] font-black tracking-[0.3em] ml-2" />
                            <div class="relative">
                                <TextInput id="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'"
                                    class="mt-1 block w-full bg-white border border-gray-200 text-gray-800 rounded-2xl py-4 pl-6 pr-14 focus:ring-4 focus:ring-green-500/20 focus:border-green-400 transition-all font-bold placeholder:text-gray-400 shadow-sm"
                                    v-model="form.password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-4 flex items-center mt-1 text-gray-400 hover:text-green-600 transition-colors">
                                    <svg v-if="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                                </button>
                            </div>
                            <InputError class="mt-2" :message="form.errors.password_confirmation" />
                        </div>
                    </div>

                    <div class="pt-6">
                        <button
                            class="w-full py-5 bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-emerald-400 text-white font-black uppercase tracking-[0.3em] rounded-2xl shadow-lg shadow-green-500/25 transition-all active:scale-[0.98] disabled:opacity-50 text-sm flex items-center justify-center"
                            :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                            SIMPAN PENGGUNA BARU
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.text-gradient-gy {
    background: linear-gradient(135deg, #16a34a 0%, #ca8a04 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.form-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(22, 163, 74, 0.15);
    box-shadow: 0 8px 40px rgba(22, 163, 74, 0.09);
}
.success-card {
    background: rgba(240, 253, 244, 0.9);
    border: 1px solid rgba(22, 163, 74, 0.3);
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-down { animation: fadeInDown 0.5s ease-out forwards; }
</style>
