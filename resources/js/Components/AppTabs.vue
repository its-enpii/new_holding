<script setup>
import { computed } from 'vue';
import AppIcon from './AppIcon.vue';

/**
 * Tab navigation dengan 3 varian layout:
 * - 'underline' (default): tab border-b-2 ala Material 3 navigation bar (top tabs)
 * - 'pill': tab dalam container rounded-xl (sidebar nav, segmented control)
 * - 'pills-bar': tab di dalam rounded container dengan pill aktif (gateway/payment tab)
 *
 * Item shape: { key, label, icon?, badge?, disabled? }
 * `icon` = string nama Material icon
 * `badge` = number/string di sebelah kanan label (mis. counter)
 * `disabled` = disable tab tertentu
 */
const props = defineProps({
    items: { type: Array, required: true },
    modelValue: { type: [String, Number], required: true },
    variant: {
        type: String,
        default: 'underline',
        validator: (value) => ['underline', 'pill', 'pills', 'pills-bar', 'pill-bar'].includes(value),
    },
    vertical: { type: Boolean, default: false },
    ariaLabel: { type: String, default: 'Tab' },
});

defineEmits(['update:modelValue']);

const normalizedVariant = computed(() => {
    if (props.vertical || props.variant === 'pill' || props.variant === 'pills') return 'pill';
    if (props.variant === 'pills-bar' || props.variant === 'pill-bar') return 'pills-bar';
    return 'underline';
});

const wrapperClass = {
    underline: 'flex flex-wrap gap-x-6 gap-y-1',
    pill: 'flex flex-col gap-1 w-full',
    'pills-bar': 'flex flex-wrap gap-1 rounded-xl border border-outline-variant bg-surface-container-lowest p-1',
};

const tabBase = {
    underline: 'flex items-center gap-2 border-b-2 px-1 pb-3 pt-2 text-sm font-medium transition-all duration-150 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-surface-container-lowest',
    pill: 'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-all duration-150 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-surface-container-low',
    'pills-bar': 'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-all duration-150 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-surface-container-lowest',
};

const tabActive = {
    underline: 'border-primary text-primary font-bold',
    pill: 'bg-primary-container font-bold text-on-primary-container shadow-xs',
    'pills-bar': 'bg-primary text-on-primary shadow-sm',
};

const tabInactive = {
    underline: 'border-transparent text-on-surface-variant hover:border-outline hover:text-on-surface',
    pill: 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface',
    'pills-bar': 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary',
};

function tabClass(item) {
    const v = normalizedVariant.value;
    if (props.modelValue === item.key) {
        return [tabBase[v], tabActive[v]];
    }
    return [tabBase[v], tabInactive[v]];
}
</script>

<template>
    <nav :class="wrapperClass[normalizedVariant]" :aria-label="ariaLabel">
        <button
            v-for="item in items"
            :key="item.key"
            type="button"
            role="tab"
            :aria-selected="modelValue === item.key"
            :aria-current="modelValue === item.key ? 'page' : undefined"
            :disabled="item.disabled"
            :class="[
                ...tabClass(item),
                item.disabled && 'cursor-not-allowed opacity-50 hover:bg-transparent active:scale-100',
            ]"
            @click="!item.disabled && $emit('update:modelValue', item.key)"
        >
            <AppIcon v-if="item.icon" :name="item.icon" class="shrink-0 text-lg" />
            <span class="truncate">{{ item.label }}</span>
            <span
                v-if="item.badge !== undefined && item.badge !== null"
                class="ml-auto inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-error/15 px-1.5 text-[10px] font-bold leading-4 text-error"
            >{{ item.badge }}</span>
        </button>
    </nav>
</template>