<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    results: {
        type: Array,
        required: true,
    },
});

const searchQuery = ref('');
const selectedClass = ref(null);
const selectedSubject = ref(null);

// Robust class matching helper supporting digits, roman numerals, and text prefixes
const classMatches = (resultClass, targetNum) => {
    if (!resultClass) return false;
    const s = String(resultClass).toLowerCase().trim();
    if (String(targetNum) === '7') {
        return /(\D|^)7(\D|$)/.test(s) || /\bvii\b/.test(s);
    }
    if (String(targetNum) === '8') {
        return /(\D|^)8(\D|$)/.test(s) || /\bviii\b/.test(s);
    }
    if (String(targetNum) === '9') {
        return /(\D|^)9(\D|$)/.test(s) || /\bix\b/.test(s);
    }
    return false;
};

// Pre-compute academic years for each class box to avoid running array filter/regex inside template render loops
const classYears = computed(() => {
    const yearsMap = {
        '7': 'Belum Ada TA',
        '8': 'Belum Ada TA',
        '9': 'Belum Ada TA',
    };
    ['7', '8', '9'].forEach(classNum => {
        const years = props.results
            .filter(r => classMatches(r.class, classNum))
            .map(r => r.academic_year)
            .filter((y, idx, self) => y && y !== '-' && self.indexOf(y) === idx);
        if (years.length > 0) {
            yearsMap[classNum] = years.join(', ');
        }
    });
    return yearsMap;
});

// Select class box handler
const selectClass = (classNum) => {
    selectedClass.value = String(classNum);
    selectedSubject.value = null;
    searchQuery.value = '';
};

// Select subject handler
const selectSubject = (subjectName) => {
    selectedSubject.value = subjectName;
    searchQuery.value = '';
};

// Compute subjects with results in the active class box
const subjectsForSelectedClass = computed(() => {
    if (!selectedClass.value) return [];
    return props.results
        .filter(r => classMatches(r.class, selectedClass.value))
        .map(r => r.subject_name)
        .filter((name, idx, self) => name && self.indexOf(name) === idx);
});

