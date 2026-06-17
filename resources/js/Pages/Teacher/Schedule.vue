<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    subjects: Array,
});

const searchQuery = ref('');
const sortField = ref('name');
const sortOrder = ref('asc');

const toggleSort = (field) => {
    if (sortField.value === field) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortOrder.value = 'asc';
    }
};

const filteredSubjects = computed(() => {
    let result = [...props.subjects];
    
    // Search Filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(subject => 
            JSON.stringify(subject).toLowerCase().includes(query)
        );
    }

    // Sort Filter
    result.sort((a, b) => {
        let fieldA = a[sortField.value] || '';
        let fieldB = b[sortField.value] || '';
        
        if (typeof fieldA === 'string') fieldA = fieldA.toLowerCase();
        if (typeof fieldB === 'string') fieldB = fieldB.toLowerCase();
        
        if (fieldA < fieldB) return sortOrder.value === 'asc' ? -1 : 1;
        if (fieldA > fieldB) return sortOrder.value === 'asc' ? 1 : -1;
        return 0;
    });

    return result;
});
</script>

<template>
    <Head title="Jadwal Mengajar" />
    <AuthenticatedLayout>
        <template #header>
            Guru: <span class="text-gradient-gy capitalize">Jadwal Mengajar</span>
        </template>

        <div class="space-y-6 sm:space-y-10">
            <!-- Header Section -->
            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 tracking-tighter">Jadwal Mengajar Anda</h2>
                    <p class="text-sm text-gray-500 mt-1">Daftar kelas dan mata pelajaran yang Anda ampu.</p>
                </div>
                
                <div class="flex flex-wrap gap-2 items-center">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input v-model="searchQuery" type="text" placeholder="Cari jadwal..." class="pl-10 pr-4 py-3 border border-gray-200 rounded-2xl text-sm focus:ring-green-500 focus:border-green-500 w-full sm:w-64 transition-all shadow-sm">
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredSubjects.length === 0" class="p-12 text-center bg-white/50 backdrop-blur-sm rounded-[2rem] border border-green-200 border-dashed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-green-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <h3 class="text-lg font-black text-gray-800">Belum Ada Jadwal</h3>
                <p class="text-gray-500 mt-2">Anda belum ditugaskan untuk mengajar di kelas manapun.</p>
            </div>

            <div v-else class="bg-white/80 backdrop-blur-md border border-green-200/60 shadow-lg shadow-green-500/10 rounded-[2rem] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-green-50/50 border-b border-green-100">
                                <th class="p-4 text-xs font-black uppercase tracking-widest text-green-700">No</th>
                                <th @click="toggleSort('name')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        Mata Pelajaran
                                        <svg v-if="sortField === 'name'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-0 group-hover:opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                                <th @click="toggleSort('academic_year')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        TA
                                        <svg v-if="sortField === 'academic_year'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-0 group-hover:opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                                <th @click="toggleSort('code')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        Kode
                                        <svg v-if="sortField === 'code'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-0 group-hover:opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-0 group-hover:opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                                <th @click="toggleSort('schedule_time')" class="p-4 text-xs font-black uppercase tracking-widest text-green-700 cursor-pointer hover:bg-green-100 transition-colors group">
                                    <div class="flex items-center gap-1">
                                        Jam Mengajar
                                        <svg v-if="sortField === 'schedule_time'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="sortOrder === 'desc' ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-0 group-hover:opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/50">
                            <tr v-for="(subject, index) in filteredSubjects" :key="subject.id" class="hover:bg-green-50/30 transition-colors">
                                <td class="p-4 text-sm font-medium text-gray-500">{{ index + 1 }}</td>
                                <td class="p-4 text-sm font-bold text-gray-800">{{ subject.name }}</td>
                                <td class="p-4 text-sm font-medium text-gray-600">
                                    <span v-if="subject.academic_year" class="px-2 py-1 bg-blue-50 border border-blue-100 rounded text-[10px] font-black text-blue-600">{{ subject.academic_year }}</span>
                                    <span v-else class="text-gray-300">-</span>
                                </td>
                                <td class="p-4 text-sm font-medium text-gray-600">
                                    <span class="px-2 py-1 bg-gray-100 border border-gray-200 rounded text-xs font-bold">{{ subject.code }}</span>
                                </td>
                                <td class="p-4 text-sm font-bold text-green-600">{{ subject.class || 'Belum diatur' }}</td>
                                <td class="p-4 text-sm font-medium text-gray-700">{{ subject.schedule_time || 'Belum diatur' }}</td>
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
