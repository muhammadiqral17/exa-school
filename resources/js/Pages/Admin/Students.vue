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
    students: Object,
    filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const sortField = ref(props.filters.sort || 'name');
const sortOrder = ref(props.filters.direction || 'asc');

const toggleSort = (field) => {
    const newOrder = sortField.value === field && sortOrder.value === 'asc' ? 'desc' : 'asc';
    
    router.get(route('admin.students.index'), { 
        search: searchQuery.value,
        sort: field,
        direction: newOrder
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};

const filteredStudents = computed(() => {
    return props.students.data;
});

// Watch for search query and update URL
watch(searchQuery, (value) => {
    router.get(route('admin.students.index'), { 
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
    role: 'siswa',
    nis_nik: '',
    class: '',
    tahun_ajaran: '',
});

const openEditModal = (student) => {
    editingUserId.value = student.id;
    editForm.name = student.name;
    editForm.email = student.email;
    editForm.role = 'siswa';
    editForm.nis_nik = student.nis_nik || '';
    editForm.class = student.class || '';
    editForm.tahun_ajaran = student.tahun_ajaran || '';
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.clearErrors();
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    editForm.reset();
};

const updateStudent = () => {
    editForm.put(route('admin.users.update', editingUserId.value), {
        onSuccess: () => closeEditModal(),
    });
};

const showConfirmDeleteModal = ref(false);
const studentToDelete = ref(null);

const deleteStudent = (student) => {
    studentToDelete.value = student;
    showConfirmDeleteModal.value = true;
};

const confirmDeleteStudent = () => {
    if (!studentToDelete.value) return;
    router.delete(route('admin.users.destroy', studentToDelete.value.id), {
        onSuccess: () => {
            showConfirmDeleteModal.value = false;
            studentToDelete.value = null;
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
    importForm.post(route('admin.students.import'), {
        onSuccess: () => closeImportModal(),
    });
};
</script>

<template>
    <Head title="Data Siswa" />
    <AuthenticatedLayout>
        <template #header>
            Manajemen: <span class="text-gradient-gy capitalize">Data Siswa</span>
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
                    <h2 class="text-2xl font-black text-gray-800 tracking-tighter">Daftar Siswa</h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola data siswa, import dari Excel, atau tambah secara manual.</p>
                </div>
                
                <div class="flex flex-wrap gap-2 items-center">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input v-model="searchQuery" type="text" placeholder="Cari siswa..." class="pl-10 pr-4 py-3 border border-gray-200 rounded-2xl text-sm focus:ring-green-500 focus:border-green-500 w-full sm:w-64 transition-all shadow-sm">
                    </div>
                    <a :href="route('admin.students.template')" target="_blank" class="flex items-center gap-2 px-4 py-3 bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 rounded-2xl font-bold transition-all text-sm shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Format Excel
                    </a>
                    <a :href="route('admin.students.export')" class="flex items-center gap-2 px-4 py-3 bg-white border border-blue-200 hover:bg-blue-50 text-blue-700 rounded-2xl font-bold transition-all text-sm shadow-sm">
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
                        Tambah Siswa
                    </Link>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredStudents.length === 0" class="p-12 text-center bg-white/50 backdrop-blur-sm rounded-[2rem] border border-green-200 border-dashed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-green-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <h3 class="text-lg font-black text-gray-800">Data Tidak Ditemukan</h3>
                <p class="text-gray-500 mt-2">Siswa tidak ditemukan atau belum ditambahkan.</p>
            </div>

            <div v-else class="bg-white/80 backdrop-blur-md border border-green-200/60 shadow-lg shadow-green-500/10 rounded-[2rem] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-green-50/50 border-b border-green-100">
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">No</th>
                                <th @click="toggleSort('name')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        Nama Siswa
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
                                        NIS
                                        <svg v-if="sortField === 'nis_nik'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                                <th @click="toggleSort('class')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        Kelas
                                        <svg v-if="sortField === 'class'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                <th @click="toggleSort('email')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        Email
                                        <svg v-if="sortField === 'email'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/50">
                            <tr v-for="(student, index) in filteredStudents" :key="student.id" class="hover:bg-green-50/30 transition-colors">
                                <td class="p-4 text-sm font-medium text-gray-500">{{ props.students.from + index }}</td>
                                <td class="p-4 text-sm font-bold text-gray-800">{{ student.name }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">{{ student.nis_nik || '-' }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">{{ student.class || '-' }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">{{ student.tahun_ajaran || '-' }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">{{ student.email }}</td>
                                <td class="p-4 flex items-center justify-center gap-2">
                                    <button @click="openEditModal(student)" class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition-colors" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button @click="deleteStudent(student)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
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

            <Pagination :links="students.links" />
        </div>

        <!-- Import Modal -->
        <Modal :show="isImportModalOpen" @close="closeImportModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter mb-4">Import Data Siswa</h2>
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
                <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter mb-4">Edit Data Siswa</h2>
                <form @submit.prevent="updateStudent" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Nama Lengkap" />
                        <TextInput id="name" v-model="editForm.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="editForm.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="nis_nik" value="NIS" />
                        <TextInput id="nis_nik" v-model="editForm.nis_nik" type="text" class="mt-1 block w-full" />
                        <InputError :message="editForm.errors.nis_nik" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="class" value="Kelas" />
                        <TextInput id="class" v-model="editForm.class" type="text" class="mt-1 block w-full" />
                        <InputError :message="editForm.errors.class" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="tahun_ajaran" value="Tahun Ajaran" />
                        <TextInput id="tahun_ajaran" v-model="editForm.tahun_ajaran" type="text" class="mt-1 block w-full" placeholder="e.g. 2023/2024" />
                        <InputError :message="editForm.errors.tahun_ajaran" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput id="email" v-model="editForm.email" type="email" class="mt-1 block w-full" required />
                        <InputError :message="editForm.errors.email" class="mt-2" />
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
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">Hapus Siswa</h3>
                </div>
                
                <p class="text-xs font-medium text-gray-500 mb-6 leading-relaxed">
                    Apakah Anda yakin ingin menghapus data siswa <span class="font-bold text-gray-800">{{ studentToDelete?.name }}</span>? Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="flex justify-end gap-2.5">
                    <button 
                        @click="showConfirmDeleteModal = false"
                        class="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-xs font-black uppercase tracking-wider transition-colors border border-gray-200"
                    >
                        Batal
                    </button>
                    <button 
                        @click="confirmDeleteStudent"
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
