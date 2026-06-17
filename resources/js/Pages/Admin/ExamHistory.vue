<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    results: Object,
    classes: Array,
    academicYears: Array,
    semesters: Array,
    subjects: Array,
    students: Array,
    filters: Object,
});

// Filter states
const search = ref(props.filters.search || '');
const selectedClass = ref(props.filters.class || '');
const selectedYear = ref(props.filters.academic_year || '');
const selectedSubjectName = ref(props.filters.subject_name || '');
const selectedSemester = ref(props.filters.semester || '');
const selectedScoreRange = ref(props.filters.score_range || '');

const sortField = ref(props.filters.sort || 'results.created_at');
const sortOrder = ref(props.filters.direction || 'desc');

// Apply all filters and route
const applyFilters = () => {
    router.get(route('admin.exams.history'), {
        search: search.value,
        class: selectedClass.value,
        academic_year: selectedYear.value,
        subject_name: selectedSubjectName.value,
        semester: selectedSemester.value,
        score_range: selectedScoreRange.value,
        sort: sortField.value,
        direction: sortOrder.value
    }, {
        preserveState: true,
        replace: true,
        preserveScroll: true
    });
};

// Toggle sorting
const toggleSort = (field) => {
    const newOrder = sortField.value === field && sortOrder.value === 'asc' ? 'desc' : 'asc';
    sortField.value = field;
    sortOrder.value = newOrder;
    applyFilters();
};

// Reset all filters
const resetFilters = () => {
    search.value = '';
    selectedClass.value = '';
    selectedYear.value = '';
    selectedSubjectName.value = '';
    selectedSemester.value = '';
    selectedScoreRange.value = '';
    applyFilters();
};

// Trigger filter application when dropdowns change
watch([selectedClass, selectedYear, selectedSubjectName, selectedSemester, selectedScoreRange], () => {
    applyFilters();
});

// Debounced watch for text inputs
let searchTimeout;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

const triggerDirectExport = () => {
    window.location.href = route('admin.exams.history.export', {
        search: search.value,
        class: selectedClass.value,
        academic_year: selectedYear.value,
        subject_name: selectedSubjectName.value,
        semester: selectedSemester.value,
        score_range: selectedScoreRange.value
    });
};
</script>

<template>
    <Head title="Riwayat Hasil Ujian Siswa" />
    <AuthenticatedLayout>
        <template #header>
            Manajemen: <span class="text-gradient-gy capitalize">Riwayat Hasil Ujian</span>
        </template>

        <div class="space-y-6 sm:space-y-10">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 tracking-tighter">Riwayat Ujian Siswa</h2>
                    <p class="text-sm text-gray-500 mt-1">Lihat dan filter hasil ujian, serta unduh rekap data excel.</p>
                </div>
                
                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <div class="relative w-48 sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input v-model="search" type="text" placeholder="Cari hasil ujian..." class="pl-10 pr-4 py-3 border border-gray-200 rounded-2xl text-sm focus:ring-green-500 focus:border-green-500 w-full transition-all shadow-sm">
                    </div>
                    <button 
                        @click="triggerDirectExport" 
                        class="flex items-center gap-2 px-4 py-3 bg-white border border-green-200 hover:bg-green-50 text-green-700 rounded-2xl font-bold transition-all text-sm shadow-sm justify-center whitespace-nowrap"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Rekap Excel
                    </button>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="bg-white/95 border border-green-200/60 shadow-lg shadow-green-500/10 rounded-[2rem] p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
                    <!-- Class Filter -->
                    <div>
                        <InputLabel value="Kelas" class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2 block" />
                        <select v-model="selectedClass" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-green-500 focus:border-green-500 w-full transition-all shadow-sm bg-white font-medium">
                            <option value="">Semua Kelas</option>
                            <option v-for="c in classes" :key="c" :value="c">Kelas {{ c }}</option>
                        </select>
                    </div>

                    <!-- Academic Year Filter -->
                    <div>
                        <InputLabel value="Tahun Ajaran" class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2 block" />
                        <select v-model="selectedYear" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-green-500 focus:border-green-500 w-full transition-all shadow-sm bg-white font-medium">
                            <option value="">Semua TA</option>
                            <option v-for="y in academicYears" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>

                    <!-- Subject Filter -->
                    <div>
                        <InputLabel value="Mata Pelajaran" class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2 block" />
                        <select v-model="selectedSubjectName" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-green-500 focus:border-green-500 w-full transition-all shadow-sm bg-white font-medium">
                            <option value="">Semua Mapel</option>
                            <option v-for="s in subjects" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>

                    <!-- Semester Filter -->
                    <div>
                        <InputLabel value="Semester" class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2 block" />
                        <select v-model="selectedSemester" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-green-500 focus:border-green-500 w-full transition-all shadow-sm bg-white font-medium">
                            <option value="">Semua Semester</option>
                            <option v-for="sem in semesters" :key="sem" :value="sem">{{ sem }}</option>
                        </select>
                    </div>

                    <!-- Score Range Filter -->
                    <div>
                        <InputLabel value="Nilai Ujian" class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2 block" />
                        <select v-model="selectedScoreRange" class="px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-green-500 focus:border-green-500 w-full transition-all shadow-sm bg-white font-medium">
                            <option value="">Semua Nilai</option>
                            <option value=">80">Sangat Baik (&gt; 80)</option>
                            <option value="60-80">Cukup (60 - 80)</option>
                            <option value="<60">Kurang (&lt; 60)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Reset Button -->
                <div class="flex justify-end mt-4">
                    <button 
                        @click="resetFilters" 
                        class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-green-700 transition-colors flex items-center gap-1"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18" />
                        </svg>
                        Reset Filter
                    </button>
                </div>
            </div>

