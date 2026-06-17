<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    exam: Object,
});

const isOngoing = ref(false);
const isLocked = ref(false);

onMounted(() => {
    const lockedKey = `exam_locked_${props.exam.id}`;
    const stateKey = `exam_state_${props.exam.id}`;

    // PRIORITIZE database status
    if (props.exam.is_locked) {
        isLocked.value = true;
        localStorage.setItem(lockedKey, 'true');
    } else {
        localStorage.removeItem(lockedKey);
        isLocked.value = false;
        
        if (localStorage.getItem(stateKey)) {
            isOngoing.value = true;
        }
    }
});
</script>

<template>
    <Head title="Persiapan Ujian" />
    <AuthenticatedLayout>
        <template #header>Persiapan <span class="text-gradient-gy">Ujian</span></template>

        <div class="max-w-3xl mx-auto mt-8 sm:mt-12">
            <div class="preparation-card p-8 sm:p-12 rounded-[2.5rem] relative overflow-hidden">
                <!-- Decorative Elements -->
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-green-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-yellow-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>

                <div class="relative z-10 flex flex-col items-center text-center">
                    <div class="w-20 h-20 bg-green-100 text-green-600 rounded-2xl flex items-center justify-center mb-6 shadow-inner">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>

                    <h2 class="text-xs font-black uppercase tracking-[0.3em] text-gray-400 mb-2">Mata Pelajaran</h2>
                    <h1 class="text-3xl sm:text-4xl font-black text-gray-800 mb-6">{{ exam.subject_name }}</h1>

                    <div class="bg-white/60 backdrop-blur-md rounded-2xl border border-green-100 p-6 w-full max-w-md mb-10 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-700 mb-4">{{ exam.title }}</h3>
                        
                        <div class="flex justify-between items-center py-3 border-b border-gray-100" v-if="exam.start_time">
                            <span class="text-sm font-bold text-gray-500 uppercase tracking-widest">Mulai</span>
                            <span class="text-sm font-black text-green-600">{{ exam.start_time }}</span>
                        </div>
                        <div class="flex justify-between items-center py-3 border-b border-gray-100" v-if="exam.end_time">
                            <span class="text-sm font-bold text-gray-500 uppercase tracking-widest">Selesai</span>
                            <span class="text-sm font-black text-red-600">{{ exam.end_time }}</span>
                        </div>
                        <div class="flex justify-between items-center py-3 border-b border-gray-100">
                            <span class="text-sm font-bold text-gray-500 uppercase tracking-widest">Durasi</span>
                            <span class="text-lg font-black text-gray-800">{{ exam.duration_minutes }} Menit</span>
                        </div>
                        <div class="flex justify-between items-center py-3">
                            <span class="text-sm font-bold text-gray-500 uppercase tracking-widest">Total Soal</span>
                            <span class="text-lg font-black text-gray-800">{{ exam.question_count }} Soal</span>
                        </div>
                    </div>

                    <div class="w-full max-w-md">
                        <div v-if="isLocked" class="w-full flex items-center justify-center gap-3 py-4 bg-red-700 hover:bg-red-800 text-white rounded-2xl font-black text-lg uppercase tracking-widest cursor-not-allowed shadow-xl shadow-red-700/30 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 2a5 5 0 00-5 5v2a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2H7V7a3 3 0 015.905-.75 1 1 0 001.937-.5A5.002 5.002 0 0010 2z" />
                            </svg>
                            TERKUNCI
                        </div>
                        <Link v-else-if="!exam.has_finished" 
                            :href="route('student.exams.take', exam.id)" 
                            @click="sessionStorage.setItem(`exam_session_${exam.id}_u${userId.value}`, 'true')"
                            class="w-full flex items-center justify-center gap-3 py-4 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-2xl font-black text-lg uppercase tracking-widest transition-all shadow-xl shadow-green-500/30 hover:-translate-y-1">
                            {{ isOngoing ? 'Lanjutkan Ujian' : 'Kerjakan Soal Sekarang' }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                        <div v-else class="w-full flex items-center justify-center gap-3 py-4 bg-gray-200 text-gray-500 rounded-2xl font-black text-lg uppercase tracking-widest cursor-not-allowed">
                            SELESAI
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <p class="text-xs text-gray-400 mt-4 font-medium">Pastikan koneksi internet Anda stabil sebelum memulai ujian.</p>
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
.preparation-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(22, 163, 74, 0.15);
    box-shadow: 0 10px 40px rgba(22, 163, 74, 0.08);
}
</style>
