<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    exam: Object,
    results: Array,
});

const calculateDuration = (startTime, endTime) => {
    if (!startTime || !endTime) return '-';
    
    const start = new Date(startTime);
    const end = new Date(endTime);
    const diffMs = end - start;
    
    if (diffMs < 0) return '-';
    
    const diffMins = Math.floor(diffMs / 60000);
    const diffSecs = Math.floor((diffMs % 60000) / 1000);
    
    if (diffMins === 0) {
        return `${diffSecs} detik`;
    }
    return `${diffMins} menit ${diffSecs} detik`;
};

const formatTime = (timeString) => {
    if (!timeString) return '-';
    return new Date(timeString).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};
</script>

<template>
    <Head :title="`Hasil Ujian: ${exam.title}`" />
    <AuthenticatedLayout>
        <template #header>
            Hasil Ujian: <span class="text-gradient-gy capitalize">{{ exam.title }}</span>
        </template>

        <div class="space-y-6 sm:space-y-10">
            <div class="flex items-center gap-4">
                <Link :href="route('teacher.exams')" class="p-2 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div class="flex-1">
                    <h2 class="text-2xl font-black text-gray-800 tracking-tighter">{{ exam.title }}</h2>
                    <p class="text-sm text-gray-500 mt-1">Mata Pelajaran: {{ exam.subject.name }} (Kelas {{ exam.subject.class }})</p>
                </div>
                
                <a v-if="results.length > 0" :href="route('teacher.exams.results.export', exam.id)" class="px-5 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-xl font-bold text-sm uppercase tracking-widest transition-all shadow-md shadow-green-500/20 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                    Unduh Rekap (Excel)
                </a>
            </div>

            <!-- Empty State -->
            <div v-if="results.length === 0" class="p-12 text-center bg-white/50 backdrop-blur-sm rounded-[2rem] border border-green-200 border-dashed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-green-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                <h3 class="text-lg font-black text-gray-800">Belum Ada Hasil</h3>
                <p class="text-gray-500 mt-2">Belum ada siswa yang menyelesaikan atau mengerjakan ujian ini.</p>
            </div>

            <div v-else class="bg-white/80 backdrop-blur-md border border-green-200/60 shadow-lg shadow-green-500/10 rounded-[2rem] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-green-50/50 border-b border-green-100">
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">No</th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">Nama Siswa</th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">NIS</th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">Waktu Mulai</th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">Waktu Selesai</th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">Pengerjaan</th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">Nilai Akhir</th>
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/50">
                            <tr v-for="(result, index) in results" :key="result.id" class="hover:bg-green-50/30 transition-colors">
                                <td class="p-4 text-sm font-medium text-gray-500">{{ index + 1 }}</td>
                                <td class="p-4 text-sm font-bold text-gray-800">{{ result.user.name }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">{{ result.user.nis_nik || '-' }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">
                                    {{ formatTime(result.start_time) }}
                                </td>
                                <td class="p-4 text-sm font-medium text-gray-600">
                                    {{ formatTime(result.end_time || result.created_at) }}
                                </td>
                                <td class="p-4 text-sm font-bold text-yellow-600">
                                    {{ calculateDuration(result.start_time, result.end_time || result.created_at) }}
                                </td>
                                <td class="p-4 text-lg font-black text-green-600">{{ result.score }}</td>
                                <td class="p-4 flex items-center justify-center">
                                    <Link :href="route('teacher.exams.review', { exam: exam.id, result: result.id })" class="px-4 py-2 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 border border-yellow-200 rounded-xl text-xs font-bold uppercase tracking-widest transition-all">
                                        Review & Edit
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
</style>
