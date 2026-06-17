<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    exam: Object,
});

const editingQuestionId = ref(null);

const form = useForm({
    type: 'pg',
    question_text: '',
    options: ['', '', '', ''],
    option_images: [null, null, null, null],
    answer: [],
    reference_note: '',
    image: null,
});

// Pagination and Filtering
const searchQuery = ref('');
const typeFilter = ref('');
const currentPage = ref(1);
const itemsPerPage = 5;

const processedQuestions = computed(() => {
    let result = props.exam.questions || [];
    
    if (typeFilter.value) {
        result = result.filter(q => q.type === typeFilter.value);
    }
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(q => {
            const originalIndex = (props.exam.questions.findIndex(eq => eq.id === q.id) + 1).toString();
            return originalIndex.includes(query) || q.question_text.toLowerCase().includes(query);
        });
    }
    
    return result;
});

const totalPages = computed(() => {
    return Math.ceil(processedQuestions.value.length / itemsPerPage);
});

const paginatedQuestions = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return processedQuestions.value.slice(start, end);
});

watch([searchQuery, typeFilter], () => {
    currentPage.value = 1;
});

const getOriginalIndex = (question) => {
    return props.exam.questions.findIndex(q => q.id === question.id) + 1;
};

const isAnswer = (question, index) => {
    if (question.answer === null || question.answer === undefined) return false;
    const ansStr = question.answer.toString();
    if (ansStr.includes(',')) {
        return ansStr.split(',').map(s => s.trim()).includes(index.toString());
    }
    return ansStr === index.toString() || ansStr === String.fromCharCode(97 + index);
};

const getOptions = (question) => {
    if (question.options && Array.isArray(question.options) && question.options.length > 0) {
        return [...question.options];
    }
    return [question.option_a, question.option_b, question.option_c, question.option_d].filter(o => o !== null && o !== undefined && o !== '');
};

const addOption = () => {
    form.options.push('');
    form.option_images.push(null);
};

const removeOption = (index) => {
    if (form.options.length > 2) {
        form.options.splice(index, 1);
        form.option_images.splice(index, 1);
        form.answer = form.answer.filter(a => parseInt(a) !== index).map(a => parseInt(a) > index ? (parseInt(a) - 1).toString() : a);
    }
};

const editQuestion = (question) => {
    editingQuestionId.value = question.id;
    form.type = question.type;
    form.question_text = question.question_text;
    
    let opts = getOptions(question);
    if (opts.length === 0) opts = ['', '', '', ''];
    form.options = opts;
    
    // Handle old answers (a, b, c, d) and multiple answers
    if (question.answer !== null && question.answer !== undefined) {
        let ansStr = question.answer.toString();
        if (ansStr.includes(',')) {
            form.answer = ansStr.split(',').map(s => s.trim());
        } else if (isNaN(ansStr)) {
            const charCode = ansStr.toLowerCase().charCodeAt(0);
            form.answer = [(charCode - 97).toString()];
        } else {
            form.answer = [ansStr];
        }
    } else {
        form.answer = [];
    }
    
    form.reference_note = question.reference_note || '';
    form.image = null;
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelEdit = () => {
    editingQuestionId.value = null;
    form.reset();
    form.options = ['', '', '', ''];
    form.option_images = [null, null, null, null];
    form.answer = [];
    form.reference_note = '';
};

const submitQuestion = () => {
    // Fill option_a to option_d for backward compatibility
    const submitData = {
        ...form.data(),
        answer: Array.isArray(form.answer) ? form.answer.join(',') : form.answer,
        option_a: form.options[0] || null,
        option_b: form.options[1] || null,
        option_c: form.options[2] || null,
        option_d: form.options[3] || null,
    };

    if (editingQuestionId.value) {
        router.post(route('admin.questions.update', editingQuestionId.value), {
            _method: 'PUT',
            ...submitData
        }, {
            onSuccess: () => {
                editingQuestionId.value = null;
                form.reset();
                form.options = ['', '', '', ''];
                form.option_images = [null, null, null, null];
                form.answer = [];
                form.reference_note = '';
            },
            preserveScroll: true,
        });
    } else {
        router.post(route('admin.exams.questions.store', props.exam.id), submitData, {
            onSuccess: () => {
                const currentType = form.type;
                form.reset();
                form.type = currentType;
                form.options = ['', '', '', ''];
                form.option_images = [null, null, null, null];
                form.answer = [];
                form.reference_note = '';
            },
            preserveScroll: true,
        });
    }
};

const showConfirmDeleteModal = ref(false);
const questionIdToDelete = ref(null);

const deleteQuestion = (questionId) => {
    questionIdToDelete.value = questionId;
    showConfirmDeleteModal.value = true;
};

const confirmDeleteQuestion = () => {
    if (!questionIdToDelete.value) return;
    router.delete(route('admin.questions.destroy', { exam: props.exam.id, question: questionIdToDelete.value }), {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmDeleteModal.value = false;
            questionIdToDelete.value = null;
        }
    });
};
</script>

