<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import SignaturePad from '@/Components/SignaturePad.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    exam: Object,
    result: Object,
});

const isSigning = ref(false);
const showConfirmDeleteModal = ref(false);

const saveSignature = (base64) => {
    router.post(route('student.exams.signature', props.exam.id), {
        signature: base64
    }, {
        onSuccess: () => {
            isSigning.value = false;
        }
    });
};

const deleteSignature = () => {
    showConfirmDeleteModal.value = true;
};

const confirmDeleteSignature = () => {
    showConfirmDeleteModal.value = false;
    router.delete(route('student.exams.delete-signature', props.exam.id));
};
</script>

<template>
    <Head title="Hasil Ujian" />
    <AuthenticatedLayout>
        <template #header>Hasil <span class="text-gradient-gy">Ujian</span></template>

        <div class="max-w-2xl mx-auto">
            <div class="result-card p-8 sm:p-12 rounded-[2.5rem] text-center">
                <div class="w-28 h-28 mx-auto rounded-full flex items-center justify-center mb-8 font-black text-5xl"
                     :class="result.score >= 70 ? 'bg-gradient-to-br from-green-400 to-green-600 text-white' : 'bg-gradient-to-br from-red-400 to-red-600 text-white'">
                    {{ Math.round(result.score) }}
                </div>

                <h2 class="text-3xl font-black text-gray-800 mb-2">{{ exam.title }}</h2>
                <p class="text-gray-500 mb-8">{{ exam.subject_name }}</p>

                <div class="grid grid-cols-2 gap-4 mb-10">
                    <div class="p-4 bg-blue-50 border border-blue-100 rounded-2xl">
                        <div class="text-[10px] font-black uppercase tracking-widest text-blue-500 mb-1">Bobot PG</div>
                        <div class="text-2xl font-black text-blue-800">{{ exam.pg_weight }}%</div>
                    </div>
                    <div class="p-4 bg-yellow-50 border border-yellow-100 rounded-2xl">
                        <div class="text-[10px] font-black uppercase tracking-widest text-yellow-500 mb-1">Bobot Essay</div>
                        <div class="text-2xl font-black text-yellow-800">{{ exam.essay_weight }}%</div>
                    </div>
                </div>

                <div class="p-6 rounded-2xl mb-8"
                     :class="result.score >= 70 ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                    <div class="text-xs font-black uppercase tracking-widest mb-2"
                         :class="result.score >= 70 ? 'text-green-500' : 'text-red-500'">
                        {{ result.score >= 70 ? 'Selamat! Anda LULUS' : 'Anda Belum Lulus' }}
                    </div>
                    <div class="text-6xl font-black"
                         :class="result.score >= 70 ? 'text-green-600' : 'text-red-600'">
                        {{ Math.round(result.score) }}
                    </div>
                    <div class="text-sm text-gray-500 mt-2">(Nilai Minimum Lulus: 70)</div>
                </div>

                <p class="text-xs text-gray-400 mb-8">* Nilai essay akan ditambahkan setelah dinilai oleh guru.</p>

                <!-- Signature Box -->
                <div class="my-8 p-6 border border-gray-200/80 rounded-3xl bg-gray-50/50 text-left">
                    <div class="flex items-center justify-between mb-4 border-b border-gray-200/50 pb-3">
                        <div>
                            <h4 class="text-sm font-black text-gray-800 uppercase tracking-wider">Tanda Tangan Siswa</h4>
                            <p class="text-[10px] text-gray-400 mt-0.5 font-medium">Tanda tangan hasil pengerjaan ujian Anda</p>
                        </div>
                        <button 
                            v-if="!isSigning" 
                            @click="isSigning = true" 
                            class="p-2 bg-white hover:bg-gray-100 text-green-600 rounded-xl transition-all border border-gray-200/80 hover:-translate-y-0.5 shadow-sm"
                            title="Tanda Tangani / Ubah"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Mode: Drawing Signature -->
                    <div v-if="isSigning" class="animate-fade-in-down">
                        <SignaturePad 
                            placeholder="Tuliskan tanda tangan Anda di sini"
                            @save="saveSignature"
                            @cancel="isSigning = false"
                        />
                    </div>

                    <!-- Mode: Display Existing Signature -->
                    <div v-else-if="result.student_signature" class="flex flex-col items-center justify-center py-4 bg-white border border-gray-150 rounded-2xl relative group/sig shadow-sm">
                        <img 
                            :src="result.student_signature" 
                            alt="Tanda Tangan Siswa" 
                            class="max-h-28 object-contain pointer-events-none select-none"
                        />
                        <button 
                            @click="deleteSignature"
                            class="absolute top-2 right-2 p-1.5 bg-red-50 hover:bg-red-100 text-red-500 rounded-lg transition-colors border border-red-100 shadow-sm opacity-0 group-hover/sig:opacity-100 focus:opacity-100"
                            title="Hapus Tanda Tangan"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>

                    <!-- Mode: Empty / No Signature Yet -->
                    <div 
                        v-else 
                        @click="isSigning = true"
                        class="flex flex-col items-center justify-center py-8 bg-white border border-dashed border-gray-300 rounded-2xl cursor-pointer hover:border-green-500 hover:bg-green-50/10 transition-all group"
                    >
                        <div class="w-12 h-12 rounded-full bg-gray-50 flex items-center justify-center group-hover:bg-green-100 transition-colors shadow-sm mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 group-hover:text-green-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-gray-500 group-hover:text-green-700 transition-colors">Belum ada tanda tangan. Klik untuk membuat.</span>
                    </div>
                </div>

                <Link :href="route('student.exams.index')" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-2xl font-bold uppercase tracking-widest transition-all shadow-lg shadow-green-500/25 hover:-translate-y-0.5">
                    ← Kembali ke Daftar Ujian
                </Link>
            </div>
        </div>

        <!-- Modern Confirmation Modal -->
        <Modal :show="showConfirmDeleteModal" @close="showConfirmDeleteModal = false" maxWidth="sm">
            <div class="p-6 text-left">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">Hapus Tanda Tangan</h3>
                </div>
                
                <p class="text-xs font-medium text-gray-500 mb-6 leading-relaxed">
                    Apakah Anda yakin ingin menghapus tanda tangan ini? Tindakan ini akan menghapus data tanda tangan hasil ujian Anda secara permanen.
                </p>

                <div class="flex justify-end gap-2.5">
                    <button 
                        @click="showConfirmDeleteModal = false"
                        class="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-xs font-black uppercase tracking-wider transition-colors border border-gray-200"
                    >
                        Batal
                    </button>
                    <button 
                        @click="confirmDeleteSignature"
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
.result-card {
    background: rgba(255, 255, 255, 0.90);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(22, 163, 74, 0.15);
    box-shadow: 0 8px 40px rgba(22, 163, 74, 0.10);
}
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-down { animation: fadeInDown 0.3s ease-out forwards; }
</style>
