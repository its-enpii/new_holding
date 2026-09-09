<script setup>
import { ref } from 'vue';

const props = defineProps({
    id: { type: String, default: null },
    text: { type: String, default: '' },
    position: { type: String, default: 'top' }, // 'top' | 'bottom' | 'left' | 'right'
});

const isVisible = ref(false);

const positionClasses = {
    top: 'bottom-full left-1/2 -translate-x-1/2 mb-2',
    bottom: 'top-full left-1/2 -translate-x-1/2 mt-2',
    left: 'right-full top-1/2 -translate-y-1/2 mr-2',
    right: 'left-full top-1/2 -translate-y-1/2 ml-2',
};
</script>

<template>
    <div
        class="group relative inline-flex items-center"
        @mouseenter="isVisible = true"
        @mouseleave="isVisible = false"
        @focusin="isVisible = true"
        @focusout="isVisible = false"
    >
        <slot>
            <button
                type="button"
                tabindex="-1"
                class="flex h-4 w-4 cursor-help items-center justify-center rounded-full bg-surface-container text-[10px] font-bold text-outline transition-all duration-150 hover:bg-surface-container-high hover:text-primary active:scale-90 focus:outline-none"
                aria-haspopup="true"
                :aria-label="text"
            >
                ?
            </button>
        </slot>

        <div
            :id="id"
            role="tooltip"
            :class="[
                'absolute z-30 w-56 rounded-lg bg-on-surface px-2.5 py-1.5 text-xs font-normal leading-relaxed text-on-primary shadow-lg pointer-events-none transition-all duration-150 ease-out',
                positionClasses[position] || positionClasses.top,
                isVisible ? 'visible opacity-100 scale-100 translate-y-0' : 'invisible opacity-0 scale-95 translate-y-1'
            ]"
        >
            <slot name="content">
                {{ text }}
            </slot>
        </div>
    </div>
</template>