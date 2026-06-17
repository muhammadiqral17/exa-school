<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    subjects: Array,
});

const selectedSubject = ref(null);
const searchQuery = ref('');

const filteredSubjects = computed(() => {
    let result = props.subjects;
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.map(subject => {
            if (JSON.stringify(subject).toLowerCase().includes(query)) return subject;
            const filteredExams = subject.exams ? subject.exams.filter(exam => JSON.stringify(exam).toLowerCase().includes(query)) : [];
            if (filteredExams.length > 0) return { ...subject, exams: filteredExams };
            return null;
        }).filter(s => s !== null);
    }
    return result;
});

const formatSchedule = (exam) => {
    if (!exam.start_time) return 'Tidak Terjadwal';
    const start = new Date(exam.start_time);
    const end = new Date(start.getTime() + (exam.duration_minutes || 0) * 60000);
    
    const dOptions = { day: '2-digit', month: 'short', year: 'numeric' };
    const tOptions = { hour: '2-digit', minute: '2-digit' };
    
    const dateStr = start.toLocaleDateString('id-ID', dOptions);
    const startStr = start.toLocaleTimeString('id-ID', tOptions);
    const endStr = end.toLocaleTimeString('id-ID', tOptions);
    
    return `${dateStr} | ${startStr} - ${endStr}`;
};

const groupedSubjects = computed(() => {
    const groups = {};
    filteredSubjects.value.forEach(subject => {
        const name = subject.name;
        if (!groups[name]) {
            groups[name] = {
                id: subject.id,
                name: name,
                exams: [],
                classes: new Set(),
            };
        }
        if (subject.exams) {
            const examsWithMeta = subject.exams.map(e => ({ 
                ...e, 
                subject_code: subject.code, 
                subject_class: subject.class 
            }));
            groups[name].exams.push(...examsWithMeta);
        }
        if (subject.class) groups[name].classes.add(subject.class);
    });
    
    return Object.values(groups).map(g => ({
        ...g,
        displayClasses: Array.from(g.classes).sort().join(', '),
        displayYears: Array.from(new Set(props.subjects.filter(s => s.name === g.name).map(s => s.academic_year).filter(Boolean))).sort().join(' / '),
        displaySemesters: Array.from(new Set(props.subjects.filter(s => s.name === g.name).map(s => s.semester).filter(Boolean))).join(' / '),
        displayMaterials: Array.from(new Set(props.subjects.filter(s => s.name === g.name).map(s => s.material).filter(Boolean))).join(' / ')
    }));
});





</script>

