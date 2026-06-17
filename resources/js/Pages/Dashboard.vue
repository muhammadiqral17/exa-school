<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    role: String,
    stats: Object,
    latest_activities: Array,
    announcements: {
        type: Array,
        default: () => []
    }
});

const refreshLog = () => {
    router.reload({ only: ['latest_activities'] });
};

const itemsPerPage = 5;

// Pagination: Log Aktivitas Terbaru
const currentPageActivity = ref(1);

const paginatedActivities = computed(() => {
    const list = props.latest_activities || [];
    const start = (currentPageActivity.value - 1) * itemsPerPage;
    return list.slice(start, start + itemsPerPage);
});

const totalActivityPages = computed(() => {
    const list = props.latest_activities || [];
    return Math.ceil(list.length / itemsPerPage) || 1;
});

// Reset page on reload/refresh
watch(() => props.latest_activities, () => {
    currentPageActivity.value = 1;
});

// Pagination: Pengumuman
const currentPageAnnouncement = ref(1);

const paginatedAnnouncements = computed(() => {
    const list = props.announcements || [];
    const start = (currentPageAnnouncement.value - 1) * itemsPerPage;
    return list.slice(start, start + itemsPerPage);
});

const totalAnnouncementPages = computed(() => {
    const list = props.announcements || [];
    return Math.ceil(list.length / itemsPerPage) || 1;
});

// Pagination: Hasil Terakhir (Student only)
const currentPageResult = ref(1);

const paginatedResults = computed(() => {
    const list = props.stats?.latest_results || [];
    const start = (currentPageResult.value - 1) * itemsPerPage;
    return list.slice(start, start + itemsPerPage);
});

const totalResultPages = computed(() => {
    const list = props.stats?.latest_results || [];
    return Math.ceil(list.length / itemsPerPage) || 1;
});

// Announcement styling helpers
const getAnnBorderClass = (type) => {
    if (type === 'PENTING') return 'border-red-200 hover:border-red-400 bg-red-50/10';
    if (type === 'INFO') return 'border-blue-200 hover:border-blue-400 bg-blue-50/10';
    return 'border-yellow-200 hover:border-yellow-400 bg-yellow-50/5';
};

const getAnnTextClass = (type) => {
    if (type === 'PENTING') return 'text-red-600';
    if (type === 'INFO') return 'text-blue-600';
    return 'text-yellow-600';
};

// Modal and Form States for Announcements
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedAnnouncement = ref(null);

const form = useForm({
    type: 'PENGUMUMAN',
    text: '',
});

const openAddModal = () => {
    form.reset();
    form.type = 'PENGUMUMAN';
    form.text = '';
    isAddModalOpen.value = true;
};

