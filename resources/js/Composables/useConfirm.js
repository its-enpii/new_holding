import { reactive } from 'vue';

const state = reactive({
    open: false,
    title: 'Konfirmasi',
    message: '',
    confirmLabel: 'Hapus',
    cancelLabel: 'Batal',
    variant: 'danger',
    icon: 'warning',
    _resolve: null,
});

function confirm({ title = 'Konfirmasi', message, confirmLabel = 'Hapus', cancelLabel = 'Batal', variant = 'danger', icon = 'warning' } = {}) {
    return new Promise((resolve) => {
        Object.assign(state, { open: true, title, message, confirmLabel, cancelLabel, variant, icon, _resolve: resolve });
    });
}

function showAlert({ title = 'Pemberitahuan', message, confirmLabel = 'OK', variant = 'primary', icon = 'info' } = {}) {
    return confirm({ title, message, confirmLabel, cancelLabel: '', variant, icon });
}

function handleConfirm() {
    state._resolve?.(true);
    state.open = false;
}

function handleCancel() {
    state._resolve?.(false);
    state.open = false;
}

export function useConfirm() {
    return { confirmState: state, confirm, showAlert, handleConfirm, handleCancel };
}