<template>
    <Head :title="editingQuestionId ? 'Edit Soal' : 'Input Soal Manual'" />
    <AuthenticatedLayout>
        <template #header>
            {{ editingQuestionId ? 'Edit' : 'Input' }} Soal: <span class="text-gradient-gy capitalize">{{ exam.title }}</span>
        </template>

        <div class="space-y-6 sm:space-y-10 max-w-5xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 tracking-tighter">
                        {{ editingQuestionId ? 'Perbarui Data Soal' : 'Tambah Soal Baru' }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Bank Soal: <span class="font-bold text-green-600">{{ exam.subject.name }}</span> | Total Soal: <span class="font-bold">{{ exam.questions ? exam.questions.length : 0 }}</span></p>
                </div>
                
                <Link :href="route('admin.subjects.index')" class="px-6 py-3 border-2 border-gray-200 hover:border-green-500 hover:bg-green-50 text-gray-600 rounded-2xl font-bold transition-all text-sm uppercase tracking-widest flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Selesai & Simpan
                </Link>
            </div>

            <div class="content-card p-6 sm:p-10 rounded-[2.5rem]">
                <form @submit.prevent="submitQuestion" class="space-y-8">
                    <!-- Tipe Soal -->
                    <div>
                        <InputLabel value="Tipe Soal" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-4" />
                        <div class="flex gap-4">
                            <label :class="{'border-green-500 bg-green-50 ring-2 ring-green-200 text-green-700': form.type === 'pg', 'border-gray-200 text-gray-500 hover:border-green-300': form.type !== 'pg'}" class="flex-1 flex justify-center py-3 border-2 rounded-2xl cursor-pointer transition-all font-bold text-sm uppercase tracking-widest">
                                <input type="radio" name="type" value="pg" v-model="form.type" class="sr-only" />
                                Pilihan Ganda
                            </label>
                            <label :class="{'border-yellow-500 bg-yellow-50 ring-2 ring-yellow-200 text-yellow-700': form.type === 'essay', 'border-gray-200 text-gray-500 hover:border-yellow-300': form.type !== 'essay'}" class="flex-1 flex justify-center py-3 border-2 rounded-2xl cursor-pointer transition-all font-bold text-sm uppercase tracking-widest">
                                <input type="radio" name="type" value="essay" v-model="form.type" class="sr-only" />
                                Essay
                            </label>
                        </div>
                    </div>

                    <!-- Pertanyaan -->
                    <div>
                        <InputLabel for="question_text" value="Pertanyaan" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <textarea
                            id="question_text"
                            v-model="form.question_text"
                            rows="4"
                            class="block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm resize-none"
                            required
                            placeholder="Ketikkan soal Anda di sini..."
                        ></textarea>
                        <InputError class="mt-2" :message="form.errors.question_text" />
                    </div>

                    <!-- Upload Gambar -->
                    <div>
                        <InputLabel for="image" value="Gambar Soal (Opsional)" class="font-black uppercase tracking-widest text-xs text-gray-600 mb-2" />
                        <input type="file" id="image" accept="image/*" @change="e => form.image = e.target.files[0]" class="block w-full text-sm text-gray-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-xl file:border-0
                            file:text-sm file:font-bold
                            file:bg-green-100 file:text-green-700
                            hover:file:bg-green-200 transition-all cursor-pointer border border-gray-200 rounded-2xl p-2" />
                        <p class="text-xs text-gray-400 mt-2 font-bold">Upload gambar jika soal membutuhkan visual (maks 2MB).</p>
                        <InputError class="mt-2" :message="form.errors.image" />
                    </div>

                    <!-- Box Referensi (Jawaban Guru) -->
                    <div>
                        <InputLabel for="reference_note" value="Box Jawaban Guru (Referensi Bab/Halaman)" class="font-black uppercase tracking-widest text-xs text-green-600 mb-2" />
                        <textarea
                            id="reference_note"
                            v-model="form.reference_note"
                            rows="2"
                            class="block w-full rounded-2xl border-green-100 bg-green-50/20 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm resize-none"
                            placeholder="Contoh: BAB 1 HALAMAN 12-15"
                        ></textarea>
                        <p class="text-[10px] text-gray-400 mt-2 font-bold italic">* Isi dengan referensi bab/halaman untuk memudahkan koreksi nantinya.</p>
                        <InputError class="mt-2" :message="form.errors.reference_note" />
                    </div>

                    <!-- Pilihan Ganda Options -->
                    <Transition
                        enter-active-class="transition-opacity duration-300 ease-out"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition-opacity duration-200 ease-in"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <div v-if="form.type === 'pg'" class="space-y-6 pt-6 border-t border-gray-100">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-black text-gray-700 tracking-tighter">Opsi Jawaban</h4>
                                <button type="button" @click="addOption" class="text-xs font-bold bg-green-100 text-green-700 px-3 py-1.5 rounded-lg hover:bg-green-200 transition-colors flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Tambah Opsi
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-for="(opt, index) in form.options" :key="index" class="relative group p-3 border border-gray-100 rounded-2xl bg-white shadow-sm">
                                    <div class="relative">
                                        <span class="absolute top-3 left-4 font-black text-gray-300">{{ String.fromCharCode(65 + index) }}</span>
                                        <TextInput :id="'option_'+index" type="text" class="pl-10 pr-10 block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200" v-model="form.options[index]" required :placeholder="'Teks Opsi ' + String.fromCharCode(65 + index)" />
                                        <button v-if="form.options.length > 2" type="button" @click="removeOption(index)" class="absolute top-2.5 right-3 text-red-300 hover:text-red-500 transition-colors p-1 bg-white rounded-lg opacity-0 group-hover:opacity-100" title="Hapus Opsi">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="mt-2">
                                        <input type="file" :id="'option_image_'+index" accept="image/*" @change="e => form.option_images[index] = e.target.files[0]" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 transition-all cursor-pointer border border-gray-200 rounded-xl p-1" />
                                    </div>
                                </div>
                            </div>

                            <div class="p-6 rounded-2xl bg-green-50 border border-green-200">
                                <InputLabel value="Jawaban Benar (Bisa lebih dari 1)" class="font-black uppercase tracking-widest text-xs text-green-800 mb-4" />
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <label v-for="(opt, index) in form.options" :key="index" 
                                           class="flex items-center gap-3 p-4 border-2 rounded-2xl cursor-pointer transition-all hover:border-green-300 shadow-sm"
                                           :class="form.answer.includes(index.toString()) ? 'border-green-500 bg-white ring-2 ring-green-200 shadow-md transform -translate-y-0.5' : 'border-gray-200 bg-white'">
                                        <input type="checkbox" v-model="form.answer" :value="index.toString()" class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500 transition-colors" />
                                        <span class="font-bold text-gray-700">Opsi {{ String.fromCharCode(65 + index) }}</span>
                                    </label>
                                </div>
                                <p v-if="form.answer.length === 0" class="text-xs text-red-500 font-bold mt-3">Pilih minimal 1 jawaban benar.</p>
                            </div>
                        </div>
                    </Transition>

                    <!-- Submit Button -->
                    <div class="flex items-center justify-end pt-6 border-t border-gray-100 gap-4">
                        <button v-if="editingQuestionId" type="button" @click="cancelEdit" class="px-6 py-4 border border-gray-200 hover:bg-gray-50 text-gray-600 rounded-2xl font-bold transition-all text-sm uppercase tracking-widest">
                            Batal Edit
                        </button>
                        <button type="submit" class="px-8 py-4 bg-gradient-to-r from-gray-800 to-gray-900 hover:from-black hover:to-black text-white rounded-2xl font-bold transition-all shadow-xl shadow-gray-900/20 hover:shadow-gray-900/40 uppercase tracking-widest flex items-center gap-3" :disabled="form.processing">
                            <svg v-if="!editingQuestionId" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span v-if="form.processing">{{ editingQuestionId ? 'Memperbarui...' : 'Menyimpan...' }}</span>
                            <span v-else>{{ editingQuestionId ? 'Update Soal' : 'Tambah Soal ke Database' }}</span>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- List Soal yang sudah dibuat -->
            <div class="content-card p-6 sm:p-10 rounded-[2.5rem] mt-8" v-if="exam.questions && exam.questions.length > 0">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <h3 class="text-xl font-black text-gray-800 tracking-tighter">Preview Soal Anda</h3>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input v-model="searchQuery" type="text" placeholder="Cari nomor atau isi..." class="pl-10 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-green-500 focus:border-green-500 w-full transition-all shadow-sm">
                        </div>
                        <select v-model="typeFilter" class="py-2 pl-3 pr-8 border border-gray-200 rounded-xl text-sm focus:ring-green-500 focus:border-green-500 transition-all shadow-sm bg-white">
                            <option value="">Semua Tipe</option>
                            <option value="pg">Pilihan Ganda</option>
                            <option value="essay">Essay</option>
                        </select>
                    </div>
                </div>

                <div v-if="paginatedQuestions.length === 0" class="text-center py-8 text-gray-500 font-medium">
                    Tidak ada soal yang sesuai dengan pencarian atau filter.
                </div>

                <div v-else class="space-y-4">
                    <div v-for="q in paginatedQuestions" :key="q.id" class="p-6 rounded-2xl border border-gray-200 bg-gray-50/50 relative group">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full" :class="q.type === 'pg' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">
                                    {{ q.type === 'pg' ? 'Pilihan Ganda' : 'Essay' }}
                                </span>
                                <span class="text-xs font-bold text-gray-400">Soal #{{ getOriginalIndex(q) }}</span>
                            </div>
                            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="editQuestion(q)" class="p-2 bg-white text-yellow-600 rounded-lg shadow-sm border border-yellow-100 hover:bg-yellow-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click="deleteQuestion(q.id)" class="p-2 bg-white text-red-600 rounded-lg shadow-sm border border-red-100 hover:bg-red-50 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <div v-if="q.image" class="mb-4 flex flex-wrap gap-4">
                            <img v-for="(img, iIdx) in q.image.split(',')" :key="'q_img_'+iIdx" :src="img.trim().startsWith('http') ? img.trim() : '/storage/' + img.trim()" alt="Gambar Soal" class="max-h-48 rounded-xl object-contain border border-gray-200 shadow-sm" />
                        </div>
                        <p class="text-gray-800 font-medium mb-4">{{ q.question_text }}</p>
                        
                        <div v-if="q.type === 'pg'" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-600 mt-2">
                            <div v-for="(opt, oIdx) in getOptions(q)" :key="oIdx" :class="isAnswer(q, oIdx) ? 'font-bold text-green-600 bg-green-50 px-3 py-2 rounded-xl border border-green-200' : 'px-3 py-2 border border-transparent'" class="transition-all flex flex-col gap-2">
                                <span>{{ String.fromCharCode(65 + oIdx) }}. {{ opt }}</span>
                                <img v-if="q.option_images && q.option_images[oIdx]" :src="q.option_images[oIdx].startsWith('http') ? q.option_images[oIdx] : '/storage/' + q.option_images[oIdx]" class="max-h-24 object-contain rounded-lg border border-gray-100 shadow-sm self-start" />
                            </div>
                        </div>

                        <!-- Preview Box Catatan (Tambahan) -->
                        <div class="mt-6 pt-4 border-t border-gray-100 space-y-4">
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1 block">Box Jawaban/Catatan Siswa (Preview)</label>
                                <div class="w-full h-20 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 flex items-center justify-center text-xs text-gray-400 font-medium italic">
                                    Tempat siswa menulis jawaban/catatan tambahan...
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-widest text-green-600 mb-1 block">Box Jawaban Guru (Hanya Guru yang melihat)</label>
                                <div class="w-full min-h-[50px] p-4 rounded-xl border border-green-100 bg-green-50/50 text-xs text-green-800 font-bold">
                                    {{ q.reference_note || 'Belum diisi referensi...' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination Controls -->
                <div v-if="totalPages > 1" class="flex justify-center items-center gap-4 mt-8 pt-6 border-t border-gray-100">
                    <button @click="currentPage--" :disabled="currentPage === 1" class="p-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <span class="text-sm font-bold text-gray-600">
                        Halaman {{ currentPage }} dari {{ totalPages }}
                    </span>
                    <button @click="currentPage++" :disabled="currentPage === totalPages" class="p-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modern Delete Confirmation Modal -->
        <Modal :show="showConfirmDeleteModal" @close="showConfirmDeleteModal = false" maxWidth="sm">
            <div class="p-6 text-left">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">Hapus Soal</h3>
                </div>
                
                <p class="text-xs font-medium text-gray-500 mb-6 leading-relaxed">
                    Apakah Anda yakin ingin menghapus soal ini dari ujian? Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="flex justify-end gap-2.5">
                    <button 
                        @click="showConfirmDeleteModal = false"
                        class="px-4 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-xs font-black uppercase tracking-wider transition-colors border border-gray-200"
                    >
                        Batal
                    </button>
                    <button 
                        @click="confirmDeleteQuestion"
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
.content-card {
    background: rgba(255, 255, 255, 0.80);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(22, 163, 74, 0.15);
    box-shadow: 0 8px 32px rgba(22, 163, 74, 0.08);
}
</style>
