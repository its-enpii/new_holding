<script setup>
import { computed, onBeforeUnmount, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '../Composables/useToast';
import AppIcon from './AppIcon.vue';

const page = usePage();
const { toastState, show, dismiss, pause, resume } = useToast();

const toneStyles = computed(() => ({
    success: {
        iconBg: 'bg-secondary-container/50 text-secondary border-secondary/30',
        icon: 'check_circle',
    },
    error: {
        iconBg: 'bg-error-container/50 text-error border-error/30',
        icon: 'error',
    },
    warning: {
        iconBg: 'bg-tertiary-fixed/50 text-tertiary border-tertiary/30',
        icon: 'warning',
    },
    info: {
        iconBg: 'bg-primary-container/30 text-primary border-primary/20',
        icon: 'info',
    },
}[toastState.tone] || {
    iconBg: 'bg-surface-container-high text-on-surface-variant border-outline-variant',
    icon: 'info',
}));

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash || typeof flash !== 'object') return;
        if (flash.success != null) show('success', flash.success);
        else if (flash.error != null) show('error', flash.error);
        else if (flash.warning != null) show('warning', flash.warning);
        else if (flash.info != null) show('info', flash.info);
    },
    { deep: true, immediate: true },
);

onBeforeUnmount(() => {
    dismiss();
});
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed inset-x-0 top-4 z-[100] flex justify-center px-4 sm:justify-end sm:px-6"
            aria-live="polite"
        >
            <Transition name="toast">
                <div
                    v-if="toastState.visible"
                    role="status"
                    class="pointer-events-auto flex max-w-md items-center gap-3.5 rounded-lg border border-outline-variant/60 bg-surface-container-lowest px-4 py-3.5 shadow-xl shadow-surface-container-highest/30 ring-1 ring-black/5 transition-all duration-200 dark:ring-white/10"
                    @mouseenter="pause"
                    @mouseleave="resume"
                >
                    <!-- Left Icon Badge -->
                    <div
                        class="grid size-9 shrink-0 place-items-center rounded-md border"
                        :class="toneStyles.iconBg"
                    >
                        <AppIcon :name="toneStyles.icon" class="text-xl" />
                    </div>

                    <!-- Message Body -->
                    <div class="min-w-0 flex-1">
                        <p
                            v-if="toastState.title"
                            class="mb-0.5 text-xs font-semibold uppercase tracking-wider text-on-surface-variant"
                        >
                            {{ toastState.title }}
                        </p>
                        <p class="text-sm font-semibold leading-snug text-on-surface">
                            {{ toastState.message }}
                        </p>
                    </div>

                    <!-- Close Button -->
                    <button
                        type="button"
                        class="grid size-8 shrink-0 place-items-center rounded-full text-on-surface-variant/70 transition-colors hover:bg-surface-container-high hover:text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20"
                        aria-label="Tutup notifikasi"
                        @click="dismiss"
                    >
                        <AppIcon name="close" class="text-lg" />
                    </button>
                </div>
            </Transition>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: opacity 220ms ease, transform 220ms cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-0.75rem) scale(0.96);
}
@media (prefers-reduced-motion: reduce) {
    .toast-enter-active,
    .toast-leave-active {
        transition: none;
    }
}
</style>
