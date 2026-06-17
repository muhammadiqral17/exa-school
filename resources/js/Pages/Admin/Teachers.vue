<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    teachers: Object,
    filters: Object,
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

const searchQuery = ref(props.filters.search || '');
const sortField = ref(props.filters.sort || 'name');
const sortOrder = ref(props.filters.direction || 'asc');

const toggleSort = (field) => {
    // Note: subjects sorting is more complex server-side, for now we handle base fields
    const newOrder = sortField.value === field && sortOrder.value === 'asc' ? 'desc' : 'asc';
    
    router.get(route('admin.teachers.index'), { 
        search: searchQuery.value,
        sort: field,
        direction: newOrder
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};

const filteredTeachers = computed(() => {
    return props.teachers.data;
});

// Watch for search query and update URL
watch(searchQuery, (value) => {
    router.get(route('admin.teachers.index'), { 
        search: value,
        sort: sortField.value,
        direction: sortOrder.value
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
});

const page = usePage();
const flashSuccess = ref(page.props.flash?.success || '');

watch(() => page.props.flash?.success, (newVal) => {
    flashSuccess.value = newVal || '';
    if (newVal) setTimeout(() => { flashSuccess.value = ''; }, 5000);
});

// Edit Modal
const isEditModalOpen = ref(false);
const editingUserId = ref(null);
const editForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'guru',
    nis_nik: '',
    tahun_ajaran: '',
    class: '',
    classes: [''],
    manual_subjects: [],
});

const addClassField = () => {
    if (editForm.classes.length < 10) {
        editForm.classes.push('');
    }
};

const removeClassField = (index) => {
    if (editForm.classes.length > 1) {
        editForm.classes.splice(index, 1);
    }
};

const addSubjectField = () => {
    if (editForm.manual_subjects.length < 5) {
        editForm.manual_subjects.push({ name: '', code: '', is_manual: false });
    }
};

const removeSubjectField = (index) => {
    if (editForm.manual_subjects.length > 1) {
        editForm.manual_subjects.splice(index, 1);
    }
};

const handleSubjectChange = (index) => {
    const subject = editForm.manual_subjects[index];
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

const openEditModal = (teacher) => {
    editingUserId.value = teacher.id;
    editForm.name = teacher.name;
    editForm.email = teacher.email;
    editForm.role = 'guru';
    editForm.nis_nik = teacher.nis_nik || '';
    editForm.tahun_ajaran = teacher.tahun_ajaran || '';
    editForm.class = teacher.class || '';
    editForm.classes = teacher.class 
        ? teacher.class.split(',').map(c => c.trim())
        : [''];
    editForm.manual_subjects = teacher.subjects && teacher.subjects.length > 0 
        ? teacher.subjects.map(s => {
            let isStandard = false;
            standardSubjects.forEach(cat => {
                if (cat.items.some(i => i.name === s.name)) {
                    isStandard = true;
                }
            });
            return { name: s.name, code: s.code, is_manual: !isStandard };
        })
        : [{ name: '', code: '', is_manual: false }];
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.clearErrors();
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editForm.reset();
};

const updateTeacher = () => {
    editForm.transform((data) => ({
        ...data,
        class: data.classes.filter(c => c).join(', ')
    })).put(route('admin.users.update', editingUserId.value), {
        onSuccess: () => closeEditModal(),
    });
};

const showConfirmDeleteModal = ref(false);
const teacherToDelete = ref(null);

const deleteTeacher = (teacher) => {
    teacherToDelete.value = teacher;
    showConfirmDeleteModal.value = true;
};

const confirmDeleteTeacher = () => {
    if (!teacherToDelete.value) return;
    router.delete(route('admin.users.destroy', teacherToDelete.value.id), {
        onSuccess: () => {
            showConfirmDeleteModal.value = false;
            teacherToDelete.value = null;
        }
    });
};

// Import Modal
const isImportModalOpen = ref(false);
const importForm = useForm({
    file: null,
});

const openImportModal = () => {
    importForm.reset();
    isImportModalOpen.value = true;
};

const closeImportModal = () => {
    isImportModalOpen.value = false;
    importForm.reset();
};

const submitImport = () => {
    importForm.post(route('admin.teachers.import'), {
        onSuccess: () => closeImportModal(),
    });
};
</script>

<template>
    <Head title="Data Guru" />
    <AuthenticatedLayout>
        <template #header>
            Manajemen: <span class="text-gradient-gy capitalize">Data Guru</span>
        </template>

        <div class="space-y-6 sm:space-y-10">
            <!-- Flash Message -->
            <div v-if="flashSuccess" class="p-4 bg-green-100 border border-green-200 text-green-700 rounded-2xl animate-fade-in-down flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                <span class="font-bold">{{ flashSuccess }}</span>
            </div>

            <!-- Header Section -->
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 tracking-tighter">Daftar Guru</h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola data guru, import dari Excel, atau tambah secara manual.</p>
                </div>
                
                <div class="flex flex-wrap gap-2 items-center">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input v-model="searchQuery" type="text" placeholder="Cari guru..." class="pl-10 pr-4 py-3 border border-gray-200 rounded-2xl text-sm focus:ring-green-500 focus:border-green-500 w-full sm:w-64 transition-all shadow-sm">
                    </div>
                    <a :href="route('admin.teachers.template')" target="_blank" class="flex items-center gap-2 px-4 py-3 bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 rounded-2xl font-bold transition-all text-sm shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Format Excel
                    </a>
                    <a :href="route('admin.teachers.export')" class="flex items-center gap-2 px-4 py-3 bg-white border border-blue-200 hover:bg-blue-50 text-blue-700 rounded-2xl font-bold transition-all text-sm shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download Data Terdaftar
                    </a>
                    <button @click="openImportModal" class="flex items-center gap-2 px-4 py-3 bg-white border border-green-200 hover:bg-green-50 text-green-700 rounded-2xl font-bold transition-all text-sm shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Import Excel
                    </button>
                    <Link :href="route('admin.users.create')" class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-2xl font-bold transition-all shadow-lg shadow-green-500/30 hover:shadow-green-500/50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Guru
                    </Link>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredTeachers.length === 0" class="p-12 text-center bg-white/50 backdrop-blur-sm rounded-[2rem] border border-green-200 border-dashed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-green-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <h3 class="text-lg font-black text-gray-800">Data Tidak Ditemukan</h3>
                <p class="text-gray-500 mt-2">Guru tidak ditemukan atau belum ditambahkan.</p>
            </div>

            <div v-else class="bg-white/80 backdrop-blur-md border border-green-200/60 shadow-lg shadow-green-500/10 rounded-[2rem] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-green-50/50 border-b border-green-100">
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">No</th>
                                <th @click="toggleSort('name')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        Nama Guru
                                        <svg v-if="sortField === 'name'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-0 group-hover:opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                                <th @click="toggleSort('nis_nik')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        NIK
                                        <svg v-if="sortField === 'nis_nik'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-0 group-hover:opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                                <th @click="toggleSort('tahun_ajaran')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        Thn Ajaran
                                        <svg v-if="sortField === 'tahun_ajaran'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-0 group-hover:opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                                <th @click="toggleSort('class')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        Kelas Mengajar
                                        <svg v-if="sortField === 'class'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-0 group-hover:opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">
                                    <div class="flex items-center gap-1 uppercase tracking-widest">
                                        Mata Pelajaran
                                    </div>
                                </th>
                                <th @click="toggleSort('email')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        Email
                                        <svg v-if="sortField === 'email'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-0 group-hover:opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/50">
                            <tr v-for="(teacher, index) in filteredTeachers" :key="teacher.id" class="hover:bg-green-50/30 transition-colors">
                                <td class="p-4 text-sm font-medium text-gray-500">{{ props.teachers.from + index }}</td>
                                <td class="p-4 text-sm font-bold text-gray-800">{{ teacher.name }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">{{ teacher.nis_nik || '-' }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">{{ teacher.tahun_ajaran || '-' }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">{{ teacher.class || '-' }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">
                                    <span v-if="teacher.subjects && teacher.subjects.length > 0">
                                        {{ [...new Set(teacher.subjects.map(s => s.name))].join(', ') }}
                                    </span>
                                    <span v-else class="text-gray-400 italic">Belum ditentukan</span>
                                </td>
                                <td class="p-4 text-sm font-medium text-gray-600">{{ teacher.email }}</td>
                                <td class="p-4 flex items-center justify-center gap-2">
                                    <button @click="openEditModal(teacher)" class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition-colors" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button @click="deleteTeacher(teacher)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :links="teachers.links" />
        </div>

        <!-- Import Modal -->
        <Modal :show="isImportModalOpen" @close="closeImportModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter mb-4">Import Data Guru</h2>
                <form @submit.prevent="submitImport">
                    <div class="mb-4">
                        <InputLabel value="File Excel/CSV" />
                        <input type="file" @change="e => importForm.file = e.target.files[0]" class="mt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-green-50 file:text-green-700 hover:file:bg-green-100" accept=".xlsx, .xls, .csv" required />
                        <InputError :message="importForm.errors.file" class="mt-2" />
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="closeImportModal" class="px-4 py-2 text-sm font-bold text-gray-600 hover:bg-gray-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-green-600 hover:bg-green-700 rounded-xl" :disabled="importForm.processing">
                            <span v-if="importForm.processing">Mengimport...</span>
                            <span v-else>Import Data</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Modal -->
        <Modal :show="isEditModalOpen" @close="closeEditModal" maxWidth="lg">
            <div class="p-6">
                <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter mb-4">Edit Data Guru</h2>
                <form @submit.prevent="updateTeacher" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Nama Lengkap" />
                        <TextInput id="name" v-model="editForm.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="editForm.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="nis_nik" value="NIK" />
                        <TextInput id="nis_nik" v-model="editForm.nis_nik" type="text" class="mt-1 block w-full" />
                        <InputError :message="editForm.errors.nis_nik" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="tahun_ajaran" value="Tahun Ajaran" />
                        <TextInput id="tahun_ajaran" v-model="editForm.tahun_ajaran" type="text" class="mt-1 block w-full" placeholder="e.g. 2023/2024" />
                        <InputError :message="editForm.errors.tahun_ajaran" class="mt-2" />
                    </div>
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <InputLabel for="class" value="Kelas Mengajar" />
                            <button type="button" @click="addClassField" class="text-[10px] font-black text-green-600 hover:text-green-700 flex items-center gap-1 uppercase tracking-widest">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Tambah Kelas
                            </button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="(cls, index) in editForm.classes" :key="index" class="flex gap-2 items-center">
                                <select v-model="editForm.classes[index]" 
                                    class="flex-1 bg-white border border-gray-200 text-sm font-medium rounded-xl py-2 px-3 focus:ring-4 focus:ring-green-500/20 focus:border-green-400 transition-all shadow-sm">
                                    <option value="" disabled>Pilih Kelas</option>
                                    <option value="7">Kelas 7</option>
                                    <option value="8">Kelas 8</option>
                                    <option value="9">Kelas 9</option>
                                    <option value="7A">7A</option><option value="7B">7B</option><option value="7C">7C</option><option value="7D">7D</option>
                                    <option value="8A">8A</option><option value="8B">8B</option><option value="8C">8C</option><option value="8D">8D</option>
                                    <option value="9A">9A</option><option value="9B">9B</option><option value="9C">9C</option><option value="9D">9D</option>
                                </select>
                                <button type="button" @click="removeClassField(index)" v-if="editForm.classes.length > 1" class="p-2 text-red-400 hover:text-red-600 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                        <InputError :message="editForm.errors.class" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput id="email" v-model="editForm.email" type="email" class="mt-1 block w-full" required />
                        <InputError :message="editForm.errors.email" class="mt-2" />
                    </div>

                    <!-- Manual Subject Input -->
                    <div class="space-y-4 p-4 bg-yellow-50/50 rounded-2xl border border-yellow-200/50">
                        <div class="flex justify-between items-center">
                            <InputLabel value="MATA PELAJARAN" class="text-yellow-600 text-[10px] font-black tracking-widest" />
                            <button type="button" @click="addSubjectField" v-if="editForm.manual_subjects.length < 5" class="text-[10px] font-black text-green-600 hover:text-green-700 flex items-center gap-1 uppercase tracking-widest">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Tambah Mapel
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div v-for="(subject, index) in editForm.manual_subjects" :key="index" class="space-y-1 animate-fade-in-down">
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
                                    <button type="button" @click="removeSubjectField(index)" v-if="editForm.manual_subjects.length > 1" class="p-3 text-red-400 hover:text-red-600 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                                <div class="flex gap-3 px-1">
                                    <InputError class="flex-1" :message="editForm.errors[`manual_subjects.${index}.name`]" />
                                    <InputError class="w-full sm:w-32" :message="editForm.errors[`manual_subjects.${index}.code`]" />
                                    <div v-if="editForm.manual_subjects.length > 1" class="hidden sm:block w-11"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pt-4 border-t">
                        <p class="text-xs text-gray-500 mb-4">Kosongkan password jika tidak ingin mengubahnya.</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="password" value="Password Baru" />
                                <TextInput id="password" v-model="editForm.password" type="password" class="mt-1 block w-full" />
                                <InputError :message="editForm.errors.password" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="password_confirmation" value="Konfirmasi Password" />
                                <TextInput id="password_confirmation" v-model="editForm.password_confirmation" type="password" class="mt-1 block w-full" />
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="closeEditModal" class="px-4 py-2 text-sm font-bold text-gray-600 hover:bg-gray-100 rounded-xl">Batal</button>
                        <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 rounded-xl shadow-lg shadow-green-500/30" :disabled="editForm.processing">
                            <span v-if="editForm.processing">Menyimpan...</span>
                            <span v-else>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modern Delete Confirmation Modal -->
        <Modal :show="showConfirmDeleteModal" @close="showConfirmDeleteModal = false" maxWidth="sm">
            <div class="p-6 text-left">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">Hapus Guru</h3>
                </div>
                
                <p class="text-xs font-medium text-gray-500 mb-6 leading-relaxed">
                    Apakah Anda yakin ingin menghapus data guru <span class="font-bold text-gray-800">{{ teacherToDelete?.name }}</span>? Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="flex justify-end gap-2.5">
                    <button 
                        @click="showConfirmDeleteModal = false"
                        class="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-xs font-black uppercase tracking-wider transition-colors border border-gray-200"
                    >
                        Batal
                    </button>
                    <button 
                        @click="confirmDeleteTeacher"
                        class="px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors shadow-md shadow-red-500/10"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<style scoped>
.text-gradient-gy {
    background: linear-gradient(135deg, #16a34a 0%, #ca8a04 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-down { animation: fadeInDown 0.3s ease-out forwards; }
</style>
