<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    subjects: {
        type: Array,
        default: () => []
    },
    gurus: {
        type: Array,
        default: () => []
    },
});

const searchQuery = ref('');
const modalSearchQuery = ref('');
const selectedGroup = ref(null);
const selectedGroupIndex = ref(0);
const isDetailModalOpen = ref(false);

const groupedSubjects = computed(() => {
    const groups = {};
    
    if (!props.subjects) return [];

    // Grouping logic
    props.subjects.forEach(subject => {
        const name = subject.name || 'Tanpa Nama';
        if (!groups[name]) {
            groups[name] = {
                name: name,
                assignments: []
            };
        }
        groups[name].assignments.push(subject);
    });

    let result = Object.values(groups);

    // Filter logic
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(group => 
            group.name.toLowerCase().includes(query) ||
            group.assignments.some(a => 
                (a.user && a.user.name.toLowerCase().includes(query)) ||
                a.class.toLowerCase().includes(query) ||
                (a.academic_year && a.academic_year.toLowerCase().includes(query))
            )
        );
    }

    return result;
});

const openDetailModal = (group, index) => {
    selectedGroup.value = group;
    selectedGroupIndex.value = index;
    modalSearchQuery.value = '';
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    setTimeout(() => {
        modalSearchQuery.value = '';
    }, 300);
};

const filteredModalAssignments = computed(() => {
    if (!selectedGroup.value) return [];
    if (!modalSearchQuery.value) return selectedGroup.value.assignments;
    
    const query = modalSearchQuery.value.toLowerCase();
    return selectedGroup.value.assignments.filter(a => 
        (a.user && a.user.name.toLowerCase().includes(query)) ||
        (a.class && a.class.toLowerCase().includes(query)) ||
        (a.academic_year && a.academic_year.toLowerCase().includes(query)) ||
        (a.code && a.code.toLowerCase().includes(query))
    );
});

const getSubjectColor = (index) => {
    const colors = ['green', 'yellow', 'black'];
    return colors[index % colors.length];
};
</script>

