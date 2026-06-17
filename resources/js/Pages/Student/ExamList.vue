<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const props = defineProps({
    exams: Array,
});

const page = usePage();
const userId = computed(() => page.props.auth.user.id);

const ongoingExams = ref({});
const lockedExams = ref({});

const searchQuery = ref('');

const filteredExams = computed(() => {
    if (!searchQuery.value) return props.exams;
    const query = searchQuery.value.toLowerCase();
    return props.exams.filter(exam => 
        JSON.stringify(exam).toLowerCase().includes(query)
    );
});

onMounted(() => {
    // Clean up old keys that don't have user ID suffix to prevent cross-user leakage
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && (key.startsWith('exam_locked_') || key.startsWith('exam_state_')) && !key.includes('_u')) {
            localStorage.removeItem(key);
            i--; // Adjust index after removal
        }
    }

    props.exams.forEach(exam => {
        const userLockedKey = `exam_locked_${exam.id}_u${userId.value}`;
        const userStateKey = `exam_state_${exam.id}_u${userId.value}`;

        // PRIORITIZE database status (is_locked)
        if (exam.is_locked) {
            lockedExams.value[exam.id] = true;
            localStorage.setItem(userLockedKey, 'true'); // Sync local
        } else {
            // If it was previously locked locally but now DB says it's unlocked, 
            // it means the teacher reactivated it. Set a bypass flag.
            if (localStorage.getItem(userLockedKey)) {
                localStorage.setItem(`exam_bypass_${exam.id}_u${userId.value}`, 'true');
            }
            
            localStorage.removeItem(userLockedKey);
            lockedExams.value[exam.id] = false;

            if (localStorage.getItem(userStateKey)) {
                ongoingExams.value[exam.id] = true;
            }
        }
    });
});
</script>

<template>
    <Head title="Daftar Ujian" />
    <AuthenticatedLayout>
        <template #header>Ikuti <span class="text-gradient-gy">Ujian</span></template>

        <div class="space-y-6 sm:space-y-10">
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 tracking-tighter">Ujian Tersedia</h2>
                    <p class="text-sm text-gray-500 mt-1">Pilih ujian yang ingin Anda kerjakan.</p>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input v-model="searchQuery" type="text" placeholder="Cari ujian..." class="pl-10 pr-4 py-3 border border-gray-200 rounded-2xl text-sm focus:ring-green-500 focus:border-green-500 w-full sm:w-64 transition-all shadow-sm">
                    </div>
                </div>
            </div>

            <div v-if="filteredExams.length === 0" class="p-12 text-center bg-white/50 backdrop-blur-sm rounded-[2rem] border border-green-200 border-dashed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-green-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <h3 class="text-lg font-black text-gray-800">Belum Ada Ujian Tersedia</h3>
                <p class="text-gray-500 mt-2">Ujian akan muncul di sini setelah admin/guru mengaktifkannya.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="exam in filteredExams" :key="exam.id" class="exam-card p-6 rounded-[2rem] hover:-translate-y-1 transition-all duration-300 flex flex-col h-full">
                    <div class="flex flex-wrap gap-2 items-center mb-2">
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-yellow-100 text-yellow-700 border border-yellow-200">
                            {{ exam.subject_name }}
                        </span>
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-blue-100 text-blue-700 border border-blue-200">
                            Kelas {{ exam.class }}
                        </span>
                        <span v-if="exam.material" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-blue-50 text-blue-600 border border-blue-100">
                            Materi: {{ exam.material }}
                        </span>
                        <div class="flex items-center gap-1">
                            <span v-if="exam.academic_year" class="px-2 py-0.5 bg-green-50 text-green-600 rounded text-[9px] font-black uppercase tracking-widest border border-green-100">{{ exam.academic_year }}</span>
                            <span v-if="exam.semester" class="px-2 py-0.5 bg-yellow-50 text-yellow-600 rounded text-[9px] font-black uppercase tracking-widest border border-yellow-100">{{ exam.semester }}</span>
                        </div>
                    </div>

                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-xl font-black text-gray-800 leading-tight">{{ exam.title }}</h3>
                        <div class="flex items-center gap-1 text-xs text-gray-400 shrink-0 ml-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ exam.duration_minutes }} Menit
                        </div>
                    </div>

                    <div class="flex items-center gap-1 -mt-2 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Guru: {{ exam.teacher_name }}</p>
                    </div>
                    
                    <div v-if="exam.start_time" class="mb-4 space-y-1">
                        <div class="flex items-center gap-2 text-[10px] font-bold text-gray-500">
                            <span class="text-green-600 uppercase tracking-widest">Mulai:</span>
                            <span>{{ exam.start_time }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-[10px] font-bold text-gray-500">
                            <span class="text-red-600 uppercase tracking-widest">Selesai:</span>
                            <span>{{ exam.end_time }}</span>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap gap-3 mt-4 text-xs text-gray-500">
                        <div class="flex items-center gap-1">
                            <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                            PG {{ exam.pg_weight }}%
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-2 h-2 rounded-full bg-yellow-400"></div>
                            Essay {{ exam.essay_weight }}%
                        </div>
                        <div class="flex items-center gap-1">
                            <div class="w-2 h-2 rounded-full bg-green-400"></div>
                            {{ exam.question_count }} Soal
                        </div>
                    </div>

                    <div class="mt-auto pt-6">
                        <template v-if="!exam.has_finished">
                            <div v-if="lockedExams[exam.id]" class="w-full flex items-center justify-center gap-2 py-3 bg-red-600 hover:bg-red-700 text-white rounded-2xl font-bold text-sm uppercase tracking-widest cursor-not-allowed shadow-lg shadow-red-600/25 transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                                TERKUNCI
                            </div>
                            <Link v-else-if="exam.can_start" 
                                :href="route('student.exams.take', exam.id)" 
                                @click="sessionStorage.setItem(`exam_session_${exam.id}_u${userId.value}`, 'true')"
                                class="w-full flex items-center justify-center gap-2 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-2xl font-bold text-sm uppercase tracking-widest transition-all shadow-lg shadow-green-500/25 hover:-translate-y-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                                {{ ongoingExams[exam.id] ? 'Lanjutkan Ujian' : 'Mulai Ujian' }}
                            </Link>
                            <div v-else class="w-full p-4 bg-yellow-50 border border-yellow-100 rounded-2xl">
                                <div class="flex items-center gap-2 text-yellow-700 mb-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-[10px] font-black uppercase tracking-widest">Belum Dimulai</span>
                                </div>
                                <p class="text-[11px] font-bold text-yellow-600/80">Ujian dijadwalkan pada:<br>{{ exam.start_time }}</p>
                            </div>
                        </template>
                        <div v-else class="w-full flex items-center justify-center gap-2 py-3 bg-gray-200 text-gray-500 rounded-2xl font-bold text-sm uppercase tracking-widest cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Selesai
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
.exam-card {
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(22, 163, 74, 0.15);
    box-shadow: 0 4px 24px rgba(22, 163, 74, 0.06);
}
</style>