const closeAddModal = () => {
    isAddModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const openEditModal = (ann) => {
    selectedAnnouncement.value = ann;
    form.type = ann.type;
    form.text = ann.text;
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    selectedAnnouncement.value = null;
    form.reset();
    form.clearErrors();
};

const submitAnnouncement = () => {
    form.post(route('admin.announcements.store'), {
        onSuccess: () => {
            closeAddModal();
        },
        preserveScroll: true,
    });
};

const updateAnnouncement = () => {
    if (!selectedAnnouncement.value) return;
    form.put(route('admin.announcements.update', selectedAnnouncement.value.id), {
        onSuccess: () => {
            closeEditModal();
        },
        preserveScroll: true,
    });
};

const showConfirmDeleteModal = ref(false);
const announcementToDelete = ref(null);

const confirmDelete = (ann) => {
    announcementToDelete.value = ann;
    showConfirmDeleteModal.value = true;
};

const deleteAnnouncement = () => {
    if (!announcementToDelete.value) return;
    router.delete(route('admin.announcements.destroy', announcementToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmDeleteModal.value = false;
            announcementToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            Panel Kendali: <span class="text-gradient-gy capitalize">{{ role }}</span>
        </template>

        <div class="space-y-6 sm:space-y-10">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-8">
                <!-- Admin Stats -->
                <template v-if="role.toLowerCase() === 'admin'">
                    <div class="stat-card p-6 sm:p-8 rounded-[2rem] group hover:border-green-400 transition-all duration-500">
                        <div class="text-green-600 text-xs font-black uppercase tracking-[0.2em] mb-2">Total User</div>
                        <div class="text-5xl font-black text-gray-800 leading-none">{{ stats.users_count }}</div>
                        <div class="mt-6 flex items-center text-[10px] font-bold text-gray-400 uppercase tracking-widest gap-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div> Data Real-time
                        </div>
                    </div>
                    <div class="stat-card p-6 sm:p-8 rounded-[2rem] group hover:border-yellow-400 transition-all duration-500">
                        <div class="text-yellow-600 text-xs font-black uppercase tracking-[0.2em] mb-2">Guru Aktif</div>
                        <div class="text-5xl font-black text-gray-800 leading-none">{{ stats.guru_count }}</div>
                        <div class="mt-6 flex items-center text-[10px] font-bold text-gray-400 uppercase tracking-widest gap-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-yellow-500"></div> Pendidik Terdaftar
                        </div>
                    </div>
                    <div class="stat-card p-6 sm:p-8 rounded-[2rem] group hover:border-blue-300 transition-all duration-500">
                        <div class="text-blue-500 text-xs font-black uppercase tracking-[0.2em] mb-2">Siswa</div>
                        <div class="text-5xl font-black text-gray-800 leading-none">{{ stats.siswa_count }}</div>
                        <div class="mt-6 flex items-center text-[10px] font-bold text-gray-400 uppercase tracking-widest gap-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-blue-400"></div> User Terverifikasi
                        </div>
                    </div>
                    <div class="stat-card p-6 sm:p-8 rounded-[2rem] group hover:border-green-400 transition-all duration-500">
                        <div class="text-green-600 text-xs font-black uppercase tracking-[0.2em] mb-2">Bank Soal</div>
                        <div class="text-5xl font-black text-gray-800 leading-none">{{ stats.subjects_count }}</div>
                        <div class="mt-6 flex items-center text-[10px] font-bold text-gray-400 uppercase tracking-widest gap-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div> Kurikulum Aktif
                        </div>
                    </div>
                    <div class="stat-card p-6 sm:p-8 rounded-[2rem] border-l-4 border-indigo-500 bg-indigo-50/30">
                        <div class="text-indigo-600 text-xs font-black uppercase tracking-[0.2em] mb-2">Tahun Ajaran</div>
                        <div class="text-3xl font-black text-gray-800 leading-none">{{ $page.props.auth.user.tahun_ajaran || '-' }}</div>
                        <div class="mt-6 flex items-center text-[10px] font-bold text-gray-400 uppercase tracking-widest gap-2">
                            <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div> Periode Aktif
                        </div>
                    </div>
                </template>

                <!-- Guru Stats -->
                <template v-if="role.toLowerCase() === 'guru'">
                    <div class="stat-card p-6 sm:p-8 rounded-[2rem] border-l-4 border-green-500">
                        <div class="text-green-600 text-xs font-black uppercase tracking-[0.2em] mb-2">Ujian Anda</div>
                        <div class="text-5xl font-black text-gray-800 leading-none">{{ stats.exams_count }}</div>
                    </div>
                    <div class="stat-card p-6 sm:p-8 rounded-[2rem] border-l-4 border-yellow-500">
                        <div class="text-yellow-600 text-xs font-black uppercase tracking-[0.2em] mb-2">Total Soal</div>
                        <div class="text-5xl font-black text-gray-800 leading-none">{{ stats.questions_count }}</div>
                    </div>
                    <div class="stat-card p-6 sm:p-8 rounded-[2rem] border-l-4 border-blue-400">
                        <div class="text-blue-500 text-xs font-black uppercase tracking-[0.2em] mb-2">Selesai Dinilai</div>
                        <div class="text-5xl font-black text-gray-800 leading-none">{{ stats.results_count }}</div>
                    </div>
                    <div class="stat-card p-6 sm:p-8 rounded-[2rem] border-l-4 border-indigo-500 bg-indigo-50/30">
                        <div class="text-indigo-600 text-xs font-black uppercase tracking-[0.2em] mb-2">Tahun Ajaran</div>
                        <div class="text-3xl font-black text-gray-800 leading-none">{{ $page.props.auth.user.tahun_ajaran || '-' }}</div>
                    </div>
                </template>

                <!-- Siswa Stats -->
                <template v-if="role.toLowerCase() === 'siswa'">
                    <div class="stat-card p-6 sm:p-8 rounded-[2rem] bg-gradient-to-br from-green-100 to-green-50 border-green-300">
                        <div class="text-green-600 text-xs font-black uppercase tracking-[0.2em] mb-2">Tersedia</div>
                        <div class="text-5xl font-black text-gray-800 leading-none">{{ stats.available_exams }}</div>
                        <Link :href="route('student.exams.index')" class="w-full inline-block mt-6 py-4 bg-green-500 hover:bg-green-600 text-white font-black uppercase tracking-widest rounded-2xl transition-all shadow-lg shadow-green-500/20 text-center">Mulai Ujian</Link>
                    </div>
                    <div class="stat-card p-6 sm:p-8 rounded-[2rem]">
                        <div class="text-yellow-600 text-xs font-black uppercase tracking-[0.2em] mb-2">Sudah Dikerjakan</div>
                        <div class="text-5xl font-black text-gray-800 leading-none">{{ stats.completed_exams }}</div>
                        <Link :href="route('student.exams.results')" class="w-full inline-block mt-6 py-4 border border-green-200 hover:bg-green-50 text-gray-600 text-xs font-bold uppercase tracking-widest rounded-2xl transition-all text-center">Lihat Riwayat Nilai</Link>
                    </div>
                    <div class="stat-card p-6 sm:p-8 rounded-[2rem] border-l-4 border-indigo-500 bg-indigo-50/30">
                        <div class="text-indigo-600 text-xs font-black uppercase tracking-[0.2em] mb-2">Tahun Ajaran</div>
                        <div class="text-3xl font-black text-gray-800 leading-none">{{ $page.props.auth.user.tahun_ajaran || '-' }}</div>
                        <div class="mt-12 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Tahun Akademik Anda</div>
                    </div>
                </template>
            </div>

            <!-- Activity & Announcement -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-8">
                <div class="lg:col-span-8">
                    <!-- Petunjuk Umum Penggunaan Web CBT (Siswa Only) -->
                    <div v-if="role.toLowerCase() === 'siswa'" class="content-card p-6 sm:p-10 rounded-[2.5rem] mb-6 border border-green-200/60 bg-white/80 backdrop-blur-md shadow-lg shadow-green-500/5 relative overflow-hidden">
                        <div class="absolute -right-16 -top-16 w-36 h-36 bg-green-50 rounded-full blur-2xl pointer-events-none"></div>
                        <div class="absolute -left-16 -bottom-16 w-36 h-36 bg-yellow-50/50 rounded-full blur-2xl pointer-events-none"></div>
                        
                        <div class="flex items-center gap-3.5 mb-6 border-b border-green-100/50 pb-5">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white shadow-md shadow-green-500/20 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl sm:text-2xl font-black text-gray-800 tracking-tight leading-tight">PETUNJUK UMUM PENGGUNAAN WEB EXASCHOOL CBT</h2>
                                <p class="text-[10px] font-black text-yellow-600 uppercase tracking-widest mt-1.5">Maju, Bermutu, Berkarakter</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                                <div class="flex gap-3.5 items-start">
                                    <div class="w-6 h-6 rounded-full bg-green-50 border border-green-200 text-green-700 flex items-center justify-center text-xs font-black shrink-0 mt-0.5 shadow-sm">1</div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-semibold">Pastikan perangkat yang digunakan (Laptop, Komputer, atau Smartphone) terhubung dengan koneksi internet yang stabil sebelum memulai ujian.</p>
                                </div>
                                <div class="flex gap-3.5 items-start">
                                    <div class="w-6 h-6 rounded-full bg-green-50 border border-green-200 text-green-700 flex items-center justify-center text-xs font-black shrink-0 mt-0.5 shadow-sm">2</div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-semibold">Login menggunakan akun yang telah diberikan oleh Administrator atau Guru. Pastikan identitas yang tampil pada halaman dashboard sesuai dengan data diri Anda.</p>
                                </div>
                                <div class="flex gap-3.5 items-start">
                                    <div class="w-6 h-6 rounded-full bg-green-50 border border-green-200 text-green-700 flex items-center justify-center text-xs font-black shrink-0 mt-0.5 shadow-sm">3</div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-semibold">Bacalah petunjuk dan informasi ujian dengan seksama sebelum menekan tombol Mulai Ujian. Kerjakan soal sesuai dengan waktu yang telah ditentukan.</p>
                                </div>
                                <div class="flex gap-3.5 items-start">
                                    <div class="w-6 h-6 rounded-full bg-green-50 border border-green-200 text-green-700 flex items-center justify-center text-xs font-black shrink-0 mt-0.5 shadow-sm">4</div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-semibold">Waktu ujian akan berjalan secara otomatis setelah ujian dimulai.</p>
                                </div>
                                <div class="flex gap-3.5 items-start">
                                    <div class="w-6 h-6 rounded-full bg-green-50 border border-green-200 text-green-700 flex items-center justify-center text-xs font-black shrink-0 mt-0.5 shadow-sm">5</div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-semibold">Pilih jawaban yang dianggap benar dengan mengklik atau mengetuk opsi jawaban yang tersedia.</p>
                                </div>
                                <div class="flex gap-3.5 items-start">
                                    <div class="w-6 h-6 rounded-full bg-green-50 border border-green-200 text-green-700 flex items-center justify-center text-xs font-black shrink-0 mt-0.5 shadow-sm">6</div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-semibold">Peserta dapat mengubah jawaban kapan saja selama waktu ujian masih berlangsung.</p>
                                </div>
                                <div class="flex gap-3.5 items-start">
                                    <div class="w-6 h-6 rounded-full bg-green-50 border border-green-200 text-green-700 flex items-center justify-center text-xs font-black shrink-0 mt-0.5 shadow-sm">7</div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-semibold">Gunakan tombol Sebelumnya, Berikutnya, atau daftar nomor soal untuk berpindah antar soal.</p>
                                </div>
                                <div class="flex gap-3.5 items-start">
                                    <div class="w-6 h-6 rounded-full bg-green-50 border border-green-200 text-green-700 flex items-center justify-center text-xs font-black shrink-0 mt-0.5 shadow-sm">8</div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-semibold">Perhatikan indikator status soal untuk mengetahui soal yang sudah dikerjakan maupun yang belum dikerjakan.</p>
                                </div>
                                <div class="flex gap-3.5 items-start">
                                    <div class="w-6 h-6 rounded-full bg-green-50 border border-green-200 text-green-700 flex items-center justify-center text-xs font-black shrink-0 mt-0.5 shadow-sm">9</div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-semibold">Gunakan tombol ragu-ragu jika anda merasa kurang yakin dengan soal tersebut dan dapat lanjut ketika ingin mengumpulkan harap tidak mencentang tombol ragu-ragu tersebut.</p>
                                </div>
                                <div class="flex gap-3.5 items-start">
                                    <div class="w-6 h-6 rounded-full bg-green-50 border border-green-200 text-green-700 flex items-center justify-center text-xs font-black shrink-0 mt-0.5 shadow-sm">10</div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-semibold">Dilarang membuka tab, aplikasi, atau sumber lain yang tidak berkaitan dengan ujian apabila ujian dilaksanakan dengan pengawasan.</p>
                                </div>
                                <div class="flex gap-3.5 items-start">
                                    <div class="w-6 h-6 rounded-full bg-green-50 border border-green-200 text-green-700 flex items-center justify-center text-xs font-black shrink-0 mt-0.5 shadow-sm">11</div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-semibold">Apabila terjadi gangguan teknis, segera laporkan kepada Guru atau Pengawas Ujian. Setelah seluruh soal selesai dikerjakan, periksa kembali jawaban Anda sebelum menekan tombol Selesai Ujian. Ketika waktu ujian berakhir, sistem akan mengakhiri ujian secara otomatis dan seluruh jawaban yang tersimpan akan dikirim ke sistem.</p>
                                </div>
                                <div class="flex gap-3.5 items-start">
                                    <div class="w-6 h-6 rounded-full bg-green-50 border border-green-200 text-green-700 flex items-center justify-center text-xs font-black shrink-0 mt-0.5 shadow-sm">12</div>
                                    <p class="text-gray-600 text-xs sm:text-sm font-semibold">Nilai atau hasil ujian dapat dilihat sesuai pengaturan yang ditetapkan oleh Guru atau Administrator.</p>
                                </div>
                            </div>

                            <div class="mt-6 pt-5 border-t border-green-100/50 flex flex-col sm:flex-row justify-between items-center gap-4 bg-green-50/40 p-5 rounded-2xl border border-green-200/30">
                                <div class="text-center sm:text-left">
                                    <p class="text-xs sm:text-sm font-extrabold text-green-800 uppercase tracking-wide">Jagalah kejujuran dan integritas selama pelaksanaan ujian berlangsung.</p>
                                    <p class="text-[11px] font-bold text-gray-500 mt-1 leading-relaxed">Selamat mengerjakan ujian. Kerjakan dengan teliti, jujur, dan manfaatkan waktu yang tersedia sebaik mungkin.</p>
                                </div>
                                <div class="text-center sm:text-right shrink-0">
                                    <span class="inline-block px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white text-[10px] font-black uppercase tracking-wider rounded-xl shadow-md shadow-green-500/10">EXASCHOOL MTS SWASTA AL-HUDA</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card p-6 sm:p-10 rounded-[2.5rem] relative overflow-hidden">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-10">
                            <div>
                                <h2 class="text-xl sm:text-2xl font-black text-gray-800">Log Aktivitas Terbaru</h2>
                                <p class="text-gray-400 text-sm">Monitoring sistem secara real-time</p>
                            </div>
                            <button @click="refreshLog" class="flex items-center gap-2 text-xs font-bold text-green-600 uppercase tracking-widest hover:text-green-500 transition-colors">
                                Segarkan
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            </button>
                        </div>
                        <div class="space-y-4 sm:space-y-6">
                            <div v-for="activity in paginatedActivities" :key="activity.id" class="flex items-center gap-4 sm:gap-6 p-4 sm:p-6 rounded-[2rem] bg-green-50/60 border border-green-200/60 hover:border-green-400/50 hover:scale-[1.01] transition-all cursor-pointer group">
                                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl bg-white flex items-center justify-center text-green-600 border border-green-200 group-hover:border-green-400 transition-colors shrink-0 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 sm:h-8 sm:w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-gray-800 font-bold text-lg">Publikasi Ujian: {{ activity.title }}</h3>
                                    <p class="text-gray-400 text-sm font-medium mt-1">
                                        Selesai diterbitkan oleh <span class="text-yellow-600">{{ activity.teacher_name }}</span> untuk mapel {{ activity.subject_name }} • {{ activity.date }}, {{ activity.time }}
                                    </p>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <span v-if="activity.material" class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded text-[9px] font-black uppercase tracking-widest border border-blue-100">Materi: {{ activity.material }}</span>
                                        <span v-if="activity.academic_year" class="px-2 py-0.5 bg-green-50 text-green-600 rounded text-[9px] font-black uppercase tracking-widest border border-green-100">{{ activity.academic_year }}</span>
                                        <span v-if="activity.semester" class="px-2 py-0.5 bg-yellow-50 text-yellow-600 rounded text-[9px] font-black uppercase tracking-widest border border-yellow-100">{{ activity.semester }}</span>
                                    </div>
                                </div>
                                <div class="px-4 sm:px-6 py-1 sm:py-2 rounded-full bg-green-100 text-green-700 text-[10px] font-black uppercase tracking-widest border border-green-300">Sukses</div>
                            </div>
                            
                            <div v-if="!latest_activities || latest_activities.length === 0" class="text-center py-6 text-gray-400 font-bold text-sm">
                                Belum ada aktivitas terbaru.
                            </div>

                            <!-- Activity Pagination Controls -->
                            <div v-if="latest_activities && latest_activities.length > 5" class="flex items-center justify-between pt-4 border-t border-gray-100">
                                <button 
                                    type="button"
                                    @click="currentPageActivity--" 
                                    :disabled="currentPageActivity === 1" 
                                    class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-200 hover:bg-green-50 text-gray-600 hover:text-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                >
                                    Sebelumnya
                                </button>
                                <span class="text-xs font-black text-gray-400 uppercase tracking-widest">Halaman {{ currentPageActivity }} dari {{ totalActivityPages }}</span>
                                <button 
                                    type="button"
                                    @click="currentPageActivity++" 
                                    :disabled="currentPageActivity === totalActivityPages" 
                                    class="px-4 py-2 text-xs font-bold rounded-xl border border-gray-200 hover:bg-green-50 text-gray-600 hover:text-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                                >
                                    Berikutnya
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="content-card p-4 sm:p-5 rounded-[2rem] bg-gradient-to-br from-green-50 to-white">
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-base font-black text-gray-800 uppercase tracking-widest">Pengumuman</h2>
                                <!-- Pencil Add button only for Admin and Guru -->
                                <button v-if="role.toLowerCase() === 'admin' || role.toLowerCase() === 'guru'" 
                                        @click="openAddModal" 
                                        class="p-2 rounded-xl bg-green-50 text-green-700 hover:bg-green-100 transition-all border border-green-200 shadow-sm"
                                        title="Tambah Pengumuman">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="space-y-3">
                                <div v-for="ann in paginatedAnnouncements" :key="ann.id" 
                                     :class="['p-3.5 rounded-xl border shadow-sm transition-all relative group', getAnnBorderClass(ann.type)]">
                                    <div class="flex justify-between items-center mb-1">
                                        <span :class="['text-[9px] font-black uppercase tracking-widest', getAnnTextClass(ann.type)]">{{ ann.type }}</span>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[8px] font-bold text-gray-400">{{ ann.date }}</span>
                                            <!-- Edit/Delete only for Admin and Guru -->
                                            <div v-if="role.toLowerCase() === 'admin' || role.toLowerCase() === 'guru'" class="flex items-center gap-1 opacity-60 group-hover:opacity-100 transition-opacity">
                                                <button @click="openEditModal(ann)" class="p-1 rounded bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors" title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                                <button @click="confirmDelete(ann)" class="p-1 rounded bg-red-50 text-red-600 hover:bg-red-100 transition-colors" title="Hapus">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-xs font-medium text-gray-600 leading-relaxed">{{ ann.text }}</p>
                                    <div v-if="ann.creator_name" class="mt-2 text-[8px] text-gray-400 font-bold text-right uppercase tracking-wider">Oleh: {{ ann.creator_name }}</div>
                                </div>
                                
                                <div v-if="!announcements || announcements.length === 0" class="text-center py-6 text-gray-400 font-bold text-xs uppercase tracking-widest">
                                    Belum ada pengumuman.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Announcement Pagination Controls -->
                        <div v-if="(announcements || []).length > 5" class="flex items-center justify-between pt-3 border-t border-gray-100 mt-4">
                            <button 
                                type="button"
                                @click="currentPageAnnouncement--" 
                                :disabled="currentPageAnnouncement === 1" 
                                class="px-2.5 py-1.5 text-[9px] font-bold rounded-xl border border-gray-200 hover:bg-green-50 text-gray-600 hover:text-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                            >
                                Sebelumnya
                            </button>
                            <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Hlm {{ currentPageAnnouncement }}/{{ totalAnnouncementPages }}</span>
                            <button 
                                type="button"
                                @click="currentPageAnnouncement++" 
                                :disabled="currentPageAnnouncement === totalAnnouncementPages" 
                                class="px-2.5 py-1.5 text-[9px] font-bold rounded-xl border border-gray-200 hover:bg-green-50 text-gray-600 hover:text-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                            >
                                Berikutnya
                            </button>
                        </div>
                    </div>

                    <!-- Student Specific: Latest Results -->
                    <div v-if="role.toLowerCase() === 'siswa' && stats.latest_results?.length > 0" class="mt-8 content-card p-6 sm:p-8 rounded-[2.5rem]">
                        <div>
                            <h2 class="text-xl font-black text-gray-800 mb-6 uppercase tracking-widest">Hasil Terakhir</h2>
                            <div class="space-y-4">
                                <div v-for="res in paginatedResults" :key="res.id" class="p-4 rounded-2xl bg-white border border-gray-100 shadow-sm hover:border-green-300 transition-all">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ res.subject_name }}</div>
                                        <div class="text-xl font-black text-green-600">{{ res.score }}</div>
                                    </div>
                                    <h3 class="text-xs font-bold text-gray-700 leading-tight">{{ res.exam_title }}</h3>
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        <span v-if="res.material" class="px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded-[4px] text-[8px] font-black uppercase tracking-widest border border-blue-100/50">Materi: {{ res.material }}</span>
                                        <span v-if="res.academic_year" class="px-1.5 py-0.5 bg-green-50 text-green-600 rounded-[4px] text-[8px] font-black uppercase tracking-widest border border-green-100/50">{{ res.academic_year }}</span>
                                    </div>
                                    <div class="text-[8px] text-gray-400 mt-2 font-bold">{{ res.date }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Result Pagination Controls -->
                        <div v-if="stats.latest_results.length > 5" class="flex items-center justify-between pt-4 border-t border-gray-100 mt-6">
                            <button 
                                type="button"
                                @click="currentPageResult--" 
                                :disabled="currentPageResult === 1" 
                                class="px-3 py-1.5 text-[10px] font-bold rounded-xl border border-gray-200 hover:bg-green-50 text-gray-600 hover:text-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                            >
                                Sebelumnya
                            </button>
                            <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Hlm {{ currentPageResult }}/{{ totalResultPages }}</span>
                            <button 
                                type="button"
                                @click="currentPageResult++" 
                                :disabled="currentPageResult === totalResultPages" 
                                class="px-3 py-1.5 text-[10px] font-bold rounded-xl border border-gray-200 hover:bg-green-50 text-gray-600 hover:text-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                            >
                                Berikutnya
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Announcement Modal -->
        <Modal :show="isAddModalOpen" @close="closeAddModal">
            <div class="p-6 sm:p-8 bg-white/95 rounded-[2rem] border border-green-200 shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Tambah Pengumuman</h2>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">Publikasikan informasi baru</p>
                    </div>
                    <button @click="closeAddModal" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitAnnouncement" class="space-y-6">
                    <div>
                        <label class="block font-black uppercase tracking-widest text-[10px] text-gray-600 mb-2">Tipe Pengumuman</label>
                        <select
                            v-model="form.type"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm bg-white font-bold text-sm py-3 px-4"
                            required
                        >
                            <option value="PENTING">PENTING</option>
                            <option value="INFO">INFO</option>
                            <option value="PENGUMUMAN">PENGUMUMAN</option>
                        </select>
                        <div v-if="form.errors.type" class="mt-1 text-xs text-red-500 font-bold">{{ form.errors.type }}</div>
                    </div>

                    <div>
                        <label class="block font-black uppercase tracking-widest text-[10px] text-gray-600 mb-2">Isi Pengumuman</label>
                        <textarea
                            v-model="form.text"
                            rows="4"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm text-sm p-4 font-medium"
                            placeholder="Tuliskan isi pengumuman di sini..."
                            required
                        ></textarea>
                        <div v-if="form.errors.text" class="mt-1 text-xs text-red-500 font-bold">{{ form.errors.text }}</div>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t border-gray-100 gap-4">
                        <button type="button" @click="closeAddModal" class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 rounded-xl font-bold transition-all text-xs uppercase tracking-widest">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-green-500/20 text-xs uppercase tracking-widest flex items-center gap-2" :disabled="form.processing">
                            <span v-if="form.processing">Menyimpan...</span>
                            <span v-else>Simpan</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Edit Announcement Modal -->
        <Modal :show="isEditModalOpen" @close="closeEditModal">
            <div class="p-6 sm:p-8 bg-white/95 rounded-[2rem] border border-green-200 shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Edit Pengumuman</h2>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">Perbarui informasi pengumuman</p>
                    </div>
                    <button @click="closeEditModal" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="updateAnnouncement" class="space-y-6">
                    <div>
                        <label class="block font-black uppercase tracking-widest text-[10px] text-gray-600 mb-2">Tipe Pengumuman</label>
                        <select
                            v-model="form.type"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm bg-white font-bold text-sm py-3 px-4"
                            required
                        >
                            <option value="PENTING">PENTING</option>
                            <option value="INFO">INFO</option>
                            <option value="PENGUMUMAN">PENGUMUMAN</option>
                        </select>
                        <div v-if="form.errors.type" class="mt-1 text-xs text-red-500 font-bold">{{ form.errors.type }}</div>
                    </div>

                    <div>
                        <label class="block font-black uppercase tracking-widest text-[10px] text-gray-600 mb-2">Isi Pengumuman</label>
                        <textarea
                            v-model="form.text"
                            rows="4"
                            class="mt-1 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm text-sm p-4 font-medium"
                            placeholder="Tuliskan isi pengumuman di sini..."
                            required
                        ></textarea>
                        <div v-if="form.errors.text" class="mt-1 text-xs text-red-500 font-bold">{{ form.errors.text }}</div>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t border-gray-100 gap-4">
                        <button type="button" @click="closeEditModal" class="px-5 py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 rounded-xl font-bold transition-all text-xs uppercase tracking-widest">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-xl font-bold transition-all shadow-lg shadow-green-500/20 text-xs uppercase tracking-widest flex items-center gap-2" :disabled="form.processing">
                            <span v-if="form.processing">Memperbarui...</span>
                            <span v-else>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modern Delete Announcement Modal -->
        <Modal :show="showConfirmDeleteModal" @close="showConfirmDeleteModal = false" maxWidth="sm">
            <div class="p-6 text-left">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">Hapus Pengumuman</h3>
                </div>
                
                <p class="text-xs font-medium text-gray-500 mb-6 leading-relaxed">
                    Apakah Anda yakin ingin menghapus pengumuman ini? Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="flex justify-end gap-2.5">
                    <button 
                        @click="showConfirmDeleteModal = false"
                        class="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-xs font-black uppercase tracking-wider transition-colors border border-gray-200"
                    >
                        Batal
                    </button>
                    <button 
                        @click="deleteAnnouncement"
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
.stat-card {
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(22, 163, 74, 0.18);
    box-shadow: 0 4px 24px rgba(22, 163, 74, 0.07);
}
.content-card {
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(22, 163, 74, 0.15);
    box-shadow: 0 8px 32px rgba(22, 163, 74, 0.08);
}
</style>
