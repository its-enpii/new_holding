import { reactive } from 'vue';

const state = reactive({
    visible: false,
    message: '',
    title: '',
    tone: 'success',
    timer: null,
    remainingTime: 4500,
    startTime: 0,
});

function normalize(value) {
    if (value == null || value === '') return { message: '', title: '' };
    if (typeof value === 'string') return { message: value, title: '' };
    if (typeof value === 'object') {
        const msg = typeof value.message === 'string' ? value.message : (typeof value.msg === 'string' ? value.msg : '');
        const title = typeof value.title === 'string' ? value.title : '';
        return { message: msg, title };
    }
    return { message: String(value), title: '' };
}

function show(tone = 'success', value, { duration = 4500 } = {}) {
    const { message, title } = normalize(value);
    if (!message && !title) return;

    if (state.timer) clearTimeout(state.timer);

    state.tone = tone;
    state.message = message;
    state.title = title;
    state.visible = true;
    state.remainingTime = duration;
    state.startTime = Date.now();

    if (duration > 0) {
        state.timer = setTimeout(() => {
            state.visible = false;
        }, duration);
    }
}

function pause() {
    if (state.timer && state.visible) {
        clearTimeout(state.timer);
        state.timer = null;
        const elapsed = Date.now() - state.startTime;
        state.remainingTime = Math.max(1000, state.remainingTime - elapsed);
    }
}

function resume() {
    if (!state.timer && state.visible && state.remainingTime > 0) {
        state.startTime = Date.now();
        state.timer = setTimeout(() => {
            state.visible = false;
        }, state.remainingTime);
    }
}

function success(value, options) {
    show('success', value, options);
}

function error(value, options) {
    show('error', value, options);
}

function warning(value, options) {
    show('warning', value, options);
}

function info(value, options) {
    show('info', value, options);
}

function dismiss() {
    if (state.timer) clearTimeout(state.timer);
    state.timer = null;
    state.visible = false;
}

export function useToast() {
    return {
        toastState: state,
        show,
        success,
        error,
        warning,
        info,
        pause,
        resume,
        dismiss,
    };
}