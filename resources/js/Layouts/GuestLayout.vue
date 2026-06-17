<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-[#f0fdf4] relative overflow-hidden">
        <!-- Back Button -->
        <Link href="/" class="absolute top-6 left-6 z-50 group flex items-center gap-3">
            <div class="h-10 w-10 glass-card rounded-xl flex items-center justify-center group-hover:bg-green-500 group-hover:text-white transition-all duration-300 shadow-lg shadow-green-500/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </div>
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest hidden sm:block group-hover:text-green-600 transition-colors">Beranda</span>
        </Link>
        <!-- Dramatic Background Decor -->
        <div class="absolute inset-0 z-0">
            <div class="absolute top-[20%] left-[-10%] w-[60%] h-[60%] bg-green-300/40 blur-[200px] rounded-full animate-float"></div>
            <div class="absolute bottom-[20%] right-[-10%] w-[60%] h-[60%] bg-yellow-300/30 blur-[200px] rounded-full"></div>
        </div>

        <div class="z-10 text-center mb-10">
            <Link href="/" class="group">
                <div class="inline-block h-28 w-28 p-3 glass-card rounded-[2rem] group-hover:scale-105 group-hover:border-green-500/50 transition-all duration-700 overflow-hidden shadow-[0_0_50px_rgba(22,163,74,0.15)]">
                    <ApplicationLogo />
                </div>
                <h1 class="mt-6 text-4xl font-black text-gray-800 tracking-tighter uppercase leading-none">Exa<span class="text-green-600">School</span></h1>
                <div class="mt-2 inline-block px-3 py-1 bg-yellow-400/20 border border-yellow-500/30 rounded-full">
                    <p class="text-yellow-700 text-[9px] font-black uppercase tracking-[0.4em]">Integrated Secure System</p>
                </div>
            </Link>
        </div>

        <div class="w-[92%] sm:max-w-md mt-6 px-6 sm:px-10 py-10 sm:py-12 glass-card rounded-[3rem] z-10 shadow-2xl relative overflow-hidden">
            <!-- Top Deco Line -->
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-green-500/50 to-transparent"></div>
            
            <slot />
        </div>

        <div class="mt-12 z-10 flex flex-col items-center gap-4">

            <div class="flex gap-6">
                <div class="w-2 h-2 rounded-full bg-green-500/50"></div>
                <div class="w-2 h-2 rounded-full bg-yellow-500/50"></div>
                <div class="w-2 h-2 rounded-full bg-emerald-400/50"></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.glass-card {
    background: rgba(255, 255, 255, 0.80);
    backdrop-filter: blur(24px);
    border: 1px solid rgba(22, 163, 74, 0.15);
    box-shadow: 0 20px 60px rgba(22, 163, 74, 0.10);
}
</style>
