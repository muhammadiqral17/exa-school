<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import SignaturePad from '@/Components/SignaturePad.vue';

const props = defineProps({
    exam: Object,
    result: Object,
    answers: Object,
});

const page = usePage();
const flashSuccess = ref(page.props.flash?.success || '');

watch(() => page.props.flash?.success, (newVal) => {
    flashSuccess.value = newVal || '';
    if (newVal) setTimeout(() => { flashSuccess.value = ''; }, 5000);
});

const form = useForm({
    score: props.result.score,
    essay_score: props.result.essay_score || 0,
    teacher_notes: {}, // Will be initialized in a watch or directly
    teacher_signature: props.result.teacher_signature || null,
});

const isTeacherSigning = ref(false);

const cancelTeacherSigning = () => {
    isTeacherSigning.value = false;
    form.teacher_signature = props.result.teacher_signature;
};

const deleteTeacherSignature = () => {
    form.teacher_signature = null;
    isTeacherSigning.value = true;
};

// Initialize notes safely
watch(() => props.result, (newVal) => {
    form.teacher_notes = typeof newVal.teacher_notes === 'string' 
        ? JSON.parse(newVal.teacher_notes) 
        : (newVal.teacher_notes || {});
    form.teacher_signature = newVal.teacher_signature || null;
    isTeacherSigning.value = false;
}, { immediate: true });

watch(() => form.essay_score, (newVal) => {
    const pgWeight = props.exam.pg_weight || 100;
    const compScore = props.result.computer_score || 0; // Raw % (0-100)
    
    const pgContribution = (compScore * pgWeight / 100);
    const finalScore = pgContribution + parseFloat(newVal || 0);
    form.score = parseFloat(finalScore.toFixed(2));
});



const updateScore = () => {
    form.put(route('teacher.results.update', props.result.id), {
        preserveScroll: true,
    });
};

const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = 5;

