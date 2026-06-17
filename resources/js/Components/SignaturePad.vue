<script setup>
import { ref, onMounted, onUnmounted, nextTick, computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: null
    },
    placeholder: {
        type: String,
        default: 'Tanda tangan di sini'
    },
    showSaveButton: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['update:modelValue', 'change', 'save', 'cancel']);

const canvas = ref(null);
const isDrawing = ref(false);
const history = ref([]);
const redoHistory = ref([]);
const canvasEmpty = ref(true);

let lastX = 0;
let lastY = 0;
let ctx = null;
let resizeObserver = null;

const canUndo = computed(() => history.value.length > 1);
const canRedo = computed(() => redoHistory.value.length > 0);

const getCoordinates = (event) => {
    if (!canvas.value) return { x: 0, y: 0 };
    const rect = canvas.value.getBoundingClientRect();
    
    // Support touch and mouse events
    let clientX, clientY;
    if (event.touches && event.touches.length > 0) {
        clientX = event.touches[0].clientX;
        clientY = event.touches[0].clientY;
    } else {
        clientX = event.clientX;
        clientY = event.clientY;
    }
    
    return {
        x: clientX - rect.left,
        y: clientY - rect.top
    };
};

const drawGrid = () => {
    if (!ctx || !canvas.value) return;
    
    // Subtle guideline in the lower third
    ctx.strokeStyle = '#e2e8f0';
    ctx.lineWidth = 1.5;
    ctx.setLineDash([6, 6]);
    ctx.beginPath();
    ctx.moveTo(20, canvas.value.height * 0.7);
    ctx.lineTo(canvas.value.width - 20, canvas.value.height * 0.7);
    ctx.stroke();
    ctx.setLineDash([]); // Reset dash
    
    // Guideline text
    ctx.fillStyle = '#94a3b8';
    ctx.font = '10px sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(props.placeholder, canvas.value.width / 2, canvas.value.height * 0.85);
};

const initCanvas = () => {
    if (!canvas.value) return;
    ctx = canvas.value.getContext('2d', { willReadFrequently: true });
    
    canvas.value.width = canvas.value.clientWidth || 300;
    canvas.value.height = canvas.value.clientHeight || 150;
    
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.value.width, canvas.value.height);
    
    drawGrid();
    
    // Add default initial empty state
    history.value = [canvas.value.toDataURL()];
    redoHistory.value = [];
    canvasEmpty.value = true;
};

onMounted(() => {
    nextTick(() => {
        initCanvas();
        
        if (window.ResizeObserver) {
            resizeObserver = new ResizeObserver(() => {
                if (!canvas.value) return;
                const prevContent = history.value[history.value.length - 1];
                
                canvas.value.width = canvas.value.clientWidth;
                canvas.value.height = canvas.value.clientHeight;
                
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.value.width, canvas.value.height);
                drawGrid();
                
                if (prevContent && history.value.length > 1) {
                    const img = new Image();
                    img.src = prevContent;
                    img.onload = () => {
                        ctx.drawImage(img, 0, 0);
                    };
                }
            });
            resizeObserver.observe(canvas.value);
        }
    });
});

onUnmounted(() => {
    if (resizeObserver) {
        resizeObserver.disconnect();
    }
});

const startDrawing = (event) => {
    isDrawing.value = true;
    const { x, y } = getCoordinates(event);
    lastX = x;
    lastY = y;
    
    ctx.beginPath();
    ctx.moveTo(x, y);
    
    // Minimal ink dot on tap
    ctx.fillStyle = '#000000';
    ctx.fillRect(x, y, 1, 1);
};

const draw = (event) => {
    if (!isDrawing.value) return;
    
    if (event.cancelable) {
        event.preventDefault();
    }
    
    const { x, y } = getCoordinates(event);
    
    ctx.strokeStyle = '#0f172a'; // Slate-900 for premium pen style
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(x, y);
    ctx.stroke();
    
    lastX = x;
    lastY = y;
    
    canvasEmpty.value = false;
};

