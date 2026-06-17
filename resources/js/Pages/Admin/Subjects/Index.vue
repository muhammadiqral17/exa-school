<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    subjects: Array,
    gurus: Array,
});

const searchQuery = ref('');

const filteredSubjects = computed(() => {
    if (!searchQuery.value) return props.subjects;
    const query = searchQuery.value.toLowerCase();
    return props.subjects.filter(subject => 
        JSON.stringify(subject).toLowerCase().includes(query)
    );
});

const isAddModalOpen = ref(false);
const isEditSubjectModalOpen = ref(false);
const isBuatSoalModalOpen = ref(false);
const isKelolaModalOpen = ref(false);
const selectedSubjectId = ref(null);
const selectedGroupName = ref(null);
const editingExamId = ref(null);


const page = usePage();
const flashSuccess = ref(page.props.flash?.success || '');
const flashError = ref(page.props.flash?.error || '');

watch(() => page.props.flash?.success, (newVal) => {
    flashSuccess.value = newVal || '';
    if (newVal) setTimeout(() => { flashSuccess.value = ''; }, 5000);
});

watch(() => page.props.flash?.error, (newVal) => {
    flashError.value = newVal || '';
    if (newVal) setTimeout(() => { flashError.value = ''; }, 5000);
});

const selectedSubject = computed(() => {
    if (!selectedSubjectId.value) return null;
    return props.subjects.find(s => s.id === selectedSubjectId.value);
});

const groupedSubjects = computed(() => {
    const groups = {};
    filteredSubjects.value.forEach(subject => {
        const name = subject.name;
        if (!groups[name]) {
            groups[name] = {
                name: name,
                subjects: [],
                allExams: [],
                classes: new Set(),
                codes: new Set(),
                years: new Set(),
            };

        }
        groups[name].subjects.push(subject);
        if (subject.exams) {
            groups[name].allExams.push(...subject.exams.map(e => ({
                ...e,
                parent_subject: subject
            })));
        }
        if (subject.class) groups[name].classes.add(subject.class);
        if (subject.code) groups[name].codes.add(subject.code);
        if (subject.academic_year) groups[name].years.add(subject.academic_year);
    });
    return Object.values(groups).map(g => ({
        ...g,
        displayClasses: Array.from(g.classes).sort().join(', '),
        displayCodes: Array.from(g.codes).join(', '),
        displayYears: Array.from(g.years).sort().join(' / '),
        displaySemesters: Array.from(new Set(g.subjects.map(s => s.semester).filter(Boolean))).join(' / ')
    }));
});



const activeGroup = computed(() => {
    if (!selectedGroupName.value) return null;
    return groupedSubjects.value.find(g => g.name === selectedGroupName.value);
});


const form = useForm({
    name: '',
    material: '',
    class: '',
    schedule_time: '',
    code: '',
    user_id: '',
    academic_year: '',
    semester: '',
});


const examForm = useForm({
    title: '',
    duration_minutes: 90,
    pg_weight: 70,
    essay_weight: 30,
    start_time: '',
    is_active: false,
    random_order: false,
    input_method: 'excel',
    excel_file: null,
});

// Computed for button disable
const weightsInvalid = computed(() => {
    const pg = parseInt(examForm.pg_weight) || 0;
    const essay = parseInt(examForm.essay_weight) || 0;
    return pg + essay !== 100;
});


const openAddModal = () => {
    form.reset();
    isAddModalOpen.value = true;
};

