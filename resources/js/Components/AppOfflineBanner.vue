<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import AppButton from './AppButton.vue';
import AppIcon from './AppIcon.vue';

const isOffline = ref(typeof navigator !== 'undefined' ? !navigator.onLine : false);
const isReconnected = ref(false);
const isChecking = ref(false);
const customMessage = ref('');
let reconnectedTimer = null;

function onOnline() {
    isOffline.value = false;
    customMessage.value = '';
    isReconnected.value = true;
    clearTimeout(reconnectedTimer);
    reconnectedTimer = setTimeout(() => {
        isReconnected.value = false;
    }, 3500);
}

function onOffline() {
    isOffline.value = true;
    isReconnected.value = false;
}

function onNetworkError(event) {
    isOffline.value = true;
    isReconnected.value = false;
    if (event.detail && typeof event.detail.message === 'string') {
        customMessage.value = event.detail.message;
    }
}

async function reconnect() {
    isChecking.value = true;
    try {
        const res = await fetch(`/desktop/sync/status?_t=${Date.now()}`, {
            method: 'GET',
            cache: 'no-store',
        });
        if (res.ok) {
            onOnline();
        } else {
            isOffline.value = true;
        }
    } catch {
        isOffline.value = true;
    } finally {
        isChecking.value = false;
    }
}

onMounted(() => {
    window.addEventListener('online', onOnline);
    window.addEventListener('offline', onOffline);
    window.addEventListener('app:network-error', onNetworkError);
});

onUnmounted(() => {
    window.removeEventListener('online', onOnline);
    window.removeEventListener('offline', onOffline);
    window.removeEventListener('app:network-error', onNetworkError);
    clearTimeout(reconnectedTimer);
});
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 top-0 z-[100] flex justify-center p-3 sm:p-4">
        <Transition
            enter-active-class="transition duration-300 ease-out transform"
            enter-from-class="-translate-y-8 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in transform"
            leave-from-class="translate-y-0 opacity-100 scale-100"
            leave-to-class="-translate-y-8 opacity-0 scale-95"
        >
            <div
                v-if="isOffline"
                class="pointer-events-auto flex max-w-2xl items-center justify-between gap-4 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-3.5 shadow-xl backdrop-blur-md text-amber-900 dark:text-amber-200 sm:px-5"
                role="alert"
                aria-live="assertive"
            >
                <div class="flex items-center gap-3">
                    <AppIcon name="wifi_off" tone="warning" container-size="9" container-shape="pill" class="shrink-0" />
                    <div class="min-w-0">
                        <p class="text-xs font-bold sm:text-sm">Mode Offline — Input Diizinkan</p>
                        <p class="truncate text-[11px] opacity-90 sm:text-xs">
                            {{ customMessage || 'Data tetap dapat dibaca & dicetak dari database lokal. Fitur penambahan/perubahan data dinonaktifkan.' }}
                        </p>
                    </div>
                </div>
                <div class="shrink-0">
                    <AppButton
                        variant="secondary"
                        size="compact"
                        icon="refresh"
                        :loading="isChecking"
                        aria-label="Cek koneksi server"
                        @click="reconnect"
                    >
                        Cek Server
                    </AppButton>
                </div>
            </div>

            <div
                v-else-if="isReconnected"
                class="pointer-events-auto flex max-w-xl items-center gap-3 rounded-2xl border border-secondary/30 bg-secondary-container/95 p-3.5 shadow-xl backdrop-blur-md text-on-secondary-container sm:px-5"
                role="status"
                aria-live="polite"
            >
                <AppIcon name="wifi" tone="success" container-size="9" container-shape="pill" filled class="shrink-0" />
                <div class="min-w-0">
                    <p class="text-xs font-bold sm:text-sm">Koneksi Pulih</p>
                    <p class="truncate text-[11px] opacity-90 sm:text-xs">Anda telah terhubung kembali ke server utama.</p>
                </div>
            </div>
        </Transition>
    </div>
</template>
