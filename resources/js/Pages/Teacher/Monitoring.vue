<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    exam: Object,
    results: Array,
});

const studentResults = ref(props.results);

// Polling every 5 seconds to get live status
let pollInterval = null;

const fetchLatestStatus = () => {
    router.get(route('teacher.exams.monitoring', props.exam.id), {}, {
        preserveScroll: true,
        preserveState: true,
        only: ['results'],
        onSuccess: (page) => {
            studentResults.value = page.props.results;
        }
    });
};

onMounted(() => {
    pollInterval = setInterval(fetchLatestStatus, 5000);
});

onBeforeUnmount(() => {
    clearInterval(pollInterval);
});

const page = usePage();
const flashSuccess = ref(page.props.flash?.success || '');
const flashError = ref(page.props.flash?.error || '');

watch(() => page.props.flash, (newVal) => {
    flashSuccess.value = newVal?.success || '';
    flashError.value = newVal?.error || '';
    
    if (flashSuccess.value) setTimeout(() => { flashSuccess.value = ''; }, 5000);
    if (flashError.value) setTimeout(() => { flashError.value = ''; }, 5000);
}, { deep: true });

const getStatusBadge = (student) => {
    if (student.end_time) return { label: 'Selesai', class: 'bg-blue-100 text-blue-700' };
    if (student.is_online) return { label: 'Online', class: 'bg-green-100 text-green-700' };
    return { label: 'Offline', class: 'bg-gray-100 text-gray-500' };
};

// Reusable confirm modal state
const confirmModal = ref({
    show: false,
    title: '',
    message: '',
    confirmText: 'Ya',
    cancelText: 'Batal',
    onConfirm: null,
    isDanger: false,
});

const openConfirm = (title, message, confirmText, cancelText, onConfirm, isDanger = false) => {
    confirmModal.value = {
        show: true,
        title,
        message,
        confirmText,
        cancelText,
        onConfirm,
        isDanger,
    };
};

const handleConfirm = () => {
    if (confirmModal.value.onConfirm) {
        confirmModal.value.onConfirm();
    }
    confirmModal.value.show = false;
};

const reactivateExam = (resultId) => {
    openConfirm(
        'Aktifkan Kembali Ujian',
        'Apakah Anda yakin ingin membuka kembali ujian siswa ini? Siswa akan dapat melanjutkan pengerjaan.',
        'Aktifkan',
        'Batal',
        () => {
            router.patch(route('teacher.results.reactivate', resultId), {}, {
                preserveScroll: true,
                preserveState: true,
            });
        },
        false
    );
};

const resetStatus = (resultId) => {
    openConfirm(
        'Jadikan Status Aman',
        'Apakah Anda yakin ingin mengubah status kecurangan siswa ini menjadi Aman?',
        'Jadikan Aman',
        'Batal',
        () => {
            router.patch(route('teacher.results.reset_status', resultId), {}, {
                preserveScroll: true,
                preserveState: true,
            });
        },
        false
    );
};

const markAsCheating = (resultId) => {
    openConfirm(
        'Tandai Sebagai Curang',
        'Apakah Anda yakin ingin menandai siswa ini sebagai Curang? Ujian siswa akan langsung dikunci.',
        'Tandai Curang',
        'Batal',
        () => {
            router.patch(route('teacher.results.mark_cheat', resultId), {}, {
                preserveScroll: true,
                preserveState: true,
            });
        },
        true
    );
};
</script>