<template>
    <Head title="Mata Pelajaran" />
    <AuthenticatedLayout>
        <template #header>
            Admin: <span class="text-gradient-gy capitalize">Mata Pelajaran</span>
        </template>

        <div class="space-y-6 sm:space-y-10">
            <!-- Header Section -->
            <div class="flex flex-col gap-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-black text-gray-800 tracking-tighter uppercase">Kategori Mata Pelajaran</h2>
                        <p class="text-sm text-gray-500 mt-1 font-medium italic">Klik "Tampilkan" untuk melihat rincian guru pengampu.</p>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-3 items-center">
                    <div class="relative w-full sm:w-80">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input v-model="searchQuery" type="text" placeholder="Cari mata pelajaran..." class="pl-10 pr-4 py-3 border border-gray-200 rounded-2xl text-sm focus:ring-green-500 focus:border-green-500 w-full transition-all shadow-sm">
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="groupedSubjects.length === 0" class="p-12 text-center bg-white/50 backdrop-blur-sm rounded-[2rem] border border-green-200 border-dashed animate-fade-in-down">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-green-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <h3 class="text-lg font-black text-gray-800 uppercase tracking-tighter">Data Kosong</h3>
                <p class="text-gray-500 mt-2 font-medium">Mata pelajaran tidak ditemukan atau belum diatur oleh admin.</p>
            </div>

            <!-- Grouped Boxes Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div v-for="(group, index) in groupedSubjects" :key="group.name" 
                     class="subject-card p-8 rounded-[2.5rem] relative overflow-hidden group hover:-translate-y-2 transition-all duration-500 animate-fade-in-down"
                     :style="{ animationDelay: (index * 0.05) + 's' }">
                    
                    <div class="absolute top-0 left-0 w-full h-2"
                        :class="[
                            getSubjectColor(index) === 'green' ? 'bg-green-500' : '',
                            getSubjectColor(index) === 'yellow' ? 'bg-yellow-500' : '',
                            getSubjectColor(index) === 'black' ? 'bg-black' : '',
                        ]">
                    </div>

                    <div class="relative z-10 flex flex-col justify-between h-full min-h-[160px]">
                        <div>
                            <div class="flex items-center justify-between mb-6">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center font-black text-white text-xl shadow-lg transform group-hover:rotate-6 transition-transform duration-500"
                                    :class="[
                                        getSubjectColor(index) === 'green' ? 'bg-gradient-to-br from-green-400 to-green-600 shadow-green-500/20' : '',
                                        getSubjectColor(index) === 'yellow' ? 'bg-gradient-to-br from-yellow-400 to-yellow-600 shadow-yellow-500/20' : '',
                                        getSubjectColor(index) === 'black' ? 'bg-black shadow-black/20' : '',
                                    ]">
                                    {{ group.name.charAt(0) }}
                                </div>
                                <div class="text-right">
                                    <div class="text-[9px] font-black text-gray-400 uppercase tracking-[0.2em] mb-1">Status</div>
                                    <div class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border flex items-center gap-1.5 shadow-sm"
                                        :class="[
                                            getSubjectColor(index) === 'green' ? 'bg-green-50 text-green-700 border-green-200' : '',
                                            getSubjectColor(index) === 'yellow' ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : '',
                                            getSubjectColor(index) === 'black' ? 'bg-black text-white border-black' : '',
                                        ]">
                                        <div class="w-1.5 h-1.5 rounded-full animate-pulse"
                                            :class="[
                                                getSubjectColor(index) === 'green' ? 'bg-green-500 shadow-[0_0_5px_rgba(34,197,94,0.8)]' : '',
                                                getSubjectColor(index) === 'yellow' ? 'bg-yellow-500 shadow-[0_0_5px_rgba(234,179,8,0.8)]' : '',
                                                getSubjectColor(index) === 'black' ? 'bg-white shadow-[0_0_5px_rgba(255,255,255,0.8)]' : '',
                                            ]"></div>
                                        {{ group.assignments.length }} Guru
                                    </div>
                                </div>
                            </div>
                            <h3 class="text-2xl font-black text-gray-800 leading-tight group-hover:text-green-600 transition-colors uppercase tracking-tighter mb-6">{{ group.name }}</h3>
                        </div>

                        <button @click="openDetailModal(group, index)" class="w-full py-3 text-white rounded-2xl font-bold text-xs uppercase tracking-widest transition-all shadow-lg flex items-center justify-center gap-2 mt-auto group/btn"
                            :class="[
                                getSubjectColor(index) === 'green' ? 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 shadow-green-500/30 hover:shadow-green-500/50' : '',
                                getSubjectColor(index) === 'yellow' ? 'bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 shadow-yellow-500/30 hover:shadow-yellow-500/50' : '',
                                getSubjectColor(index) === 'black' ? 'bg-black hover:bg-gray-800 shadow-black/30 hover:shadow-black/50' : '',
                            ]">
                            <span>Tampilkan Guru</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform group-hover/btn:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Modal -->
        <Modal :show="isDetailModalOpen" @close="closeDetailModal" maxWidth="3xl">
            <div v-if="selectedGroup" class="p-6 sm:p-8 bg-white/90 backdrop-blur-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-green-100/30 blur-3xl -mr-32 -mt-32 rounded-full pointer-events-none"></div>
                
                <div class="relative z-10">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h2 class="text-xl font-black text-gray-800 uppercase tracking-tighter">Rincian Pengampu: {{ selectedGroup.name }}</h2>
                            <p class="text-xs text-gray-500 font-bold uppercase tracking-widest mt-1">Daftar guru yang mengampu mata pelajaran ini</p>
                        </div>
                        <button @click="closeDetailModal" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Search -->
                    <div class="mb-6">
                        <div class="relative w-full">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input v-model="modalSearchQuery" type="text" placeholder="Cari nama guru, kelas, atau tahun ajaran..." class="pl-10 pr-4 py-3 border border-gray-200 rounded-2xl text-sm focus:ring-green-500 focus:border-green-500 w-full transition-all shadow-sm bg-gray-50/50">
                        </div>
                    </div>

                    <div v-if="filteredModalAssignments.length === 0" class="py-12 text-center bg-gray-50/50 rounded-3xl border border-dashed border-gray-200">
                        <p class="text-sm font-bold text-gray-500 uppercase tracking-widest">Guru tidak ditemukan.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[50vh] overflow-y-auto pr-2 custom-scrollbar">
                        <div v-for="assignment in filteredModalAssignments" :key="assignment.id" 
                             class="p-6 border border-gray-100 bg-white rounded-[2rem] hover:border-green-300 hover:shadow-xl hover:shadow-green-500/5 transition-all group/item flex items-center gap-5">
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-green-50 to-green-100 border border-green-200 flex items-center justify-center font-black text-green-600 text-lg group-hover/item:scale-110 transition-transform">
                                {{ assignment.class ? assignment.class.charAt(0) : '?' }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">{{ assignment.code }}</div>
                                <div class="text-base font-black text-gray-800 truncate uppercase leading-none mb-2">{{ assignment.user ? assignment.user.name : 'Guru Tidak Ditemukan' }}</div>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-blue-50 text-blue-600 text-[9px] font-black uppercase tracking-widest rounded-lg border border-blue-100">
                                        Kelas {{ assignment.class }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 bg-yellow-50 text-yellow-600 text-[9px] font-black uppercase tracking-widest rounded-lg border border-yellow-100">
                                        {{ assignment.academic_year || '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-8 pt-6 border-t border-gray-100">
                        <button @click="closeDetailModal" class="px-6 py-3 text-white rounded-2xl font-bold text-xs uppercase tracking-widest transition-all shadow-lg"
                            :class="[
                                getSubjectColor(selectedGroupIndex) === 'green' ? 'bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 shadow-green-500/30 hover:shadow-green-500/50' : '',
                                getSubjectColor(selectedGroupIndex) === 'yellow' ? 'bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 shadow-yellow-500/30 hover:shadow-yellow-500/50' : '',
                                getSubjectColor(selectedGroupIndex) === 'black' ? 'bg-black hover:bg-gray-800 shadow-black/30 hover:shadow-black/50' : '',
                            ]">
                            Tutup
                        </button>
                    </div>
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

.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(22, 163, 74, 0.2);
    border-radius: 10px;
}

@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-down { animation: fadeInDown 0.4s ease-out forwards; }
</style>
