<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    exam: Object,
    questions: Array,
    initialAnswers: Object,
    initialNotes: Object,
});

const page = usePage();
const userId = computed(() => page.props.auth.user.id);
const storageKey = `exam_state_${props.exam.id}_u${userId.value}`;

// Modern alert modal state
const alertModal = ref({
    show: false,
    title: '',
    message: '',
    confirmText: 'OK',
    isDanger: false,
    closeable: true,
    onClose: null,
});

const showAlert = (title, message, isDanger = false, closeable = true, onClose = null) => {
    alertModal.value = {
        show: true,
        title,
        message,
        confirmText: 'OK',
        isDanger,
        closeable,
        onClose,
    };
};

const handleAlertClose = () => {
    const callback = alertModal.value.onClose;
    alertModal.value.show = false;
    if (callback) {
        callback();
    }
};

const isLockedOut = ref(false);

// State
const timeLeft = ref(props.exam.remaining_seconds !== undefined ? props.exam.remaining_seconds : props.exam.duration_minutes * 60);
const timerInterval = ref(null);
const answers = ref({});
const studentNotes = ref({});
const doubtful = ref({});
const currentPage = ref(0);
const questionsPerPage = 1;

// Load state from local storage or database fallback
const loadState = () => {
    const savedState = localStorage.getItem(storageKey);
    let parsed = {};
    if (savedState) {
        try {
            parsed = JSON.parse(savedState) || {};
        } catch (e) {
            console.error("Failed to parse saved state", e);
        }
    }

    if (props.exam.has_start_time) {
        timeLeft.value = props.exam.remaining_seconds;
    } else {
        timeLeft.value = parsed.timeLeft !== undefined ? parsed.timeLeft : (props.exam.remaining_seconds !== undefined ? props.exam.remaining_seconds : props.exam.duration_minutes * 60);
    }

    // Merge database answers and local storage answers (local storage takes priority if exists)
    answers.value = {
        ...(props.initialAnswers || {}),
        ...(parsed.answers || {})
    };

    // Merge database notes and local storage notes
    studentNotes.value = {
        ...(props.initialNotes || {}),
        ...(parsed.studentNotes || {})
    };

    doubtful.value = parsed.doubtful || {};
    currentPage.value = parsed.currentPage || 0;
};

// Save state to local storage
const saveState = () => {
    localStorage.setItem(storageKey, JSON.stringify({
        timeLeft: timeLeft.value,
        answers: answers.value,
        studentNotes: studentNotes.value,
        doubtful: doubtful.value,
        currentPage: currentPage.value,
    }));
};

