<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const isSidebarOpen = ref(true); // Default value, will be set on mount
const user = usePage().props.auth.user;

const handleResize = () => {
    if (window.innerWidth >= 1024) {
        isSidebarOpen.value = true;
    } else {
        isSidebarOpen.value = false;
    }
};

onMounted(() => {
    handleResize(); // Set initial state based on actual device width
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const closeSidebar = () => {
    if (window.innerWidth < 1024) {
        isSidebarOpen.value = false;
    }
};

const showConfirmLogoutModal = ref(false);

const handleLogout = () => {
    if (route().current('student.exams.take')) {
        showConfirmLogoutModal.value = true;
    } else {
        router.post(route('logout'));
    }
};

const confirmLogout = () => {
    showConfirmLogoutModal.value = false;
    router.post(route('logout'));
};
</script>

<template>
    <div class="h-screen w-full bg-[#f0fdf4] text-gray-800 flex overflow-hidden selection:bg-green-400/30">
        
        <!-- Sidebar -->
        <aside 
            :class="[
                'h-full w-72 border-r transition-all duration-500 ease-in-out shrink-0 sidebar-card rounded-none z-50',
                isSidebarOpen ? 'ml-0' : '-ml-72'
            ]"
        >
            <div class="flex flex-col h-full p-6">
                <!-- Close Button (Mobile Only) -->
                <button @click="closeSidebar" class="lg:hidden absolute top-6 right-6 p-2 text-gray-400 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Sidebar Header (Logo & Brand) -->
                <div class="flex items-center gap-4 mb-12">
                    <div class="h-12 w-12 p-2 logo-card rounded-xl">
                        <ApplicationLogo />
                    </div>
                    <div>
                        <span class="text-2xl font-black tracking-tighter uppercase block leading-none text-gray-800">
                            Exa<span class="text-green-600">School</span>
                        </span>
                        <span class="text-[9px] uppercase tracking-[0.3em] text-yellow-600 font-bold ml-1">Control Center</span>
                    </div>
                </div>

                <!-- Navigation Links -->
                <nav class="flex-1 space-y-2">
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em] mb-4 ml-2">Main Menu</div>
                    
                    <Link :href="route('dashboard')" @click="closeSidebar" :class="[
                        'flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-300 group',
                        route().current('dashboard') ? 'bg-green-500 text-white font-bold shadow-lg shadow-green-500/25' : 'hover:bg-green-50 text-gray-500 hover:text-green-700'
                    ]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </Link>

                    <template v-if="user.role.toLowerCase() === 'admin'">
                        <Link :href="route('admin.users.create')" @click="closeSidebar" :class="[
                            'flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-300 group',
                            route().current('admin.users.create') ? 'bg-green-500 text-white font-bold shadow-lg shadow-green-500/25' : 'hover:bg-green-50 text-gray-500 hover:text-green-700'
                        ]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Manajemen User
                        </Link>
                        <Link :href="route('admin.admins.index')" @click="closeSidebar" :class="[
                            'flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-300 group',
                            route().current('admin.admins.index') ? 'bg-green-500 text-white font-bold shadow-lg shadow-green-500/25' : 'hover:bg-green-50 text-gray-500 hover:text-green-700'
                        ]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Data Admin
                        </Link>
                        <Link :href="route('admin.students.index')" @click="closeSidebar" :class="[
                            'flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-300 group',
                            route().current('admin.students.index') ? 'bg-green-500 text-white font-bold shadow-lg shadow-green-500/25' : 'hover:bg-green-50 text-gray-500 hover:text-green-700'
                        ]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Data Siswa
                        </Link>
                        <Link :href="route('admin.teachers.index')" @click="closeSidebar" :class="[
                            'flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-300 group',
                            route().current('admin.teachers.index') ? 'bg-green-500 text-white font-bold shadow-lg shadow-green-500/25' : 'hover:bg-green-50 text-gray-500 hover:text-green-700'
                        ]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Data Guru
                        </Link>
                        <Link :href="route('admin.assignments.index')" @click="closeSidebar" :class="[
                            'flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-300 group',
                            route().current('admin.assignments.index') ? 'bg-green-500 text-white font-bold shadow-lg shadow-green-500/25' : 'hover:bg-green-50 text-gray-500 hover:text-green-700'
                        ]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                            Mata Pelajaran
                        </Link>
                        <Link :href="route('admin.subjects.index')" @click="closeSidebar" :class="[
                            'flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-300 group',
                            route().current('admin.subjects.index') ? 'bg-green-500 text-white font-bold shadow-lg shadow-green-500/25' : 'hover:bg-green-50 text-gray-500 hover:text-green-700'
                        ]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            Bank Soal
                        </Link>
                        <Link :href="route('admin.exams.history')" @click="closeSidebar" :class="[
                            'flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-300 group',
                            route().current('admin.exams.history') ? 'bg-green-500 text-white font-bold shadow-lg shadow-green-500/25' : 'hover:bg-green-50 text-gray-500 hover:text-green-700'
                        ]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Riwayat Hasil Ujian
                        </Link>
                    </template>

                    <template v-if="user.role.toLowerCase() === 'guru'">
                        <Link :href="route('teacher.schedule')" @click="closeSidebar" :class="[
                            'flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-300 group',
                            route().current('teacher.schedule') ? 'bg-green-500 text-white font-bold shadow-lg shadow-green-500/25' : 'hover:bg-green-50 text-gray-500 hover:text-green-700'
                        ]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Jadwal Mengajar
                        </Link>
                        <Link :href="route('teacher.exams')" @click="closeSidebar" :class="[
                            'flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-300 group',
                            route().current('teacher.exams*') ? 'bg-green-500 text-white font-bold shadow-lg shadow-green-500/25' : 'hover:bg-green-50 text-gray-500 hover:text-green-700'
                        ]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Kelola Ujian
                        </Link>
                    </template>

                    <template v-if="user && user.role && user.role.toLowerCase() === 'siswa'">
                        <Link :href="route('student.exams.index')" @click="closeSidebar" :class="[
                            'flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-300 group cursor-pointer relative z-50',
                            (route().current('student.exams.*') && !route().current('student.exams.results')) ? 'bg-green-500 text-white font-bold shadow-lg shadow-green-500/25' : 'hover:bg-green-50 text-gray-500 hover:text-green-700'
                        ]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            <span class="font-bold text-left">Ikuti Ujian</span>
                        </Link>
                    </template>

                    <!-- Nilai Ujian (Siswa) -->
                    <template v-if="user && user.role && user.role.toLowerCase() === 'siswa'">
                        <Link :href="route('student.exams.results')" @click="closeSidebar" :class="[
                            'flex items-center gap-4 px-4 py-3 rounded-2xl transition-all duration-300 group cursor-pointer relative z-50',
                            route().current('student.exams.results') ? 'bg-green-500 text-white font-bold shadow-lg shadow-green-500/25' : 'hover:bg-green-50 text-gray-500 hover:text-green-700'
                        ]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span class="font-bold text-left">Nilai Ujian</span>
                        </Link>
                    </template>
                </nav>

                <div class="mt-auto pt-6 border-t border-green-200/60">
                    <div class="flex items-center gap-3 p-2 user-card rounded-2xl">
                        <div v-if="user.avatar" class="w-10 h-10 rounded-xl overflow-hidden shrink-0">
                            <img :src="user.avatar.startsWith('http') ? user.avatar : '/foto-profile/' + (user.avatar.includes('/') ? user.avatar.split('/').pop() : user.avatar)" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-10 h-10 rounded-xl bg-gradient-to-tr from-green-500 to-yellow-400 flex items-center justify-center font-black text-white text-sm shrink-0">
                            {{ user.name.charAt(0) }}
                        </div>
                        <div class="flex-1 overflow-hidden">
                            <div class="text-sm font-bold text-gray-800 truncate">{{ user.name }}</div>
                            <div v-if="user.role.toLowerCase() === 'siswa'" class="space-y-0.5">
                                <div class="text-[9px] text-gray-500 font-bold tracking-wider">NIS: {{ user.nis_nik || '-' }}</div>
                                <div class="text-[9px] text-green-600 font-black uppercase tracking-widest">Kelas: {{ user.class || '-' }}</div>
                            </div>
                            <div v-else class="text-[10px] text-gray-400 uppercase font-black truncate tracking-widest">{{ user.role }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="h-full w-full lg:flex-1 shrink-0 flex flex-col relative transition-all duration-500 ease-in-out">
            
            <!-- Contextual Mobile Backdrop -->
            <Transition
                enter-active-class="transition-opacity duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div 
                    v-if="isSidebarOpen" 
                    @click="closeSidebar"
                    class="absolute inset-0 bg-white/40 backdrop-blur-[2px] z-30 lg:hidden"
                ></div>
            </Transition>

            <!-- Top Navbar -->
            <header class="h-20 bg-white/70 backdrop-blur-md border-b border-green-200/60 flex items-center justify-between px-4 sm:px-8 z-40 shadow-sm">
                <div class="flex items-center gap-6">
                    <!-- Toggle Menu Button -->
                    <button @click="toggleSidebar" class="p-3 topbar-btn rounded-xl transition-all group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    
                    <h1 v-if="$slots.header" class="text-lg sm:text-xl font-black text-gray-800 uppercase tracking-tighter truncate max-w-[200px] sm:max-w-none">
                        <slot name="header" />
                    </h1>
                </div>

                <div class="flex items-center gap-4">
                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="topbar-btn rounded-xl transition-all text-gray-500 hover:text-gray-700 flex items-center gap-2 pr-3 overflow-hidden">
                                <div v-if="user.avatar" class="w-10 h-10 shrink-0">
                                    <img :src="user.avatar.startsWith('http') ? user.avatar : '/foto-profile/' + (user.avatar.includes('/') ? user.avatar.split('/').pop() : user.avatar)" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-10 h-10 bg-gradient-to-tr from-green-500 to-yellow-400 flex items-center justify-center font-black text-white text-sm shrink-0">
                                    {{ user.name.charAt(0) }}
                                </div>
                                <span class="text-xs font-bold uppercase tracking-widest hidden sm:block">{{ user.name.split(' ')[0] }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1 hidden sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </template>
                        <template #content>
                            <div class="p-2 bg-white border border-green-200/60 rounded-2xl shadow-xl shadow-green-500/10">
                                <DropdownLink :href="route('profile.edit')" class="rounded-xl hover:bg-green-50 transition-colors text-gray-700">Profil</DropdownLink>
                                <button type="button" @click="handleLogout" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-red-50 hover:text-red-600 rounded-xl transition-colors text-gray-700">Keluar</button>
                            </div>
                        </template>
                    </Dropdown>
                </div>
            </header>

            <!-- Content Container -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-8 custom-scrollbar">
                <slot />
                
                <!-- Bottom Decor -->
                <div class="mt-20 py-8 border-t border-green-200/60 flex justify-between items-center text-gray-400 text-[10px] font-bold uppercase tracking-widest">
                    <div>ExaSchool Management System v2.5</div>
                    <div>&copy; 2026 ExaSchool</div>
                </div>
            </div>
        </main>

        <!-- Modern Logout Confirmation Modal -->
        <Modal :show="showConfirmLogoutModal" @close="showConfirmLogoutModal = false" maxWidth="sm">
            <div class="p-6 text-left">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">Keluar dari Ujian?</h3>
                </div>
                
                <p class="text-xs font-medium text-gray-500 mb-6 leading-relaxed">
                    Peringatan: Ujian Anda sedang berlangsung. Jika Anda keluar sekarang, ujian Anda akan <span class="font-bold text-red-600">TERKUNCI</span> dan Anda harus meminta guru untuk membukanya kembali. Apakah Anda yakin ingin keluar?
                </p>

                <div class="flex justify-end gap-2.5">
                    <button 
                        @click="showConfirmLogoutModal = false"
                        class="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-xs font-black uppercase tracking-wider transition-colors border border-gray-200"
                    >
                        Batal
                    </button>
                    <button 
                        @click="confirmLogout"
                        class="px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors shadow-md shadow-red-500/10"
                    >
                        Ya, Keluar
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>

<style scoped>
.sidebar-card {
    background: rgba(255, 255, 255, 0.90);
    backdrop-filter: blur(20px);
    border-color: rgba(22, 163, 74, 0.15);
    box-shadow: 4px 0 30px rgba(22, 163, 74, 0.06);
}

.logo-card {
    background: rgba(240, 253, 244, 0.9);
    border: 1px solid rgba(22, 163, 74, 0.2);
}

.user-card {
    background: rgba(240, 253, 244, 0.8);
    border: 1px solid rgba(22, 163, 74, 0.15);
}

.topbar-btn {
    background: rgba(240, 253, 244, 0.8);
    border: 1px solid rgba(22, 163, 74, 0.15);
}

.topbar-btn:hover {
    background: rgba(220, 252, 231, 0.9);
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
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(22, 163, 74, 0.4);
}
</style>