<template>
    <Head :title="'Monitoring: ' + exam.title" />
    <AuthenticatedLayout>
        <template #header>
            Monitoring: <span class="text-gradient-gy capitalize">{{ exam.title }}</span>
        </template>

        <div class="space-y-6">
            <!-- Flash Message -->
            <div v-if="flashSuccess" class="p-4 bg-green-100 border border-green-200 text-green-700 rounded-2xl animate-fade-in-down flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                <span class="font-bold">{{ flashSuccess }}</span>
            </div>

            <!-- Error Message -->
            <div v-if="flashError" class="p-4 bg-red-100 border border-red-200 text-red-700 rounded-2xl animate-fade-in-down flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                <span class="font-bold">{{ flashError }}</span>
            </div>

            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 tracking-tighter uppercase">Monitoring Live</h2>
                    <p class="text-sm text-gray-500 mt-1">Memantau aktivitas siswa secara real-time selama ujian berlangsung.</p>
                </div>
                <Link :href="route('teacher.exams')" class="px-4 py-2 bg-white border border-gray-200 text-gray-600 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-gray-50 transition-all">
                    Kembali
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Summary Cards -->
                <div class="bg-white p-6 rounded-[2rem] border border-green-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Siswa</div>
                        <div class="text-2xl font-black text-gray-800">{{ studentResults.length }}</div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-blue-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Aktif Mengerjakan</div>
                        <div class="text-2xl font-black text-gray-800">{{ studentResults.filter(r => r.is_online && !r.end_time).length }}</div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-red-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center text-red-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Terdeteksi Curang</div>
                        <div class="text-2xl font-black text-red-600">{{ studentResults.filter(r => r.is_cheating).length }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-xl overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Siswa</th>
                            <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</th>
                            <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Deteksi Kecurangan</th>
                            <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Aktifkan Ujian</th>
                            <th class="p-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Terakhir Aktif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="result in studentResults" :key="result.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 font-bold uppercase">
                                        {{ result.user.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-800">{{ result.user.name }}</div>
                                        <div class="text-[10px] text-gray-400 font-black uppercase">{{ result.user.nis_nik || 'No NIP' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4">
                                <span :class="getStatusBadge(result).class" class="px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">
                                    {{ getStatusBadge(result).label }}
                                </span>
                            </td>
                            <td class="p-4 text-center">
                                <div v-if="result.is_cheating" class="flex flex-col items-center gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-100 text-red-700 rounded-xl animate-pulse w-full justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        <span class="text-[10px] font-black uppercase tracking-widest">Terdeteksi Curang</span>
                                    </div>
                                    <button @click="resetStatus(result.id)" 
                                            class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-blue-100 text-blue-700 rounded-xl hover:bg-blue-200 transition-all w-full shadow-sm shadow-blue-500/10">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="text-[9px] font-black uppercase tracking-widest">Jadikan Aman</span>
                                    </button>
                                </div>
                                <div v-else class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 text-green-600 rounded-xl w-full justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span class="text-[10px] font-black uppercase tracking-widest">Aman</span>
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <button v-if="result.end_time || !result.is_online" 
                                        @click="reactivateExam(result.id)"
                                        class="px-3 py-1.5 bg-yellow-50 text-yellow-600 hover:bg-yellow-100 border border-yellow-200 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all whitespace-nowrap shadow-sm shadow-yellow-500/10"
                                        title="Buka Kembali Akses Ujian">
                                    Aktifkan Kembali
                                </button>
                                <span v-else class="text-[9px] font-bold text-gray-300 uppercase tracking-widest italic">Sedang Mengerjakan</span>
                            </td>
                            <td class="p-4 text-right">
                                <span class="text-xs font-medium text-gray-500">{{ result.last_activity ? new Date(result.last_activity).toLocaleTimeString() : '-' }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reusable modern confirm modal -->
        <Modal :show="confirmModal.show" @close="confirmModal.show = false" maxWidth="sm">
            <div class="p-6 text-left">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :class="confirmModal.isDanger ? 'bg-red-50' : 'bg-yellow-50'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :class="confirmModal.isDanger ? 'text-red-500' : 'text-yellow-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">{{ confirmModal.title }}</h3>
                </div>
                
                <p class="text-xs font-medium text-gray-500 mb-6 leading-relaxed">
                    {{ confirmModal.message }}
                </p>

                <div class="flex justify-end gap-2.5">
                    <button 
                        @click="confirmModal.show = false"
                        class="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-xs font-black uppercase tracking-wider transition-colors border border-gray-200"
                    >
                        {{ confirmModal.cancelText }}
                    </button>
                    <button 
                        @click="handleConfirm"
                        class="px-4 py-2 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors shadow-md"
                        :class="confirmModal.isDanger 
                            ? 'bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 shadow-red-500/10' 
                            : 'bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 shadow-yellow-500/10'"
                    >
                        {{ confirmModal.confirmText }}
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