const formattedTime = computed(() => {
    const t = Math.max(0, Math.floor(timeLeft.value));
    const m = Math.floor(t / 60).toString().padStart(2, '0');
    const s = (t % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
});

const timerClass = computed(() => {
    if (timeLeft.value <= 60) return 'text-red-600 animate-pulse';
    if (timeLeft.value <= 300) return 'text-yellow-600';
    return 'text-green-600';
});

onMounted(() => {
    const sessionKey = `exam_session_${props.exam.id}_u${userId.value}`;
    const lockedKey = `exam_locked_${props.exam.id}_u${userId.value}`;
    const hasSession = sessionStorage.getItem(sessionKey);
    const hasState = localStorage.getItem(storageKey);

    const bypassKey = `exam_bypass_${props.exam.id}_u${userId.value}`;

    // PRIORITIZE database status
    if (props.exam.is_locked) {
        localStorage.setItem(lockedKey, 'true');
        window.location.href = route('student.exams.index');
        return;
    } else {
        // Teacher reactivated it, so clear local lock
        localStorage.removeItem(lockedKey);
    }

    if (hasState && !hasSession) {
        // CHECK FOR BYPASS (Teacher just unlocked this student)
        if (localStorage.getItem(bypassKey)) {
            localStorage.removeItem(bypassKey);
            // Allow them to continue without locking
        } else {
            // Only report cheat if we don't have a valid session flag from dashboard
            // and no bypass from teacher reactivation
            reportCheat();
            return;
        }
    }

    // Set session flag if it wasn't there
    if (!hasSession) {
        sessionStorage.setItem(sessionKey, 'true');
    }

    loadState();
    
    // Heartbeat every 30 seconds
    const pingInterval = setInterval(() => {
        axios.post(route('student.exams.ping', props.exam.id)).catch(e => console.error(e));
    }, 30000);

    // Visibility Change / Cheat Detection
    const handleVisibilityChange = () => {
        if (document.visibilityState === 'hidden') {
            reportCheat();
        }
    };

    const handleBlur = () => {
        reportCheat();
    };

    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('blur', handleBlur);

    timerInterval.value = setInterval(() => {
        if (timeLeft.value > 0) {
            timeLeft.value--;
            saveState(); // Save state every second
        } else {
            clearInterval(timerInterval.value);
            clearInterval(pingInterval);
            forceSubmitAnswers();
        }
    }, 1000);

    // Store intervals to cleanup
    onBeforeUnmount(() => {
        clearInterval(timerInterval.value);
        clearInterval(pingInterval);
        document.removeEventListener('visibilitychange', handleVisibilityChange);
        window.removeEventListener('blur', handleBlur);
    });
});

const reportCheat = () => {
    if (isLockedOut.value) return;
    isLockedOut.value = true;

    const lockedKey = `exam_locked_${props.exam.id}_u${userId.value}`;
    localStorage.setItem(lockedKey, 'true');

    axios.post(route('student.exams.cheat', props.exam.id))
        .catch(e => console.error("Failed to report cheating", e));

    showAlert(
        "Ujian Terkunci!",
        "Ujian Anda telah dikunci karena Anda terdeteksi keluar dari halaman ujian.",
        true, // isDanger
        false, // closeable
        () => {
            window.location.href = route('student.exams.index');
        }
    );
};

onBeforeUnmount(() => {
    // This is already handled inside onMounted's cleanup or here
});

let autosaveTimeout = null;
const saveStatus = ref({ text: 'Tersimpan ke server', colorClass: 'bg-green-500' });

const debouncedSave = () => {
    saveStatus.value = { text: 'Menyimpan...', colorClass: 'bg-blue-500 animate-pulse' };
    
    if (autosaveTimeout) clearTimeout(autosaveTimeout);
    
    autosaveTimeout = setTimeout(() => {
        axios.post(route('student.exams.autosave', props.exam.id), {
            answers: answers.value,
            student_notes: studentNotes.value,
        })
        .then(() => {
            saveStatus.value = { text: 'Tersimpan ke server', colorClass: 'bg-green-500' };
        })
        .catch(e => {
            console.error("Autosave failed", e);
            saveStatus.value = { text: 'Koneksi terputus! (Tersimpan lokal)', colorClass: 'bg-yellow-500' };
        });
    }, 1000);
};

watch([answers, studentNotes], () => {
    saveState();
    debouncedSave();
}, { deep: true });

watch([doubtful, currentPage], () => {
    saveState();
}, { deep: true });

const goToQuestion = (idx) => {
    currentPage.value = idx;
};

const pagedQuestions = computed(() => {
    const start = currentPage.value * questionsPerPage;
    return props.questions.slice(start, start + questionsPerPage);
});

const totalPages = computed(() => Math.ceil(props.questions.length / questionsPerPage));

const answeredCount = computed(() => Object.keys(answers.value).length);

const form = useForm({});

const forceSubmitAnswers = () => {
    clearInterval(timerInterval.value);
    localStorage.removeItem(storageKey); // Clear saved state on submit
    form.transform(() => ({
        answers: answers.value,
        student_notes: studentNotes.value,
    })).post(route('student.exams.submit', props.exam.id));
};

const submitAnswers = () => {
    const answeredCountReal = props.questions.filter(q => {
        const ans = answers.value[q.id];
        return ans !== undefined && ans !== null && String(ans).trim() !== '';
    }).length;

    if (answeredCountReal < props.questions.length) {
        showAlert(
            "Jawaban Belum Lengkap",
            "Mohon jawab semua soal sebelum mengumpulkan ujian.",
            false,
            true
        );
        return;
    }

    const hasDoubtful = props.questions.some(q => doubtful.value[q.id] === true);
    if (hasDoubtful) {
        showAlert(
            "Soal Ragu-Ragu",
            "Harap hilangkan status ragu-ragu pada semua soal sebelum mengumpulkan.",
            false,
            true
        );
        return;
    }

    forceSubmitAnswers();
};

const getOptions = (question) => {
    if (question.options && Array.isArray(question.options) && question.options.length > 0) {
        return question.options;
    }
    return [question.option_a, question.option_b, question.option_c, question.option_d].filter(o => o !== null && o !== undefined && o !== '');
};
</script>

<template>
    <Head :title="'Ujian: ' + exam.title" />
    <AuthenticatedLayout>
        <template #header>{{ exam.title }}</template>

        <div class="max-w-7xl mx-auto flex flex-col-reverse lg:flex-row gap-6 items-start">
            <!-- Left Side: Exam Content -->
            <div class="flex-1 space-y-6 w-full">
            <!-- Sticky Info Bar -->
            <div class="relative z-30 bg-white/90 backdrop-blur-md border border-green-200 rounded-2xl p-4 flex items-center justify-between shadow-sm">
                <div>
                    <div class="text-xs font-black text-gray-400 uppercase tracking-widest">{{ exam.subject_name }}</div>
                    <div class="text-sm font-bold text-gray-700 mt-0.5">{{ answeredCount }}/{{ questions.length }} Soal Dijawab</div>
                    <div class="flex items-center gap-1.5 mt-1">
                        <span class="inline-block w-2 h-2 rounded-full" :class="saveStatus.colorClass"></span>
                        <span class="text-[9px] font-black uppercase tracking-widest text-gray-400">{{ saveStatus.text }}</span>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-xs font-black text-gray-400 uppercase tracking-widest mb-1">Sisa Waktu</div>
                    <div class="text-3xl font-black tabular-nums flex items-baseline gap-1" :class="timerClass">
                        {{ formattedTime }} <span class="text-sm font-bold text-gray-500 tracking-widest">Tersisa</span>
                    </div>
                </div>
                <button @click="submitAnswers" :disabled="form.processing" class="px-6 py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-2xl font-bold text-sm uppercase tracking-widest transition-all shadow-lg shadow-green-500/20 disabled:opacity-60">
                    {{ form.processing ? 'Menyimpan...' : 'Kumpulkan' }}
                </button>
            </div>

            <!-- Questions -->
            <div class="space-y-6">
                <div v-for="(q, idx) in pagedQuestions" :key="q.id" class="question-card p-6 sm:p-8 rounded-[2rem]">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm shrink-0"
                             :class="answers[q.id] ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-400'">
                            {{ currentPage * questionsPerPage + idx + 1 }}
                        </div>
                        <div class="flex-1">
                            <span class="inline-block mb-3 px-2 py-0.5 text-[10px] font-black uppercase tracking-widest rounded-full"
                                  :class="q.type === 'pg' ? 'bg-blue-50 text-blue-600' : 'bg-yellow-50 text-yellow-600'">
                                {{ q.type === 'pg' ? 'Pilihan Ganda' : 'Essay' }}
                            </span>
                            <div v-if="q.image" class="mb-4 flex flex-wrap gap-4">
                                <img v-for="(img, iIdx) in q.image.split(',')" :key="'q_img_'+iIdx" :src="img.trim().startsWith('http') ? img.trim() : '/storage/' + img.trim()" alt="Gambar Soal" class="max-h-64 rounded-xl object-contain border border-gray-200 shadow-sm" />
                            </div>
                            <p class="text-gray-800 font-semibold text-base leading-relaxed mb-6">{{ q.question_text }}</p>

                            <!-- Pilihan Ganda -->
                            <div v-if="q.type === 'pg'" class="space-y-3">
                                <label v-for="(optText, oIdx) in getOptions(q)" :key="oIdx"
                                       :class="answers[q.id] == oIdx || answers[q.id] === String.fromCharCode(97 + oIdx) ? 'border-green-500 bg-green-50 ring-2 ring-green-200' : 'border-gray-200 hover:border-green-300 hover:bg-green-50/30'"
                                       class="flex items-center gap-4 p-4 rounded-2xl border-2 cursor-pointer transition-all">
                                    <input type="radio" :name="'q_' + q.id" :value="oIdx.toString()" v-model="answers[q.id]" class="sr-only" />
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-sm border-2 shrink-0"
                                         :class="answers[q.id] == oIdx || answers[q.id] === String.fromCharCode(97 + oIdx) ? 'bg-green-500 text-white border-green-500' : 'border-gray-200 text-gray-400'">
                                        {{ String.fromCharCode(65 + oIdx) }}
                                    </div>
                                    <div class="flex-1 flex flex-col gap-2">
                                        <span class="text-gray-700 font-medium">{{ optText }}</span>
                                        <img v-if="q.option_images && q.option_images[oIdx]" :src="q.option_images[oIdx].startsWith('http') ? q.option_images[oIdx] : '/storage/' + q.option_images[oIdx]" class="max-h-32 object-contain rounded-lg border border-gray-100 shadow-sm self-start" />
                                    </div>
                                </label>
                            </div>

                            <!-- Essay -->
                            <div v-else>
                                <textarea
                                    v-model="answers[q.id]"
                                    rows="5"
                                    class="block w-full rounded-2xl border-gray-200 focus:border-green-500 focus:ring focus:ring-green-200 transition-all resize-none"
                                    placeholder="Tulis jawaban Anda di sini..."
                                ></textarea>
                            </div>
                            
                            <!-- Box Jawaban/Catatan Siswa (Tambahan) -->
                            <div class="mt-6 pt-4 border-t border-gray-100">
                                <label class="text-[10px] font-black uppercase tracking-widest text-green-600 mb-2 block">BAB ATAU HALAMAN YANG KAMU PELAJARI</label>
                                <textarea
                                    v-model="studentNotes[q.id]"
                                    rows="3"
                                    class="block w-full rounded-xl border-green-100 focus:border-green-500 focus:ring focus:ring-green-200 transition-all shadow-sm text-sm"
                                    placeholder="Contoh: BAB 1 HALAMAN 12-15..."
                                ></textarea>
                            </div>

                            <!-- Ragu-Ragu Checkbox -->
                            <div class="mt-6 pt-4 border-t border-gray-100 flex justify-between items-center">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center justify-center w-6 h-6 rounded-md border-2 transition-all" :class="doubtful[q.id] ? 'bg-yellow-400 border-yellow-500' : 'border-gray-300 bg-white group-hover:border-yellow-400'">
                                        <svg v-if="doubtful[q.id]" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-900" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                        <input type="checkbox" v-model="doubtful[q.id]" class="opacity-0 absolute inset-0 cursor-pointer w-full h-full" />
                                    </div>
                                    <span class="text-sm font-black uppercase tracking-widest transition-colors" :class="doubtful[q.id] ? 'text-yellow-600' : 'text-gray-400 group-hover:text-yellow-500'">Ragu - Ragu</span>
                                </label>
                                
                                <div class="flex items-center gap-2">
                                    <button @click="currentPage--" v-if="currentPage > 0" class="px-4 py-2 border-2 border-gray-200 hover:border-green-400 text-gray-600 rounded-xl font-bold text-xs uppercase tracking-widest transition-all">
                                        ← Seb
                                    </button>
                                    <button @click="currentPage++" v-if="currentPage < totalPages - 1" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-md shadow-gray-800/20">
                                        Lanjut →
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            </div>
            
            <!-- Right Side: Question Navigation Bar -->
            <div class="w-full lg:w-80 relative lg:sticky lg:top-4 bg-white/90 backdrop-blur-md border border-green-200 rounded-3xl p-6 shadow-sm flex-shrink-0">
                <h3 class="text-sm font-black text-gray-700 uppercase tracking-widest mb-4">Navigasi Soal</h3>
                
                <div class="grid grid-cols-5 gap-2 sm:gap-3">
                    <button v-for="(q, idx) in questions" :key="'nav-'+q.id" 
                        @click="goToQuestion(idx)"
                        :class="[
                            'w-10 h-10 sm:w-12 sm:h-12 rounded-xl font-black text-sm sm:text-base transition-all border-2 flex items-center justify-center cursor-pointer hover:scale-105',
                            currentPage === idx ? 'ring-4 ring-green-500/30' : '',
                            doubtful[q.id] ? 'bg-yellow-400 text-yellow-900 border-yellow-500' :
                            (answers[q.id] ? 'bg-green-500 text-white border-green-600' : 'bg-gray-100 text-gray-500 border-gray-200 hover:border-green-300')
                        ]">
                        {{ idx + 1 }}
                    </button>
                </div>
                
                <div class="mt-8 space-y-3 bg-gray-50 p-4 rounded-2xl border border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 bg-green-500 rounded-md border border-green-600 shadow-sm"></div> 
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-widest">Sudah Dijawab</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 bg-yellow-400 rounded-md border border-yellow-500 shadow-sm"></div> 
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-widest">Ragu - Ragu</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 bg-gray-100 rounded-md border border-gray-200 shadow-sm"></div> 
                        <span class="text-xs font-bold text-gray-600 uppercase tracking-widest">Belum Dijawab</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reusable modern alert modal -->
        <Modal :show="alertModal.show" @close="handleAlertClose" :closeable="alertModal.closeable" maxWidth="sm">
            <div class="p-6 text-left">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :class="alertModal.isDanger ? 'bg-red-50' : 'bg-yellow-50'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" :class="alertModal.isDanger ? 'text-red-500' : 'text-yellow-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-black text-gray-800 uppercase tracking-wider">{{ alertModal.title }}</h3>
                </div>
                
                <p class="text-xs font-medium text-gray-500 mb-6 leading-relaxed">
                    {{ alertModal.message }}
                </p>

                <div class="flex justify-end">
                    <button 
                        @click="handleAlertClose"
                        class="px-5 py-2.5 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-md active:scale-95 bg-gradient-to-r"
                        :class="alertModal.isDanger 
                            ? 'from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 shadow-red-500/20' 
                            : 'from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 shadow-green-500/20'"
                    >
                        {{ alertModal.confirmText }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.question-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(22, 163, 74, 0.12);
    box-shadow: 0 4px 20px rgba(22, 163, 74, 0.06);
}
</style>