const processedQuestions = computed(() => {
    let result = props.exam.questions || [];
    
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

watch(searchQuery, () => {
    currentPage.value = 1;
});

const getOriginalIndex = (question) => {
    return props.exam.questions.findIndex(q => q.id === question.id);
};

const getStudentAnswer = (questionId) => {
    if (!props.answers) return null;
    return props.answers[questionId] || null;
};

const isCorrect = (question) => {
    if (question.type !== 'pg') return null;
    const ans = getStudentAnswer(question.id);
    if (!ans && ans !== 0 && ans !== '0') return false;
    
    const correctAns = question.answer;
    if (correctAns === null || correctAns === undefined) return false;
    
    const correctAnswersArray = String(correctAns).split(',').map(s => s.trim().toLowerCase());
    const studentAnsStr = String(ans).trim().toLowerCase();
    
    const studentAnsIndex = !isNaN(studentAnsStr) ? studentAnsStr : (studentAnsStr.charCodeAt(0) - 97).toString();
    
    for (const ca of correctAnswersArray) {
        const caIndex = !isNaN(ca) ? ca : (ca.charCodeAt(0) - 97).toString();
        if (studentAnsIndex === caIndex) {
            return true;
        }
    }
    return false;
};

const getOptionText = (question, answerVal) => {
    if (answerVal === null || answerVal === undefined || answerVal === '') return '-';
    
    const answers = String(answerVal).split(',').map(s => s.trim());
    const texts = answers.map(ans => {
        let index = -1;
        if (!isNaN(ans)) {
            index = parseInt(ans);
        } else {
            index = String(ans).toLowerCase().charCodeAt(0) - 97;
        }
        
        if (question.options && Array.isArray(question.options) && question.options.length > index && index >= 0) {
            return question.options[index];
        }
        
        const letters = ['a', 'b', 'c', 'd'];
        if (index >= 0 && index < 4) {
            return question['option_' + letters[index]] || '-';
        }
        
        return '-';
    });
    
    return texts.join(' ATAU ');
};

const formatAnswerLabel = (answerVal) => {
    if (answerVal === null || answerVal === undefined || answerVal === '') return '-';
    const answers = String(answerVal).split(',').map(s => s.trim());
    return answers.map(ans => {
        if (!isNaN(ans)) {
            return String.fromCharCode(65 + parseInt(ans));
        }
        return String(ans).toUpperCase();
    }).join(' / ');
};

const getAnswerIndicesArray = (answerVal) => {
    if (answerVal === null || answerVal === undefined || answerVal === '') return [];
    return String(answerVal).split(',').map(s => {
        s = s.trim();
        return !isNaN(s) ? parseInt(s) : String(s).toLowerCase().charCodeAt(0) - 97;
    });
};

const pgStats = computed(() => {
    const pgQuestions = props.exam.questions ? props.exam.questions.filter(q => q.type === 'pg') : [];
    let correct = 0;
    let incorrect = 0;
    
    pgQuestions.forEach(q => {
        if (isCorrect(q)) {
            correct++;
        } else {
            incorrect++;
        }
    });
    
    return { correct, incorrect };
});

const hasEssay = computed(() => {
    return props.exam.questions ? props.exam.questions.some(q => q.type === 'essay') : false;
});

const parsedStudentNotes = computed(() => {
    if (!props.result.student_notes) return {};
    return typeof props.result.student_notes === 'string' 
        ? JSON.parse(props.result.student_notes) 
        : props.result.student_notes;
});

const parsedTeacherNotes = computed(() => {
    if (!props.result.teacher_notes) return {};
    return typeof props.result.teacher_notes === 'string' 
        ? JSON.parse(props.result.teacher_notes) 
        : props.result.teacher_notes;
});

</script>

<template>
    <Head :title="`Review: ${result.user.name}`" />
    <AuthenticatedLayout>
        <template #header>
            Review Nilai: <span class="text-gradient-gy capitalize">{{ result.user.name }}</span>
        </template>

        <div class="space-y-6 sm:space-y-10">
            <!-- Flash Message -->
            <div v-if="flashSuccess" class="p-4 bg-green-100 border border-green-200 text-green-700 rounded-2xl animate-fade-in-down flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                <span class="font-bold">{{ flashSuccess }}</span>
            </div>

            <div class="flex items-center gap-4">
                <Link :href="route('teacher.exams.results', exam.id)" class="p-2 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </Link>
                <div>
                    <h2 class="text-2xl font-black text-gray-800 tracking-tighter">Review Jawaban</h2>
                    <p class="text-sm text-gray-500 mt-1">Siswa: <span class="font-bold text-gray-700">{{ result.user.name }}</span> | Ujian: {{ exam.title }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left: Edit Score Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white/80 backdrop-blur-md border border-green-200/60 shadow-lg shadow-green-500/10 rounded-[2rem] p-6 sticky top-24">
                        <h3 class="text-lg font-black text-gray-800 mb-4 border-b border-gray-100 pb-2">Penilaian</h3>
                        <p class="text-xs text-gray-500 mb-6">Bobot Ujian: PG ({{ exam.pg_weight }}%) | Essay ({{ exam.essay_weight }}%)</p>
                        
                        <form @submit.prevent="updateScore" class="space-y-6">
                            <!-- 1. Box Pilihan Ganda (System) -->
                            <div class="relative group">
                                <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-blue-400 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
                                <div class="relative bg-white border border-blue-100 rounded-2xl p-4">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-blue-600 mb-1">Skor Pilihan Ganda (Sistem)</label>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-3xl font-black text-blue-600">{{ ((result.computer_score || 0) * exam.pg_weight / 100).toFixed(2) }}</span>
                                        <span class="text-xs font-bold text-gray-400">/ {{ exam.pg_weight }}</span>
                                    </div>
                                    <div class="mt-2 pt-2 border-t border-blue-50 flex justify-between items-center">
                                        <span class="text-[9px] text-gray-400 uppercase tracking-tighter">Akurasi Jawaban</span>
                                        <span class="text-[10px] font-bold text-blue-500">{{ result.computer_score !== null ? result.computer_score : 0 }}% Benar</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 2. Box Essay (Editable) -->
                            <div class="relative group">
                                <div class="absolute -inset-1 bg-gradient-to-r from-purple-600 to-purple-400 rounded-2xl blur opacity-25 group-hover:opacity-40 transition duration-1000 group-hover:duration-200"></div>
                                <div class="relative bg-white border border-purple-100 rounded-2xl p-4">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-purple-600 mb-2">Skor Essay (Input Guru)</label>
                                    <div class="flex items-center gap-3">
                                        <input type="number" step="0.01" min="0" :max="exam.essay_weight" v-model="form.essay_score" class="w-full text-2xl font-black text-purple-700 p-0 border-none focus:ring-0 bg-transparent placeholder-purple-200" placeholder="0.00">
                                        <span class="text-xs font-bold text-gray-400 shrink-0">/ {{ exam.essay_weight }}</span>
                                    </div>
                                    <div class="mt-2 pt-2 border-t border-purple-50 flex justify-between items-center">
                                        <span class="text-[9px] text-gray-400 uppercase tracking-tighter">Batas Nilai Essay</span>
                                        <span class="text-[10px] font-bold text-purple-500">Maks. {{ exam.essay_weight }} Poin</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Box Nilai Akhir (Calculated) -->
                            <div class="relative group">
                                <div class="absolute -inset-1 bg-gradient-to-r from-green-600 to-green-400 rounded-3xl blur opacity-30 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                                <div class="relative bg-gradient-to-br from-green-50 to-white border-2 border-green-200 rounded-3xl p-6 shadow-xl shadow-green-500/10">
                                    <label class="block text-xs font-black uppercase tracking-widest text-green-700 mb-3 text-center">Hasil Nilai Akhir</label>
                                    <div class="flex justify-center items-center gap-2">
                                        <span class="text-5xl font-black text-green-600">{{ form.score }}</span>
                                    </div>
                                    <p class="text-[10px] text-green-600/60 mt-3 text-center font-medium">
                                        Skor PG ({{ ((result.computer_score || 0) * exam.pg_weight / 100).toFixed(2) }}) + Skor Essay ({{ form.essay_score || 0 }})
                                    </p>
                                </div>
                            </div>

                            <!-- Student Signature (Read-Only) -->
                            <div class="relative group">
                                <div class="relative bg-white border border-gray-150 rounded-2xl p-4">
                                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">Tanda Tangan Siswa</label>
                                    <div v-if="result.student_signature" class="flex justify-center items-center py-2 bg-gray-50/50 rounded-xl border border-gray-100">
                                        <img :src="result.student_signature" alt="Tanda Tangan Siswa" class="max-h-20 object-contain pointer-events-none select-none" />
                                    </div>
                                    <div v-else class="text-center py-4 bg-gray-50/50 border border-dashed border-gray-250 rounded-xl text-[10px] font-bold text-gray-400">
                                        Siswa belum menandatangani
                                    </div>
                                </div>
                            </div>

                            <!-- Teacher Signature Box -->
                            <div class="relative group">
                                <div class="relative bg-white border border-gray-150 rounded-2xl p-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-500 font-bold">Tanda Tangan Guru</label>
                                        <button 
                                            v-if="form.teacher_signature && !isTeacherSigning"
                                            type="button" 
                                            @click="isTeacherSigning = true" 
                                            class="text-[10px] font-black text-green-600 hover:text-green-700 flex items-center gap-0.5"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                            Ubah
                                        </button>
                                    </div>
                                    
                                    <!-- Drawing Mode -->
                                    <div v-if="!form.teacher_signature || isTeacherSigning" class="space-y-2">
                                        <SignaturePad 
                                            placeholder="Tanda tangan guru di sini"
                                            :show-save-button="false"
                                            v-model="form.teacher_signature"
                                            @change="(val) => { form.teacher_signature = val; }"
                                        />
                                        <div v-if="result.teacher_signature && isTeacherSigning" class="text-right">
                                            <button 
                                                type="button"
                                                @click="cancelTeacherSigning"
                                                class="px-2.5 py-1 text-[10px] font-bold text-gray-500 hover:text-gray-700 bg-gray-100 rounded-lg transition-colors border border-gray-200"
                                            >
                                                Batal Ubah
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- View Mode -->
                                    <div v-else class="flex flex-col items-center justify-center py-2 bg-gray-50/50 rounded-xl border border-gray-100 relative group/t-sig">
                                        <img :src="form.teacher_signature" alt="Tanda Tangan Guru" class="max-h-20 object-contain pointer-events-none select-none" />
                                        <button 
                                            type="button"
                                            @click="deleteTeacherSignature"
                                            class="absolute top-1.5 right-1.5 p-1 bg-red-50 hover:bg-red-100 text-red-500 rounded-lg transition-colors border border-red-100 opacity-0 group-hover/t-sig:opacity-100"
                                            title="Hapus"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" :disabled="form.processing" class="w-full py-4 bg-gradient-to-r from-gray-800 to-gray-900 hover:from-black hover:to-gray-800 text-white rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl transition-all hover:-translate-y-1 active:translate-y-0 disabled:opacity-50">
                                <span v-if="form.processing" class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Menyimpan...
                                </span>
                                <span v-else>Simpan Semua Perubahan</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right: Questions and Answers -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Search Bar -->
                    <div class="bg-white/80 backdrop-blur-md shadow-sm rounded-3xl p-4 border border-gray-100 flex items-center gap-3 relative">
                        <div class="absolute inset-y-0 left-0 pl-7 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input v-model="searchQuery" type="text" placeholder="Cari berdasarkan nomor atau isi soal..." class="pl-12 pr-4 py-3 border border-gray-200 rounded-2xl text-sm focus:ring-green-500 focus:border-green-500 w-full transition-all bg-gray-50/50">
                    </div>

                    <!-- Statistics Summary -->
                    <div class="bg-white/60 backdrop-blur-md shadow-sm rounded-2xl p-4 border border-gray-100 flex flex-wrap items-center gap-6 animate-fade-in-down">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Benar PG</p>
                                <p class="text-lg font-black text-green-700 leading-none">{{ pgStats.correct }} <span class="text-xs font-medium text-gray-400 ml-1">Soal</span></p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Salah PG</p>
                                <p class="text-lg font-black text-red-700 leading-none">{{ pgStats.incorrect }} <span class="text-xs font-medium text-gray-400 ml-1">Soal</span></p>
                            </div>
                        </div>

                        <div v-if="hasEssay" class="ml-auto flex items-center gap-3 py-2 px-4 bg-blue-50 rounded-xl border border-blue-100">
                            <div class="relative">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <div class="absolute inset-0 w-2 h-2 bg-blue-500 rounded-full animate-ping"></div>
                            </div>
                            <span class="text-xs font-bold text-blue-700 italic uppercase tracking-wider">Essay belum dicek</span>
                        </div>
                    </div>

                    <div v-if="paginatedQuestions.length === 0" class="text-center py-10 bg-white/50 rounded-3xl border border-gray-100">
                        <p class="text-gray-500 font-medium">Tidak ada soal yang sesuai dengan pencarian Anda.</p>
                    </div>

                    <div v-for="question in paginatedQuestions" :key="question.id" class="bg-white/80 backdrop-blur-md border shadow-sm rounded-3xl p-6 transition-colors" :class="question.type === 'pg' ? (isCorrect(question) ? 'border-green-200 shadow-green-500/10' : 'border-red-200 shadow-red-500/10') : 'border-blue-200 shadow-blue-500/10'">
                        <div class="flex justify-between items-start mb-4">
                            <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest" :class="question.type === 'pg' ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700'">
                                Soal {{ getOriginalIndex(question) + 1 }} ({{ question.type === 'pg' ? 'Pilihan Ganda' : 'Essay' }})
                            </span>
                            
                            <!-- PG Status Indicator -->
                            <span v-if="question.type === 'pg'" class="px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest flex items-center gap-1" :class="isCorrect(question) ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-red-100 text-red-700 border border-red-200'">
                                <svg v-if="isCorrect(question)" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>
                                {{ isCorrect(question) ? 'Benar' : 'Salah' }}
                            </span>
                        </div>

                        <div v-if="question.image" class="mb-4 flex flex-wrap gap-4">
                            <img v-for="(img, iIdx) in question.image.split(',')" :key="'q_img_'+iIdx" :src="img.trim().startsWith('http') ? img.trim() : '/storage/' + img.trim()" alt="Gambar Soal" class="max-h-64 rounded-xl object-contain border border-gray-200 shadow-sm" />
                        </div>
                        <p class="text-gray-800 font-medium whitespace-pre-wrap mb-6">{{ question.question_text }}</p>

                        <!-- Pilihan Ganda Info -->
                        <div v-if="question.type === 'pg'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl border border-gray-100 bg-gray-50 flex flex-col">
                                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Jawaban Siswa</p>
                                <div class="font-medium text-gray-800 text-sm flex gap-2">
                                    <span class="font-black uppercase shrink-0">{{ formatAnswerLabel(getStudentAnswer(question.id)) }}.</span> 
                                    <div class="flex flex-col gap-2">
                                        <span>{{ getOptionText(question, getStudentAnswer(question.id)) }}</span>
                                        <img v-if="question.option_images && question.option_images[getStudentAnswer(question.id)]" :src="question.option_images[getStudentAnswer(question.id)].startsWith('http') ? question.option_images[getStudentAnswer(question.id)] : '/storage/' + question.option_images[getStudentAnswer(question.id)]" class="max-h-24 object-contain rounded-lg border border-gray-200" />
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 rounded-xl border border-green-100 bg-green-50 flex flex-col">
                                <p class="text-xs font-bold text-green-600 uppercase tracking-widest mb-2">Kunci Jawaban</p>
                                <div class="font-medium text-green-800 text-sm flex gap-2">
                                    <span class="font-black uppercase shrink-0">{{ formatAnswerLabel(question.answer) }}.</span>
                                    <div class="flex flex-col gap-2">
                                        <span>{{ getOptionText(question, question.answer) }}</span>
                                        <div v-if="question.option_images" class="flex flex-wrap gap-2">
                                            <template v-for="idx in getAnswerIndicesArray(question.answer)" :key="idx">
                                                <img v-if="question.option_images[idx]" :src="question.option_images[idx].startsWith('http') ? question.option_images[idx] : '/storage/' + question.option_images[idx]" class="max-h-24 object-contain rounded-lg border border-green-200" />
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Essay Info -->
                        <div v-else class="space-y-4">
                            <div class="p-4 rounded-xl border border-blue-100 bg-blue-50/50">
                                <p class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-2">Jawaban Siswa</p>
                                <p class="text-gray-800 whitespace-pre-wrap text-sm">{{ getStudentAnswer(question.id) || '(Tidak menjawab)' }}</p>
                            </div>
                        </div>

                        <!-- Box Catatan Siswa & Guru (Tambahan) -->
                        <div class="mt-6 pt-4 border-t border-gray-100 space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-green-600 block">Box Jawaban Guru (Referensi)</label>
                                    <div class="p-4 rounded-xl border border-green-100 bg-green-50/30 text-sm text-green-800 font-bold min-h-[60px] whitespace-pre-wrap">
                                        {{ question.reference_note || '(Referensi belum diisi oleh Admin)' }}
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-gray-400 block">Box Jawaban / Catatan Siswa</label>
                                    <div class="p-4 rounded-xl border border-gray-100 bg-gray-50 text-sm text-gray-700 min-h-[60px] whitespace-pre-wrap">
                                        {{ (parsedStudentNotes[question.id]) || '(Tidak ada catatan)' }}
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-blue-600 block">Koreksi / Feedback Guru Untuk Siswa</label>
                                <textarea
                                    v-model="form.teacher_notes[question.id]"
                                    rows="2"
                                    class="block w-full rounded-xl border-blue-100 bg-blue-50/10 focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all text-sm shadow-sm"
                                    placeholder="Tulis feedback untuk siswa..."
                                ></textarea>
                            </div>
                        </div>

                    </div>
                    
                    <!-- Pagination Controls -->
                    <div v-if="totalPages > 1" class="flex justify-center items-center gap-4 mt-6">
                        <button @click="currentPage--" :disabled="currentPage === 1" class="p-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-white bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <span class="text-sm font-bold text-gray-600 bg-white px-4 py-2 rounded-xl border border-gray-100 shadow-sm">
                            Hal {{ currentPage }} dari {{ totalPages }}
                        </span>
                        <button @click="currentPage++" :disabled="currentPage === totalPages" class="p-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-white bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
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
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-down { animation: fadeInDown 0.3s ease-out forwards; }
</style>