<template>
    <Head title="Kelola Ujian" />
    <AuthenticatedLayout>
        <template #header>
            Guru: <span class="text-gradient-gy capitalize">Kelola Ujian</span>
        </template>

        <div class="space-y-6 sm:space-y-10">
            <!-- Header Section -->
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 tracking-tighter">Daftar Ujian per Kelas</h2>
                    <p class="text-sm text-gray-500 mt-1">Pilih ujian untuk melihat siswa yang sedang mengerjakan dan menilai hasilnya.</p>
                </div>
                
                <div class="flex flex-wrap gap-2 items-center">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input v-model="searchQuery" type="text" placeholder="Cari ujian atau pelajaran..." class="pl-10 pr-4 py-3 border border-gray-200 rounded-2xl text-sm focus:ring-green-500 focus:border-green-500 w-full sm:w-64 transition-all shadow-sm">
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="groupedSubjects.length === 0 || !groupedSubjects.some(s => s.exams && s.exams.length > 0)" class="p-12 text-center bg-white/50 backdrop-blur-sm rounded-[2rem] border border-green-200 border-dashed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-green-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h3 class="text-lg font-black text-gray-800">Belum Ada Ujian</h3>
                <p class="text-gray-500 mt-2">Admin belum membuat ujian untuk mata pelajaran Anda.</p>
            </div>

            <div v-else>
                <!-- Level 1: Subject Grid -->
                <div v-if="!selectedSubject" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                    <div v-for="group in groupedSubjects" :key="group.name" @click="selectedSubject = group" class="cursor-pointer group relative">
                        <div class="absolute -inset-1 bg-gradient-to-r from-green-600 to-yellow-500 rounded-[2.5rem] blur opacity-10 group-hover:opacity-30 transition duration-500"></div>
                        <div class="relative bg-gradient-to-br from-white to-green-50/10 backdrop-blur-xl rounded-[2.5rem] p-7 border border-white shadow-xl shadow-green-900/5 flex flex-col h-full transform transition-all duration-500 group-hover:-translate-y-2 group-hover:shadow-green-500/10">
                            <!-- Icon & Count -->
                            <div class="flex justify-between items-start mb-6">
                                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center shadow-lg shadow-green-500/20 group-hover:rotate-12 transition-transform duration-500">
                                    <span class="text-2xl font-black text-white">{{ group.name.charAt(0) }}</span>
                                </div>
                                <div class="px-3 py-1.5 bg-white rounded-xl shadow-sm border border-gray-50 flex flex-col items-end">
                                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none">Ujian</span>
                                    <span class="text-xl font-black text-green-600 leading-none mt-1">{{ group.exams ? group.exams.length : 0 }}</span>
                                </div>
                            </div>
                            
                            <h3 class="text-xl font-black text-gray-800 group-hover:text-green-600 transition-colors tracking-tighter leading-tight">{{ group.name }}</h3>
                            
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="px-2.5 py-1 bg-white border border-gray-100 text-gray-500 rounded-lg text-[9px] font-black uppercase tracking-widest shadow-sm">
                                    Kelas {{ group.displayClasses }}
                                </span>
                                <span v-if="group.displayYears" class="px-2.5 py-1 bg-gray-50 text-gray-400 rounded-lg text-[9px] font-black uppercase tracking-widest border border-gray-100 flex items-center gap-1">
                                    {{ group.displayYears }}
                                    <span v-if="group.displaySemesters" class="text-green-600">&bull; {{ group.displaySemesters }}</span>
                                </span>
                                <span v-if="group.displayMaterials" class="px-2.5 py-1 bg-blue-50 text-blue-600 rounded-lg text-[9px] font-black uppercase tracking-widest border border-blue-100/50 flex items-center gap-1">
                                    Materi: {{ group.displayMaterials }}
                                </span>
                            </div>

                            
                            <!-- Footer -->
                            <div class="mt-8 pt-6 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest group-hover:text-green-600 transition-colors">Buka Pelajaran</span>
                                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center group-hover:bg-green-500 group-hover:text-white transition-all duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transform transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Level 2: Exam Boxes for Selected Subject -->
                <div v-else class="space-y-8 animate-fade-in">
                    <div class="flex items-center gap-4">
                        <button @click="selectedSubject = null" class="p-3 bg-white border border-gray-100 rounded-2xl hover:bg-green-50 hover:border-green-200 transition-all shadow-sm group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400 group-hover:text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </button>
                        <div>
                            <h3 class="text-3xl font-black text-gray-800 tracking-tighter">{{ selectedSubject.name }}</h3>
                            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Daftar Semua Ujian</p>
                        </div>
                    </div>



                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <div v-for="exam in selectedSubject.exams" :key="exam.id" class="relative group">
                            <div class="absolute -inset-0.5 bg-gradient-to-r from-green-500 to-yellow-500 rounded-[2.5rem] blur opacity-0 group-hover:opacity-20 transition duration-500"></div>
                            <div class="relative bg-gradient-to-br from-white via-white to-green-50/30 backdrop-blur-xl border border-white shadow-2xl shadow-green-900/5 rounded-[2.5rem] p-8 h-full flex flex-col transition-all duration-300 group-hover:border-green-100">
                                <div class="flex justify-between items-center mb-8">
                                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-lg shadow-gray-200/50 group-hover:scale-110 transition-transform duration-500">
                                        <div class="w-8 h-8 rounded-lg bg-green-500 flex items-center justify-center text-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex flex-col items-end gap-1">
                                        <span v-if="exam.is_active" class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-green-500 text-white shadow-lg shadow-green-500/30">Aktif</span>
                                        <span v-else class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-gray-400 text-white shadow-lg shadow-gray-400/30">Nonaktif</span>
                                    </div>
                                </div>

                                <h4 class="text-2xl font-black text-gray-800 mb-2 tracking-tighter leading-tight group-hover:text-green-600 transition-colors">{{ exam.title }}</h4>
                                <div class="flex flex-wrap gap-2 mb-6">
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-[9px] font-black uppercase tracking-widest">{{ exam.subject_code }}</span>
                                    <template v-for="subject in subjects" :key="subject.id">
                                        <div v-if="subject.code === exam.subject_code && subject.class === exam.subject_class" class="flex items-center gap-2 flex-wrap">
                                            <span v-if="subject.material" class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded text-[9px] font-black uppercase tracking-widest border border-blue-100">{{ subject.material }}</span>
                                            <span v-if="subject.academic_year" class="px-2 py-0.5 bg-green-50 text-green-600 rounded text-[9px] font-black uppercase tracking-widest border border-green-100">{{ subject.academic_year }}</span>
                                            <span v-if="subject.semester" class="px-2 py-0.5 bg-yellow-50 text-yellow-600 rounded text-[9px] font-black uppercase tracking-widest border border-yellow-100">{{ subject.semester }}</span>
                                        </div>
                                    </template>
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-600 rounded text-[9px] font-black uppercase tracking-widest border border-blue-200/50">Kelas {{ exam.subject_class }}</span>
                                </div>


                                <!-- Schedule Section -->
                                <div class="mt-auto mb-8 bg-white/50 border border-gray-100 rounded-3xl p-4 group-hover:bg-green-50/50 group-hover:border-green-100 transition-colors duration-500">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center text-green-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-0.5">Jadwal Pelaksanaan</p>
                                            <p class="text-[11px] font-bold text-gray-700">{{ formatSchedule(exam) }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 pt-4 border-t border-gray-100/50 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
                                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ exam.duration_minutes }} Menit</span>
                                        </div>
                                        <span class="text-[10px] font-black text-green-600 uppercase tracking-widest">PG {{ exam.pg_weight }}% &bull; ESSAY {{ exam.essay_weight }}%</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <Link :href="route('teacher.exams.monitoring', exam.id)" class="px-4 py-4 bg-gray-800 hover:bg-black text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-gray-900/10 flex items-center justify-center gap-2 group/btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 group-hover/btn:text-green-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        Monitor
                                    </Link>
                                    <Link :href="route('teacher.exams.results', exam.id)" class="px-4 py-4 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all shadow-xl shadow-green-500/30 flex items-center justify-center">
                                        Lihat Hasil
                                    </Link>
                                </div>
                            </div>
                        </div>
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
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}
</style>