const closeAddModal = () => {
    isAddModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const openEditSubjectModal = (subject) => {
    selectedSubjectId.value = subject.id;
    form.name = subject.name;
    form.material = subject.material;
    form.class = subject.class || '';
    form.code = subject.code;
    form.user_id = subject.user_id;
    form.academic_year = subject.academic_year || '';
    form.semester = subject.semester || '';

    
    if (subject.schedule_time) {
        try {
            // Try to parse if it's an ISO string or similar
            const dt = new Date(subject.schedule_time);
            if (!isNaN(dt)) {
                form.schedule_time = dt.toISOString().slice(0, 16);
            } else {
                form.schedule_time = subject.schedule_time;
            }
        } catch (e) {
            form.schedule_time = subject.schedule_time;
        }
    } else {
        form.schedule_time = '';
    }
    
    isEditSubjectModalOpen.value = true;
};

const closeEditSubjectModal = () => {
    isEditSubjectModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const openKelolaModal = (subject) => {
    selectedSubjectId.value = subject.id;
    isKelolaModalOpen.value = true;
};

const closeKelolaModal = () => {
    isKelolaModalOpen.value = false;
};

const openEditExamModal = (exam) => {
    editingExamId.value = exam.id;
    examForm.title = exam.title;
    examForm.duration_minutes = exam.duration_minutes;
    examForm.pg_weight = exam.pg_weight;
    examForm.essay_weight = exam.essay_weight;
    if (exam.start_time) {
        const dt = new Date(exam.start_time);
        examForm.start_time = dt.toISOString().slice(0, 16);
    } else {
        examForm.start_time = '';
    }
    examForm.is_active = !!exam.is_active;
    examForm.random_order = !!exam.random_order;
    examForm.input_method = 'manual';
    examForm.clearErrors();
    isBuatSoalModalOpen.value = true;
};

const showConfirmDeleteModal = ref(false);
const confirmDeleteTitle = ref('');
const confirmDeleteMessage = ref('');
const confirmDeleteAction = ref(null);

const deleteExam = (exam) => {
    confirmDeleteTitle.value = 'Hapus Sesi Ujian';
    confirmDeleteMessage.value = `Apakah Anda yakin ingin menghapus sesi ujian "${exam.title}"? Semua soal dan nilai terkait akan ikut terhapus secara permanen.`;
    confirmDeleteAction.value = () => {
        router.delete(route('admin.exams.destroy', exam.id), {
            preserveScroll: true
        });
    };
    showConfirmDeleteModal.value = true;
};

const deleteSubject = (subject) => {
    confirmDeleteTitle.value = 'Hapus Bank Soal';
    confirmDeleteMessage.value = `Apakah Anda yakin ingin menghapus bank soal "${subject.name}"? Semua data kelas dan ujian terkait akan ikut terhapus secara permanen.`;
    confirmDeleteAction.value = () => {
        router.delete(route('admin.subjects.destroy', subject.id), {
            preserveScroll: true,
            onFinish: () => {
                if (isKelolaModalOpen.value) closeKelolaModal();
            }
        });
    };
    showConfirmDeleteModal.value = true;
};

const executeConfirmDelete = () => {
    if (confirmDeleteAction.value) {
        confirmDeleteAction.value();
    }
    showConfirmDeleteModal.value = false;
};

const openBuatSoalModal = (subject) => {
    selectedSubjectId.value = subject.id;
    editingExamId.value = null;
    examForm.title = '';
    examForm.duration_minutes = 90;
    examForm.pg_weight = 70;
    examForm.essay_weight = 30;
    examForm.start_time = '';
    examForm.is_active = false;
    examForm.random_order = false;
    examForm.input_method = 'excel';
    examForm.excel_file = null;
    examForm.clearErrors();
    isBuatSoalModalOpen.value = true;
};

const closeBuatSoalModal = () => {
    isBuatSoalModalOpen.value = false;
};

const submit = () => {
    form.post(route('admin.subjects.store'), {
        onSuccess: () => closeAddModal(),
    });
};

const updateSubject = () => {
    form.put(route('admin.subjects.update', selectedSubjectId.value), {
        onSuccess: () => closeEditSubjectModal(),
    });
};

const submitExam = () => {
    const dataTransform = (data) => ({
        ...data,
        subject_id: selectedSubjectId.value,
        pg_weight: parseInt(data.pg_weight) || 0,
        essay_weight: parseInt(data.essay_weight) || 0,
        duration_minutes: parseInt(data.duration_minutes) || 60,
    });

    if (editingExamId.value) {
        examForm.transform(dataTransform).put(route('admin.exams.update', editingExamId.value), {
            onSuccess: () => closeBuatSoalModal(),
            preserveScroll: true,
        });
    } else {
        examForm.transform(dataTransform).post(route('admin.exams.store'), {
            onSuccess: () => closeBuatSoalModal(),
            preserveScroll: true,
        });
    }
};


const getSubjectColor = (index) => {
    const colors = ['green', 'yellow', 'blue'];
    return colors[index % colors.length];
};

const formatSchedule = (time) => {
    if (!time) return 'Belum diatur';
    try {
        const dt = new Date(time);
        if (!isNaN(dt)) {
            return dt.toLocaleString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    } catch (e) {}
    return time;
};

const formatExamSchedule = (exam) => {
    if (!exam.start_time) return 'Tidak Terjadwal';
    const start = new Date(exam.start_time);
    const end = new Date(start.getTime() + (exam.duration_minutes || 0) * 60000);
    return `${start.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'})} - ${end.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'})}`;
};

</script>

<template>
    <Head title="Manajemen Bank Soal" />
    <AuthenticatedLayout>
        <template #header>
            Manajemen: <span class="text-gradient-gy capitalize">Bank Soal</span>
        </template>

        <div class="space-y-6 sm:space-y-10">
            
            <!-- Flash Messages -->
            <div v-if="flashSuccess" class="p-4 bg-green-100 border border-green-200 text-green-700 rounded-2xl animate-fade-in-down flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                <span class="font-bold">{{ flashSuccess }}</span>
            </div>

            <div v-if="flashError" class="p-4 bg-red-100 border border-red-200 text-red-700 rounded-2xl animate-fade-in-down flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                <span class="font-bold">{{ flashError }}</span>
            </div>

            <!-- Header Section -->
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 tracking-tighter">Daftar Bank Soal</h2>
                    <p class="text-sm text-gray-500 mt-1">Kelola kurikulum dan bank soal untuk setiap kategori.</p>
                </div>
                
                <div class="flex flex-wrap gap-2 items-center">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input v-model="searchQuery" type="text" placeholder="Cari pelajaran..." class="pl-10 pr-4 py-3 border border-gray-200 rounded-2xl text-sm focus:ring-green-500 focus:border-green-500 w-full sm:w-64 transition-all shadow-sm">
                    </div>
                    <button @click="openAddModal" class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-2xl font-bold transition-all shadow-lg shadow-green-500/30 hover:shadow-green-500/50 hover:-translate-y-0.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Mata Pelajaran
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredSubjects.length === 0" class="p-12 text-center bg-white/50 backdrop-blur-sm rounded-[2rem] border border-green-200 border-dashed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-green-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <h3 class="text-lg font-black text-gray-800">Data Tidak Ditemukan</h3>
                <p class="text-gray-500 mt-2">Bank soal tidak ditemukan atau belum ditambahkan.</p>
            </div>

            <!-- Subject Cards Grid -->
            <div v-else>
                <!-- Level 1: Grouped Subject Grid -->
                <div v-if="!selectedGroupName" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="group in groupedSubjects" :key="group.name" @click="selectedGroupName = group.name" class="cursor-pointer group relative">
                        <div class="absolute -inset-1 bg-gradient-to-r from-green-600 to-yellow-500 rounded-[2.5rem] blur opacity-10 group-hover:opacity-30 transition duration-500"></div>
                        <div class="relative bg-gradient-to-br from-white to-green-50/10 backdrop-blur-xl rounded-[2.5rem] p-8 border border-white shadow-xl shadow-green-900/5 flex flex-col h-full transform transition-all duration-500 group-hover:-translate-y-2 group-hover:shadow-green-500/10">
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg shadow-green-500/20 group-hover:rotate-12 transition-transform duration-500">
                                    <span class="text-3xl font-black text-white">{{ group.name.charAt(0) }}</span>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <div class="px-3 py-1.5 bg-white rounded-xl shadow-sm border border-gray-50 flex flex-col items-end">
                                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none">Total Ujian</span>
                                        <span class="text-xl font-black text-green-600 leading-none mt-1">{{ group.allExams.length }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <h3 class="text-xl font-black text-gray-800 group-hover:text-green-600 transition-colors tracking-tighter leading-tight">{{ group.name }}</h3>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="px-2.5 py-1 bg-green-50 text-green-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-green-100">
                                    Kelas {{ group.displayClasses }}
                                </span>
                                <span v-if="group.displayYears" class="px-2.5 py-1 bg-gray-50 text-gray-400 rounded-lg text-[9px] font-black uppercase tracking-widest border border-gray-100 flex items-center gap-1">
                                    {{ group.displayYears }}
                                    <span v-if="group.displaySemesters" class="text-green-600">&bull; {{ group.displaySemesters }}</span>
                                </span>

                            </div>


                            
                            <div class="mt-8 pt-6 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest group-hover:text-green-600 transition-colors">Kelola Bank Soal</span>
                                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center group-hover:bg-green-500 group-hover:text-white transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Level 2: Detailed View -->
                <div v-else class="space-y-10 animate-fade-in">
                    <div class="flex items-center gap-4">
                        <button @click="selectedGroupName = null" class="p-3 bg-white border border-gray-100 rounded-2xl hover:bg-green-50 hover:border-green-200 transition-all shadow-sm group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400 group-hover:text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-3xl font-black text-gray-800 tracking-tighter">{{ activeGroup.name }}</h3>
                            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Manajemen Mata Pelajaran & Ujian</p>
                        </div>
                    </div>

                    <!-- Sub-subjects (Different Classes for same name) -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div v-for="subject in activeGroup.subjects" :key="subject.id" class="bg-white/80 backdrop-blur-md rounded-[2.5rem] border border-gray-100 p-8 shadow-xl shadow-gray-200/50">
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-[9px] font-black uppercase tracking-widest">{{ subject.code }}</span>
                                        <span v-if="subject.material" class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded text-[9px] font-black uppercase tracking-widest border border-blue-100">{{ subject.material }}</span>
                                        <div class="flex items-center gap-2">
                                            <span v-if="subject.academic_year" class="px-2 py-0.5 bg-green-50 text-green-600 rounded text-[9px] font-black uppercase tracking-widest border border-green-100">{{ subject.academic_year }}</span>
                                            <span v-if="subject.semester" class="px-2 py-0.5 bg-yellow-50 text-yellow-600 rounded text-[9px] font-black uppercase tracking-widest border border-yellow-100">{{ subject.semester }}</span>
                                        </div>
                                    </div>

                                    <h4 class="text-xl font-black text-gray-800 mt-3 tracking-tight">{{ subject.name }}</h4>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Kelas {{ subject.class }}</p>
                                    <div class="mt-2 space-y-1">
                                        <p class="text-[11px] text-gray-500 font-bold flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                            {{ subject.user ? subject.user.name : 'Guru Belum Ditentukan' }}
                                        </p>
                                        <p v-if="subject.schedule_time" class="text-[11px] text-gray-500 font-bold flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            Jadwal: {{ formatSchedule(subject.schedule_time) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <button @click="openEditSubjectModal(subject)" class="p-2.5 bg-yellow-50 text-yellow-600 rounded-xl hover:bg-yellow-100 transition-all border border-yellow-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                    </button>
                                    <button @click="deleteSubject(subject)" class="p-2.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all border border-red-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 mb-8">
                                <div v-for="exam in subject.exams" :key="exam.id" class="p-4 bg-gray-50/50 rounded-2xl border border-gray-100 hover:border-green-200 transition-all flex justify-between items-center group/ex">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h5 class="font-bold text-gray-800 text-sm">{{ exam.title }}</h5>
                                            <span v-if="exam.is_active" class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest bg-green-100 text-green-700 border border-green-200">Aktif</span>
                                            <span v-else class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest bg-gray-50 text-gray-400 border border-gray-100">Nonaktif</span>
                                            <span v-if="exam.random_order" class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-widest bg-indigo-50 text-indigo-600 border border-indigo-100">Acak</span>
                                        </div>
                                        <div class="flex flex-col mt-1">
                                            <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">
                                                {{ exam.duration_minutes }}m &bull; PG {{ exam.pg_weight }}% ESSAY {{ exam.essay_weight }}%
                                            </p>
                                            <p v-if="exam.start_time" class="text-[9px] text-green-600 font-bold uppercase tracking-widest mt-0.5">
                                                Mulai: {{ formatSchedule(exam.start_time) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex gap-2 opacity-0 group-hover/ex:opacity-100 transition-opacity">
                                        <Link :href="route('admin.exams.questions.create', exam.id)" class="p-2 bg-blue-50 text-blue-600 rounded-lg shadow-sm border border-blue-100 hover:bg-blue-100 transition-all" title="Edit Soal">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </Link>
                                        <button @click="openEditExamModal(exam)" class="p-2 bg-yellow-50 text-yellow-600 rounded-lg shadow-sm border border-yellow-100 hover:bg-yellow-100 transition-all" title="Edit Pengaturan Ujian">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        </button>
                                        <button @click="deleteExam(exam)" class="p-2 bg-red-50 text-red-600 rounded-lg shadow-sm border border-red-100 hover:bg-red-100 transition-all" title="Hapus Ujian">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>

                                </div>
                            </div>

                            <button @click="openBuatSoalModal(subject)" class="w-full py-3 bg-gray-800 hover:bg-black text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Buat Sesi Ujian Baru
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Add Subject Modal -->
        <Modal :show="isAddModalOpen" @close="closeAddModal">
            <div class="p-6 sm:p-8 bg-white/90 backdrop-blur-xl">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Tambah Mata Pelajaran</h2>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">Form Registrasi Kurikulum</p>
                    </div>
                    <button @click="closeAddModal" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="code" value="Kode Pelajaran" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <TextInput
                            id="code"
                            type="text"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm"
                            v-model="form.code"
                            required
                            placeholder="Contoh: MAT-101"
                        />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>
                    
                        <div>
                            <InputLabel for="academic_year" value="Tahun Ajaran" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                            <TextInput
                                id="academic_year"
                                type="text"
                                class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm"
                                v-model="form.academic_year"
                                placeholder="Contoh: 2026/2028"
                            />
                            <InputError class="mt-2" :message="form.errors.academic_year" />
                        </div>

                        <div>
                            <InputLabel for="semester" value="Semester" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                            <select
                                id="semester"
                                v-model="form.semester"
                                class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm bg-white font-bold text-sm py-4 px-6"
                            >
                                <option value="">Pilih Semester</option>
                                <option value="GANJIL">GANJIL</option>
                                <option value="GENAP">GENAP</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.semester" />
                        </div>

                    <div>

                        <InputLabel for="name" value="Nama Mata Pelajaran" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <select
                            id="name"
                            v-model="form.name"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm bg-white font-bold text-sm py-4 px-6"
                            required
                        >
                            <option value="" disabled>Pilih Mata Pelajaran</option>
                            <optgroup label="KATEGORI AGAMA">
                                <option value="FIQIH (FIQ)">FIQIH (FIQ)</option>
                                <option value="AKIDAH AKHLAK (AA)">AKIDAH AKHLAK (AA)</option>
                                <option value="AL-QUR'AN DAN HADIST (QH)">AL-QUR'AN DAN HADIST (QH)</option>
                                <option value="BAHASA ARAB (BA)">BAHASA ARAB (BA)</option>
                                <option value="SEJARAH KEBUDAYAAN ISLAM (SKI)">SEJARAH KEBUDAYAAN ISLAM (SKI)</option>
                            </optgroup>
                            <optgroup label="KATEGORI UMUM">
                                <option value="MATEMATIKA (MTK)">MATEMATIKA (MTK)</option>
                                <option value="ILMU PENGETAHUAN ALAM (IPA)">ILMU PENGETAHUAN ALAM (IPA)</option>
                                <option value="ILMU PENGETAHUAN SOSIAL (IPS)">ILMU PENGETAHUAN SOSIAL (IPS)</option>
                                <option value="BAHASA INDONESIA (BI)">BAHASA INDONESIA (BI)</option>
                                <option value="BAHASA INGGRIS (B.ING)">BAHASA INGGRIS (B.ING)</option>
                                <option value="PENDIDIKAN JASMANI OLAHRAGA DAN KESEHATAN (PENJAS)">PENDIDIKAN JASMANI OLAHRAGA DAN KESEHATAN (PENJAS)</option>
                                <option value="PENDIDIKAN PANCASILA DAN KEWARGANEGARAAN (PPKN)">PENDIDIKAN PANCASILA DAN KEWARGANEGARAAN (PPKN)</option>
                                <option value="SENI BUDAYA (SBK)">SENI BUDAYA (SBK)</option>
                                <option value="TEKNOLOGI INFORMASI DAN KOMUNIKASI (TIK)">TEKNOLOGI INFORMASI DAN KOMUNIKASI (TIK)</option>
                            </optgroup>
                        </select>
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="material" value="Materi Terkait" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <TextInput
                            id="material"
                            type="text"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm"
                            v-model="form.material"
                            placeholder="Contoh: Tentang Haji, Shalat Berjamaah, dll"
                        />
                        <InputError class="mt-2" :message="form.errors.material" />
                    </div>

                    <div>
                        <InputLabel for="class" value="Kelas" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <TextInput
                            id="class"
                            type="text"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm"
                            v-model="form.class"
                            required
                            placeholder="Contoh: XII IPA 1"
                        />
                        <InputError class="mt-2" :message="form.errors.class" />
                    </div>

                    <div>
                        <InputLabel for="schedule_time" value="Jam Mengajar" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <TextInput
                            id="schedule_time"
                            type="datetime-local"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm bg-white"
                            v-model="form.schedule_time"
                        />
                        <InputError class="mt-2" :message="form.errors.schedule_time" />
                        <p class="text-[10px] text-gray-400 mt-2 font-bold italic">* Pilih tanggal dan waktu pelaksanaan mengajar.</p>
                    </div>

                    <div>
                        <InputLabel for="user_id" value="Guru Pengampu" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <select
                            id="user_id"
                            v-model="form.user_id"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm bg-white py-4 px-6"
                            required
                        >
                            <option value="" disabled selected>Pilih Guru</option>
                            <option v-for="guru in gurus" :key="guru.id" :value="guru.id">
                                {{ guru.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.user_id" />
                    </div>

                    <div class="flex items-center justify-end mt-8 pt-6 border-t border-gray-100 gap-4">
                        <button type="button" @click="closeAddModal" class="px-6 py-3 border border-gray-200 hover:bg-gray-50 text-gray-600 rounded-2xl font-bold transition-all text-sm uppercase tracking-widest">
                            Batal
                        </button>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-2xl font-bold transition-all shadow-lg shadow-green-500/30 hover:shadow-green-500/50 text-sm uppercase tracking-widest flex items-center gap-2" :disabled="form.processing">
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Subject Modal -->
        <Modal :show="isEditSubjectModalOpen" @close="closeEditSubjectModal">
            <div class="p-6 sm:p-8 bg-white/90 backdrop-blur-xl">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Edit Bank Soal</h2>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">Ubah Data Kurikulum</p>
                    </div>
                    <button @click="closeEditSubjectModal" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="updateSubject" class="space-y-6">
                    <div>
                        <InputLabel for="edit_code" value="Kode Pelajaran" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <TextInput
                            id="edit_code"
                            type="text"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm"
                            v-model="form.code"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>

                        <div>
                            <InputLabel for="edit_academic_year" value="Tahun Ajaran" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                            <TextInput
                                id="edit_academic_year"
                                type="text"
                                class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm"
                                v-model="form.academic_year"
                                placeholder="Contoh: 2026/2028"
                            />
                            <InputError class="mt-2" :message="form.errors.academic_year" />
                        </div>

                        <div>
                            <InputLabel for="edit_semester" value="Semester" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                            <select
                                id="edit_semester"
                                v-model="form.semester"
                                class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm bg-white font-bold text-sm py-4 px-6"
                            >
                                <option value="">Pilih Semester</option>
                                <option value="GANJIL">GANJIL</option>
                                <option value="GENAP">GENAP</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.semester" />
                        </div>

                    <div>

                        <InputLabel for="edit_name" value="Nama Mata Pelajaran" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <select
                            id="edit_name"
                            v-model="form.name"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm bg-white font-bold text-sm py-4 px-6"
                            required
                        >
                            <optgroup label="KATEGORI AGAMA">
                                <option value="FIQIH (FIQ)">FIQIH (FIQ)</option>
                                <option value="AKIDAH AKHLAK (AA)">AKIDAH AKHLAK (AA)</option>
                                <option value="AL-QUR'AN DAN HADIST (QH)">AL-QUR'AN DAN HADIST (QH)</option>
                                <option value="BAHASA ARAB (BA)">BAHASA ARAB (BA)</option>
                                <option value="SEJARAH KEBUDAYAAN ISLAM (SKI)">SEJARAH KEBUDAYAAN ISLAM (SKI)</option>
                            </optgroup>
                            <optgroup label="KATEGORI UMUM">
                                <option value="MATEMATIKA (MTK)">MATEMATIKA (MTK)</option>
                                <option value="ILMU PENGETAHUAN ALAM (IPA)">ILMU PENGETAHUAN ALAM (IPA)</option>
                                <option value="ILMU PENGETAHUAN SOSIAL (IPS)">ILMU PENGETAHUAN SOSIAL (IPS)</option>
                                <option value="BAHASA INDONESIA (BI)">BAHASA INDONESIA (BI)</option>
                                <option value="BAHASA INGGRIS (B.ING)">BAHASA INGGRIS (B.ING)</option>
                                <option value="PENDIDIKAN JASMANI OLAHRAGA DAN KESEHATAN (PENJAS)">PENDIDIKAN JASMANI OLAHRAGA DAN KESEHATAN (PENJAS)</option>
                                <option value="PENDIDIKAN PANCASILA DAN KEWARGANEGARAAN (PPKN)">PENDIDIKAN PANCASILA DAN KEWARGANEGARAAN (PPKN)</option>
                                <option value="SENI BUDAYA (SBK)">SENI BUDAYA (SBK)</option>
                                <option value="TEKNOLOGI INFORMASI DAN KOMUNIKASI (TIK)">TEKNOLOGI INFORMASI DAN KOMUNIKASI (TIK)</option>
                            </optgroup>
                        </select>
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="edit_material" value="Materi Terkait" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <TextInput
                            id="edit_material"
                            type="text"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm"
                            v-model="form.material"
                            placeholder="Contoh: Tentang Haji, Shalat Berjamaah, dll"
                        />
                        <InputError class="mt-2" :message="form.errors.material" />
                    </div>

                    <div>
                        <InputLabel for="edit_class" value="Kelas" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <TextInput
                            id="edit_class"
                            type="text"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm"
                            v-model="form.class"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.class" />
                    </div>

                    <div>
                        <InputLabel for="edit_schedule_time" value="Jam Mengajar" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <TextInput
                            id="edit_schedule_time"
                            type="datetime-local"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm bg-white"
                            v-model="form.schedule_time"
                        />
                        <InputError class="mt-2" :message="form.errors.schedule_time" />
                    </div>

                    <div>
                        <InputLabel for="edit_user_id" value="Guru Pengampu" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <select
                            id="edit_user_id"
                            v-model="form.user_id"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm bg-white py-4 px-6"
                            required
                        >
                            <option v-for="guru in gurus" :key="guru.id" :value="guru.id">
                                {{ guru.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.user_id" />
                    </div>

                    <div class="flex items-center justify-end mt-8 pt-6 border-t border-gray-100 gap-4">
                        <button type="button" @click="closeEditSubjectModal" class="px-6 py-3 border border-gray-200 hover:bg-gray-50 text-gray-600 rounded-2xl font-bold transition-all text-sm uppercase tracking-widest">
                            Batal
                        </button>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white rounded-2xl font-bold transition-all shadow-lg shadow-yellow-500/30 text-sm uppercase tracking-widest flex items-center gap-2" :disabled="form.processing">
                            <span v-if="form.processing">Memperbarui...</span>
                            <span v-else>Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Buat Soal Modal -->
        <Modal :show="isBuatSoalModalOpen" @close="closeBuatSoalModal" maxWidth="2xl">
            <div class="p-6 sm:p-8 bg-white/90 backdrop-blur-xl">
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter">{{ editingExamId ? 'Edit Pengaturan Ujian' : 'Pengaturan Ujian Baru' }}</h2>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1" v-if="selectedSubject">Bank Soal: <span class="text-green-600">{{ selectedSubject.name }}</span> <span v-if="selectedSubject.academic_year" class="ml-2 px-2 py-0.5 bg-gray-100 rounded text-gray-500">TA: {{ selectedSubject.academic_year }}</span></p>
                    </div>
                    <button @click="closeBuatSoalModal" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitExam" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="exam_title" value="Nama Ujian" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                            <select
                                id="exam_title"
                                v-model="examForm.title"
                                class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm bg-white font-bold text-sm py-4 px-6"
                                required
                            >
                                <option value="" disabled>Pilih Nama Ujian</option>
                                <option value="UJIAN TENGAH SEMESTER (UTS)">UJIAN TENGAH SEMESTER (UTS)</option>
                                <option value="UJIAN AKHIR SEMESTER (UAS)">UJIAN AKHIR SEMESTER (UAS)</option>
                                <option value="UJIAN HARIAN">UJIAN HARIAN</option>
                                <option value="SIMULASI UJIAN">SIMULASI UJIAN</option>
                            </select>
                            <InputError class="mt-2" :message="examForm.errors.title" />
                        </div>
                        <div>
                            <InputLabel for="duration" value="Waktu Pengerjaan (Menit)" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                            <TextInput
                                id="duration"
                                type="number"
                                min="1"
                                class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm"
                                v-model="examForm.duration_minutes"
                                required
                            />
                            <InputError class="mt-2" :message="examForm.errors.duration_minutes" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <InputLabel for="start_time" value="Waktu Mulai Ujian" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                            <TextInput
                                id="start_time"
                                type="datetime-local"
                                class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm bg-white"
                                v-model="examForm.start_time"
                            />
                            <InputError class="mt-2" :message="examForm.errors.start_time" />
                            <p class="text-[10px] text-gray-400 mt-2 font-bold">Kosongkan jika bisa dikerjakan kapan saja saat aktif.</p>
                        </div>
                        <div class="flex flex-col justify-center pt-2">
                            <InputLabel value="Status Ujian" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-3" />
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" class="sr-only peer" v-model="examForm.is_active">
                                    <div class="w-12 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                                </div>
                                <span class="text-xs font-black uppercase tracking-widest transition-colors" :class="examForm.is_active ? 'text-green-600' : 'text-gray-500 group-hover:text-gray-700'">{{ examForm.is_active ? 'Aktif (Dapat Dikerjakan)' : 'Nonaktif (Belum Mulai)' }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex flex-col justify-center pt-2">
                            <InputLabel value="Acak Soal" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-3" />
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" class="sr-only peer" v-model="examForm.random_order">
                                    <div class="w-12 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-500"></div>
                                </div>
                                <span class="text-xs font-black uppercase tracking-widest transition-colors" :class="examForm.random_order ? 'text-indigo-600' : 'text-gray-500 group-hover:text-gray-700'">{{ examForm.random_order ? 'Acak Soal untuk Siswa' : 'Urut Sesuai Input' }}</span>
                            </label>
                            <p class="text-[10px] text-gray-400 mt-2 font-bold italic">* Jika aktif, urutan soal akan diacak setiap siswa membuka ujian.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="p-4 rounded-2xl border border-blue-200 bg-blue-50/50">
                            <InputLabel for="pg_weight" value="Bobot Pilihan Ganda (%)" class="font-black uppercase tracking-widest text-xs text-blue-800 mb-2" />
                            <TextInput
                                id="pg_weight"
                                type="number"
                                min="0"
                                max="100"
                                class="mt-1 block w-full rounded-xl border-blue-200 focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all bg-white"
                                v-model="examForm.pg_weight"
                                required
                            />
                            <InputError class="mt-2" :message="examForm.errors.pg_weight" />
                        </div>
                        <div class="p-4 rounded-2xl border border-yellow-200 bg-yellow-50/50">
                            <InputLabel for="essay_weight" value="Bobot Essay (%)" class="font-black uppercase tracking-widest text-xs text-yellow-800 mb-2" />
                            <TextInput
                                id="essay_weight"
                                type="number"
                                min="0"
                                max="100"
                                class="mt-1 block w-full rounded-xl border-yellow-200 focus:border-yellow-500 focus:ring focus:ring-yellow-200 transition-all bg-white"
                                v-model="examForm.essay_weight"
                                required
                            />
                            <InputError class="mt-2" :message="examForm.errors.essay_weight" />
                            <p v-if="weightsInvalid" class="text-xs text-red-500 mt-2 font-bold">Total bobot harus 100%</p>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100" v-if="!editingExamId">
                        <InputLabel value="Metode Input Soal" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-4" />
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label :class="{'border-green-500 bg-green-50 ring-2 ring-green-200': examForm.input_method === 'excel', 'border-gray-200 hover:border-green-300': examForm.input_method !== 'excel'}" class="relative flex flex-col items-center justify-center p-6 border-2 rounded-2xl cursor-pointer transition-all">
                                <input type="radio" name="input_method" value="excel" v-model="examForm.input_method" class="sr-only" />
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" :class="{'text-green-600': examForm.input_method === 'excel', 'text-gray-400': examForm.input_method !== 'excel'}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="font-bold text-sm" :class="{'text-green-700': examForm.input_method === 'excel', 'text-gray-600': examForm.input_method !== 'excel'}">Import dari Excel</span>
                            </label>

                            <label :class="{'border-yellow-500 bg-yellow-50 ring-2 ring-yellow-200': examForm.input_method === 'manual', 'border-gray-200 hover:border-yellow-300': examForm.input_method !== 'manual'}" class="relative flex flex-col items-center justify-center p-6 border-2 rounded-2xl cursor-pointer transition-all">
                                <input type="radio" name="input_method" value="manual" v-model="examForm.input_method" class="sr-only" />
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2" :class="{'text-yellow-600': examForm.input_method === 'manual', 'text-gray-400': examForm.input_method !== 'manual'}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span class="font-bold text-sm" :class="{'text-yellow-700': examForm.input_method === 'manual', 'text-gray-600': examForm.input_method !== 'manual'}">Input Manual</span>
                            </label>
                        </div>
                    </div>

                    <!-- Excel Upload Area -->
                    <div v-if="examForm.input_method === 'excel' && !editingExamId" class="mt-4 p-6 border-2 border-dashed border-green-300 bg-green-50/50 rounded-2xl text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-green-500 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        <p class="text-sm text-gray-600 font-medium mb-4">Pilih file Excel (.xlsx, .xls) yang berisi format soal.</p>
                        
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-4">
                            <a :href="route('admin.exams.template')" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-white border border-green-200 hover:bg-green-50 text-green-700 rounded-xl font-bold transition-all text-sm shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Download Template Soal
                            </a>
                        </div>

                        <input type="file" accept=".xlsx, .xls" class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-xl file:border-0
                            file:text-sm file:font-bold
                            file:bg-green-100 file:text-green-700
                            hover:file:bg-green-200 transition-all cursor-pointer" 
                            @change="e => examForm.excel_file = e.target.files[0]"
                        />
                        <InputError class="mt-2 text-left" :message="examForm.errors.excel_file" />
                    </div>

                    <div class="flex items-center justify-end mt-8 pt-6 border-t border-gray-100 gap-4">
                        <button type="button" @click="closeBuatSoalModal" class="px-6 py-3 border border-gray-200 hover:bg-gray-50 text-gray-600 rounded-2xl font-bold transition-all text-sm uppercase tracking-widest">
                            Batal
                        </button>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-gray-800 to-gray-900 hover:from-black hover:to-black text-white rounded-2xl font-bold transition-all shadow-lg shadow-gray-900/30 hover:shadow-gray-900/50 text-sm uppercase tracking-widest flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="examForm.processing || weightsInvalid">
                            <span v-if="examForm.processing">Memproses...</span>
                            <span v-else>{{ editingExamId ? 'Update Ujian' : 'Simpan & Lanjutkan' }}</span>
                            <svg v-if="!examForm.processing" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Kelola Modal -->
        <Modal :show="isKelolaModalOpen" @close="closeKelolaModal" maxWidth="3xl">
            <div class="p-6 sm:p-8 bg-white/90 backdrop-blur-xl">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Daftar Ujian</h2>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1" v-if="selectedSubject">
                            Bank Soal: <span class="text-green-600">{{ selectedSubject.name }}</span> 
                            <span v-if="selectedSubject.academic_year" class="ml-2 px-2 py-0.5 bg-gray-100 rounded text-gray-500">TA: {{ selectedSubject.academic_year }}</span>
                            <span v-if="selectedSubject.semester" class="ml-2 px-2 py-0.5 bg-yellow-100 rounded text-yellow-700">{{ selectedSubject.semester }}</span>
                        </p>

                    </div>
                    <button @click="closeKelolaModal" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div v-if="selectedSubject && selectedSubject.exams && selectedSubject.exams.length > 0" class="space-y-4">
                    <div v-for="exam in selectedSubject.exams" :key="exam.id" class="p-4 border rounded-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:border-green-300 transition-colors">
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-gray-800">{{ exam.title }}</h4>
                                <span v-if="exam.is_active" class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest bg-green-100 text-green-700 border border-green-200">Aktif</span>
                                <span v-else class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest bg-gray-100 text-gray-600 border border-gray-200">Nonaktif</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ exam.duration_minutes }} Menit | PG: {{ exam.pg_weight }}% | Essay: {{ exam.essay_weight }}%
                                <span v-if="exam.random_order" class="ml-2 text-indigo-600 font-bold">&bull; Acak Soal</span>
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5" v-if="exam.start_time">Mulai: {{ new Date(exam.start_time).toLocaleString('id-ID') }}</p>
                        </div>
                        <div class="flex gap-2 w-full md:w-auto">
                            <Link :href="route('admin.exams.questions.create', exam.id)" class="px-3 py-2 text-[10px] font-black uppercase tracking-widest bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors text-center flex-1 md:flex-none border border-blue-100">
                                Edit Soal
                            </Link>
                            <button @click="openEditExamModal(exam)" class="px-3 py-2 text-[10px] font-black uppercase tracking-widest bg-yellow-50 text-yellow-600 rounded-lg hover:bg-yellow-100 transition-colors flex-1 md:flex-none border border-yellow-100">
                                Edit Ujian
                            </button>
                            <button @click="deleteExam(exam)" class="px-3 py-2 text-[10px] font-black uppercase tracking-widest bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors flex-1 md:flex-none border border-red-100">
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="py-12 text-center bg-gray-50 rounded-3xl border border-dashed border-gray-200">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-gray-500 uppercase tracking-widest">Belum ada ujian untuk bank soal ini.</p>
                    <button @click="openBuatSoalModal(selectedSubject)" class="mt-4 px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all">
                        Buat Ujian Sekarang
                    </button>
                </div>
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
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">{{ confirmDeleteTitle }}</h3>
                </div>
                
                <p class="text-xs font-medium text-gray-500 mb-6 leading-relaxed">
                    {{ confirmDeleteMessage }}
                </p>

                <div class="flex justify-end gap-2.5">
                    <button 
                        @click="showConfirmDeleteModal = false"
                        class="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-xs font-black uppercase tracking-wider transition-colors border border-gray-200"
                    >
                        Batal
                    </button>
                    <button 
                        @click="executeConfirmDelete"
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
.subject-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(22, 163, 74, 0.15);
    box-shadow: 0 4px 24px rgba(22, 163, 74, 0.06);
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-down { animation: fadeInDown 0.3s ease-out forwards; }
</style>