// Compute exam results matching active class box and selected subject
const finalFilteredResults = computed(() => {
    if (!selectedClass.value || !selectedSubject.value) return [];
    
    let baseResults = props.results.filter(r => 
        classMatches(r.class, selectedClass.value) && r.subject_name === selectedSubject.value
    );

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        return baseResults.filter(result => 
            JSON.stringify(result).toLowerCase().includes(query)
        );
    }
    
    return baseResults;
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Nilai Ujian" />
        <template #header>
            Daftar <span class="text-gradient-gy capitalize">Nilai Ujian</span>
        </template>

        <div class="max-w-7xl mx-auto space-y-8">
            <div class="form-card p-8 sm:p-12 rounded-[2.5rem] relative overflow-hidden">

                
                <div class="mb-10 text-center sm:text-left">
                    <h2 class="text-2xl font-black text-gray-800 uppercase tracking-tighter">Riwayat <span class="text-green-600">Nilai Ujian</span></h2>
                    <p class="text-gray-400 text-xs mt-2 uppercase tracking-widest font-bold">Pilih kelas dan mata pelajaran untuk melihat nilai Anda</p>
                </div>

                <!-- 3 Class Boxes -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-10">
                    <!-- Box Kelas 7 -->
                    <div 
                        @click="selectClass('7')"
                        :class="[
                            'p-6 rounded-[2rem] border transition-all cursor-pointer relative overflow-hidden group',
                            selectedClass === '7' 
                                ? 'bg-gradient-to-br from-green-500 to-green-600 border-green-600 text-white shadow-xl shadow-green-500/20' 
                                : 'bg-white border-gray-100 hover:border-green-300 shadow-md hover:shadow-lg'
                        ]"
                    >
                        <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12 transition-transform group-hover:scale-125 duration-500"></div>
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <span :class="['text-[10px] font-black uppercase tracking-widest block mb-1', selectedClass === '7' ? 'text-green-100' : 'text-gray-400']">MTS Class</span>
                                <h3 class="text-2xl font-black tracking-tight">Kelas 7</h3>
                                <p :class="['text-xs mt-2 font-bold uppercase tracking-widest', selectedClass === '7' ? 'text-green-200' : 'text-green-600']">
                                    TA: {{ classYears['7'] }}
                                </p>
                            </div>
                            <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center font-black text-2xl shadow-inner', selectedClass === '7' ? 'bg-white/20 text-white' : 'bg-green-50 text-green-700']">
                                VII
                            </div>
                        </div>
                    </div>

                    <!-- Box Kelas 8 -->
                    <div 
                        @click="selectClass('8')"
                        :class="[
                            'p-6 rounded-[2rem] border transition-all cursor-pointer relative overflow-hidden group',
                            selectedClass === '8' 
                                ? 'bg-gradient-to-br from-green-500 to-green-600 border-green-600 text-white shadow-xl shadow-green-500/20' 
                                : 'bg-white border-gray-100 hover:border-green-300 shadow-md hover:shadow-lg'
                        ]"
                    >
                        <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12 transition-transform group-hover:scale-125 duration-500"></div>
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <span :class="['text-[10px] font-black uppercase tracking-widest block mb-1', selectedClass === '8' ? 'text-green-100' : 'text-gray-400']">MTS Class</span>
                                <h3 class="text-2xl font-black tracking-tight">Kelas 8</h3>
                                <p :class="['text-xs mt-2 font-bold uppercase tracking-widest', selectedClass === '8' ? 'text-green-200' : 'text-green-600']">
                                    TA: {{ classYears['8'] }}
                                </p>
                            </div>
                            <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center font-black text-2xl shadow-inner', selectedClass === '8' ? 'bg-white/20 text-white' : 'bg-green-50 text-green-700']">
                                VIII
                            </div>
                        </div>
                    </div>

                    <!-- Box Kelas 9 -->
                    <div 
                        @click="selectClass('9')"
                        :class="[
                            'p-6 rounded-[2rem] border transition-all cursor-pointer relative overflow-hidden group',
                            selectedClass === '9' 
                                ? 'bg-gradient-to-br from-green-500 to-green-600 border-green-600 text-white shadow-xl shadow-green-500/20' 
                                : 'bg-white border-gray-100 hover:border-green-300 shadow-md hover:shadow-lg'
                        ]"
                    >
                        <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12 transition-transform group-hover:scale-125 duration-500"></div>
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <span :class="['text-[10px] font-black uppercase tracking-widest block mb-1', selectedClass === '9' ? 'text-green-100' : 'text-gray-400']">MTS Class</span>
                                <h3 class="text-2xl font-black tracking-tight">Kelas 9</h3>
                                <p :class="['text-xs mt-2 font-bold uppercase tracking-widest', selectedClass === '9' ? 'text-green-200' : 'text-green-600']">
                                    TA: {{ classYears['9'] }}
                                </p>
                            </div>
                            <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center font-black text-2xl shadow-inner', selectedClass === '9' ? 'bg-white/20 text-white' : 'bg-green-50 text-green-700']">
                                IX
                            </div>
                        </div>
                    </div>
                </div>

                <!-- If no class is selected yet -->
                <div v-if="!selectedClass" class="text-center py-16 bg-white/50 border border-dashed border-green-200 rounded-[2.5rem] p-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-green-400 mb-4 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                    </svg>
                    <h3 class="text-lg font-black text-gray-700 uppercase tracking-wider">Pilih Kelas</h3>
                    <p class="text-gray-400 mt-2 font-medium text-sm">Silakan klik salah satu box kelas di atas untuk melihat mata pelajaran dan riwayat nilai Anda.</p>
                </div>

                <!-- Dynamic display after class is selected -->
                <div v-else class="space-y-8 animate-fade-in-down">
                    <!-- Subject Selection Area -->
                    <div class="bg-white border border-gray-100 shadow-sm rounded-[2.5rem] p-6 sm:p-8">
                        <div class="flex items-center justify-between mb-6 border-b pb-4">
                            <div>
                                <h3 class="text-lg font-black text-gray-800 uppercase tracking-tighter">Mata Pelajaran (Kelas {{ selectedClass }})</h3>
                                <p class="text-xs text-gray-400 mt-0.5">Pilih mata pelajaran untuk menampilkan nilai ujian</p>
                            </div>
                            <button @click="selectedClass = null; selectedSubject = null" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors uppercase tracking-wider">
                                Tutup Kelas
                            </button>
                        </div>

                        <!-- List of subjects available in that class -->
                        <div v-if="subjectsForSelectedClass.length === 0" class="text-center py-6">
                            <p class="text-gray-400 font-medium text-sm">Belum ada riwayat mata pelajaran untuk Kelas {{ selectedClass }} saat ini.</p>
                        </div>
                        <div v-else class="flex flex-wrap gap-2.5">
                            <button 
                                v-for="sub in subjectsForSelectedClass" 
                                :key="sub"
                                @click="selectSubject(sub)"
                                :class="[
                                    'px-5 py-3 rounded-2xl text-xs font-black transition-all border shadow-sm uppercase tracking-widest',
                                    selectedSubject === sub
                                        ? 'bg-gradient-to-r from-green-500 to-green-600 border-green-600 text-white shadow-lg shadow-green-500/20'
                                        : 'bg-white border-gray-200 hover:border-green-300 text-gray-700 hover:text-green-700'
                                ]"
                            >
                                {{ sub }}
                            </button>
                        </div>
                    </div>

                    <!-- Exam Results List (Shown only when subject is selected) -->
                    <div v-if="selectedSubject" class="space-y-6 animate-fade-in-down">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                            <div>
                                <h3 class="text-xl font-black text-gray-800 uppercase tracking-tighter">
                                    Hasil Ujian: <span class="text-green-600">{{ selectedSubject }}</span>
                                </h3>
                                <p class="text-xs text-gray-400 mt-1 uppercase tracking-widest font-bold">Daftar nilai ujian yang telah Anda selesaikan di Kelas {{ selectedClass }}</p>
                            </div>
                            
                            <!-- Local Search Box -->
                            <div class="relative w-full sm:w-64">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input v-model="searchQuery" type="text" placeholder="Cari dalam hasil ini..." class="pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-green-500 focus:border-green-500 w-full transition-all shadow-sm">
                            </div>
                        </div>

                        <!-- Exam Cards Grid -->
                        <div v-if="finalFilteredResults.length === 0" class="text-center py-12 bg-white/50 border border-dashed border-gray-200 rounded-[2.5rem]">
                            <h4 class="text-sm font-black text-gray-500 uppercase tracking-wider">Hasil Tidak Ditemukan</h4>
                            <p class="text-xs text-gray-400 mt-1">Tidak ditemukan hasil ujian yang cocok dengan filter atau kata kunci pencarian Anda.</p>
                        </div>
                        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 relative z-10">
                            <div v-for="result in finalFilteredResults" :key="result.id" class="bg-white border border-gray-200 rounded-3xl p-6 hover:shadow-xl hover:border-green-300 transition-shadow transition-colors duration-300 group relative overflow-hidden">
                                
                                <div class="relative z-10 flex flex-col h-full">
                                    <div class="flex items-start justify-between mb-4">
                                        <div>
                                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                                <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-700 text-[10px] font-black uppercase tracking-widest rounded-full">
                                                    {{ result.subject_name }}
                                                </span>
                                                <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 text-[10px] font-black uppercase tracking-widest rounded-full">
                                                    Kelas {{ result.class }}
                                                </span>
                                                <span v-if="result.material && result.material !== '-'" class="inline-block px-3 py-1 bg-blue-50 text-blue-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-blue-100">
                                                    Materi: {{ result.material }}
                                                </span>
                                                <span v-if="result.academic_year && result.academic_year !== '-'" class="inline-block px-3 py-1 bg-green-100 text-green-700 text-[10px] font-black uppercase tracking-widest rounded-full">
                                                    {{ result.academic_year }}
                                                </span>
                                                <span v-if="result.semester && result.semester !== '-'" class="inline-block px-3 py-1 bg-yellow-100 text-yellow-700 text-[10px] font-black uppercase tracking-widest rounded-full">
                                                    {{ result.semester }}
                                                </span>
                                            </div>
                                            <h3 class="font-black text-gray-800 text-lg leading-tight uppercase tracking-tight">{{ result.exam_title }}</h3>
                                            <div class="flex items-center gap-1 mt-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Guru: {{ result.teacher_name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                        <div class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2 border-b border-gray-200 pb-1">Waktu Pengerjaan Anda</div>
                                        <div class="flex items-center gap-2 text-[10px] font-bold text-blue-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>Dikirim: {{ result.created_at }}</span>
                                        </div>
                                    </div>

                                    <div class="mt-3 p-4 bg-green-50/30 rounded-2xl border border-green-100/50">
                                        <div class="text-[9px] font-black text-green-600/50 uppercase tracking-widest mb-2 border-b border-green-100 pb-1">Jadwal Resmi Ujian</div>
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2 text-[10px] font-bold text-gray-600">
                                                <div class="w-1.5 h-1.5 rounded-full bg-green-400"></div>
                                                <span class="opacity-60">Mulai:</span> {{ result.start_time }}
                                            </div>
                                            <div class="flex items-center gap-2 text-[10px] font-bold text-gray-600">
                                                <div class="w-1.5 h-1.5 rounded-full bg-red-400"></div>
                                                <span class="opacity-60">Selesai:</span> {{ result.end_time }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-6 pt-6 border-t border-gray-100 flex items-center justify-between">
                                        <span class="text-xs font-black text-gray-400 uppercase tracking-widest">Skor Akhir</span>
                                        <div class="text-3xl font-black text-green-600">{{ result.score }}<span class="text-sm text-gray-400 ml-1">/ 100</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- If class is selected, but subject is not -->
                    <div v-else class="text-center py-16 bg-white/50 border border-dashed border-gray-200 rounded-[2.5rem] p-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-green-400 mb-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <h3 class="text-lg font-black text-gray-700 uppercase tracking-wider">Pilih Mata Pelajaran</h3>
                        <p class="text-gray-400 mt-2 font-medium text-sm">Silakan klik salah satu mata pelajaran di atas untuk melihat nilai ujian Anda.</p>
                    </div>
                </div>
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
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(22, 163, 74, 0.15);
    box-shadow: 0 8px 40px rgba(22, 163, 74, 0.09);
}
</style>