const stopDrawing = () => {
    if (!isDrawing.value) return;
    isDrawing.value = false;
    
    const dataUrl = canvas.value.toDataURL();
    redoHistory.value = [];
    history.value.push(dataUrl);
    
    if (history.value.length > 25) {
        history.value.shift();
    }
    
    canvasEmpty.value = false;
    emit('change', dataUrl);
    emit('update:modelValue', dataUrl);
};

const restoreState = (dataUrl) => {
    const img = new Image();
    img.src = dataUrl;
    img.onload = () => {
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.value.width, canvas.value.height);
        drawGrid();
        ctx.drawImage(img, 0, 0);
        
        // Emitting empty if restored to initial state
        const isEmptyState = history.value.length === 1;
        const outUrl = isEmptyState ? null : dataUrl;
        emit('change', outUrl);
        emit('update:modelValue', outUrl);
    };
};

const undo = () => {
    if (canUndo.value) {
        const current = history.value.pop();
        redoHistory.value.push(current);
        const previous = history.value[history.value.length - 1];
        restoreState(previous);
        canvasEmpty.value = history.value.length === 1;
    }
};

const redo = () => {
    if (canRedo.value) {
        const next = redoHistory.value.pop();
        history.value.push(next);
        restoreState(next);
        canvasEmpty.value = false;
    }
};

const clear = () => {
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.value.width, canvas.value.height);
    drawGrid();
    history.value = [canvas.value.toDataURL()];
    redoHistory.value = [];
    canvasEmpty.value = true;
    emit('change', null);
    emit('update:modelValue', null);
};

const save = () => {
    if (canvasEmpty.value) return;
    const dataUrl = canvas.value.toDataURL();
    emit('save', dataUrl);
};

const cancel = () => {
    emit('cancel');
};

defineExpose({
    clear,
    undo,
    redo,
    save,
    isEmpty: () => canvasEmpty.value,
    getDataUrl: () => canvasEmpty.value ? null : canvas.value.toDataURL()
});
</script>

<template>
    <div class="signature-pad-container flex flex-col gap-3">
        <div class="relative w-full h-44 rounded-2xl border border-dashed border-gray-300 bg-white overflow-hidden shadow-inner group hover:border-gray-400 transition-colors">
            <canvas
                ref="canvas"
                class="absolute inset-0 w-full h-full cursor-crosshair touch-none"
                @mousedown="startDrawing"
                @mousemove="draw"
                @mouseup="stopDrawing"
                @mouseleave="stopDrawing"
                @touchstart="startDrawing"
                @touchmove="draw"
                @touchend="stopDrawing"
            ></canvas>
        </div>

        <div class="flex items-center justify-between gap-2">
            <!-- Undo/Redo/Clear controls -->
            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    @click="undo"
                    :disabled="!canUndo"
                    class="p-2 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-xl disabled:opacity-40 disabled:hover:bg-gray-50 transition-colors border border-gray-200"
                    title="Undo"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                    </svg>
                </button>
                
                <button
                    type="button"
                    @click="redo"
                    :disabled="!canRedo"
                    class="p-2 bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-xl disabled:opacity-40 disabled:hover:bg-gray-50 transition-colors border border-gray-200"
                    title="Redo"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 000 12h3" />
                    </svg>
                </button>

                <button
                    type="button"
                    @click="clear"
                    :disabled="canvasEmpty"
                    class="p-2 bg-gray-50 hover:bg-gray-100 text-red-500 rounded-xl disabled:opacity-40 disabled:hover:bg-gray-50 transition-colors border border-gray-200"
                    title="Hapus"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>

            <!-- Action controls (Save/Cancel) -->
            <div class="flex items-center gap-1.5">
                <button
                    v-if="showSaveButton"
                    type="button"
                    @click="cancel"
                    class="px-3.5 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 font-bold text-xs uppercase tracking-wider rounded-xl transition-colors border border-gray-200"
                >
                    Batal
                </button>
                <button
                    v-if="showSaveButton"
                    type="button"
                    @click="save"
                    :disabled="canvasEmpty"
                    class="px-3.5 py-1.5 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-md shadow-green-500/10"
                >
                    Simpan
                </button>
            </div>
        </div>
    </div>
</template>