<!-- Empty State -->
            <div v-if="results.data.length === 0" class="p-12 text-center bg-white/50 backdrop-blur-sm rounded-[2rem] border border-green-200 border-dashed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-green-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h3 class="text-lg font-black text-gray-800">Riwayat Ujian Kosong</h3>
                <p class="text-gray-500 mt-2">Tidak ditemukan hasil ujian siswa yang cocok dengan filter saat ini.</p>
            </div>

            <!-- Table Card -->
            <div v-else class="bg-white/95 border border-green-200/60 shadow-lg shadow-green-500/10 rounded-[2rem] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-green-50/50 border-b border-green-100">
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">No</th>
                                <th @click="toggleSort('nis_nik')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        NIS
                                        <svg v-if="sortField === 'nis_nik'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                                <th @click="toggleSort('name')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        Nama Siswa
                                        <svg v-if="sortField === 'name'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                    </div>
                                </th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">Tahun Ajaran</th>
                                <th @click="toggleSort('subject')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        Mata Pelajaran
                                        <svg v-if="sortField === 'subject'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">Ujian</th>
                                <th @click="toggleSort('score')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        Nilai Ujian
                                        <svg v-if="sortField === 'score'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">Semester</th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">Tanggal Selesai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/50">
                            <tr v-for="(result, index) in results.data" :key="result.id" class="hover:bg-green-50/30 transition-colors">
                                <td class="p-4 text-sm font-medium text-gray-500">{{ results.from + index }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">{{ result.user?.nis_nik || '-' }}</td>
                                <td class="p-4 text-sm font-bold text-gray-800">{{ result.user?.name || '-' }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">{{ result.exam?.subject?.class || '-' }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">{{ result.exam?.subject?.academic_year || '-' }}</td>
                                <td class="p-4 text-sm font-bold text-green-700">{{ result.exam?.subject?.name || '-' }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">{{ result.exam?.title || '-' }}</td>
                                <td class="p-4 text-sm">
                                    <span 
                                        :class="[
                                            'px-2.5 py-1 rounded-lg font-black text-xs',
                                            result.score >= 80 ? 'bg-green-100 text-green-700 border border-green-200' :
                                            result.score >= 60 ? 'bg-yellow-100 text-yellow-700 border border-yellow-200' :
                                            'bg-red-100 text-red-700 border border-red-200'
                                        ]"
                                    >
                                        {{ result.score }}
                                    </span>
                                </td>
                                <td class="p-4 text-sm font-semibold text-gray-600">
                                    <span class="px-2 py-0.5 bg-slate-100 border border-slate-200 text-slate-700 rounded-md text-[10px]">
                                        {{ result.exam?.subject?.semester || '-' }}
                                    </span>
                                </td>
                                <td class="p-4 text-sm font-medium text-gray-500">
                                    {{ result.end_time ? new Date(result.end_time).toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit'}) : '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <Pagination :links="results.links" />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.text-gradient-gy {
    background: linear-gradient(135deg, #16a34a 0%, #ca8a04 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
</style>
