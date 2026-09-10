<script setup>
import AppIcon from './AppIcon.vue';

defineOptions({ inheritAttrs: false });

/**
 * Pill/filter chip yang bisa toggle aktif/non-aktif.
 * Pakai untuk: filter status, segment control, document stage filter, dll.
 *
 * Item shape: { value, label, icon?, count?, disabled? }
 * `count` = angka di sebelah label dalam kurung, mis. "Semua (12)"
 * `icon` = nama Material icon di kiri label
 */
const props = defineProps({
    items: { type: Array, required: true },
    modelValue: { type: [String, Number, Boolean], default: null },
    /**
     * Layout:
     * - 'outline' (default): rounded-xl border-outline-variant, aktif border-primary bg-primary
     * - 'solid': rounded-full bg-surface-container-high, aktif bg-primary
     * - 'segment': rounded-lg, aktif bg-primary, non-aktif bg-surface-container-low
     */
    variant: {
        type: String,
        default: 'outline',
        validator: (value) => ['outline', 'solid', 'segment'].includes(value),
    },
    size: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'compact'].includes(value),
    },
    ariaLabel: { type: String, default: 'Filter' },
});

defineEmits(['update:modelValue']);

const wrapperBase = 'flex flex-wrap gap-2';

const alignCls = 'inline-flex items-center justify-center gap-1.5';

const base = {
    outline: 'rounded-xl border transition-all duration-150 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-surface-container-lowest',
    solid: 'rounded-full transition-all duration-150 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-surface-container-lowest',
    segment: 'rounded-lg transition-all duration-150 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-surface-container-lowest',
};

const sizeCls = {
    default: 'px-3 py-2 text-sm font-semibold',
    compact: 'rounded-full px-3 py-1.5 text-xs font-semibold',
};

const activeCls = {
    outline: 'border-primary bg-primary text-on-primary shadow-sm',
    solid: 'bg-primary text-on-primary',
    segment: 'bg-primary text-on-primary shadow-sm',
};

const inactiveCls = {
    outline: 'border-outline-variant bg-surface-container-lowest text-primary hover:border-primary/40',
    solid: 'bg-surface-container-high text-on-surface-variant hover:bg-surface-container',
    segment: 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container',
};

function pillClass(item) {
    const isActive = props.modelValue === item.value;
    return [
        alignCls,
        base[props.variant],
        sizeCls[props.size],
        isActive ? activeCls[props.variant] : inactiveCls[props.variant],
        item.disabled && 'cursor-not-allowed opacity-50 active:scale-100',
    ];
}
</script>

<template>
    <div :class="wrapperBase" role="group" :aria-label="ariaLabel">
        <button
            v-for="item in items"
            :key="item.value"
            type="button"
            :disabled="item.disabled"
            :class="pillClass(item)"
            @click="!item.disabled && $emit('update:modelValue', item.value)"
        >
            <AppIcon v-if="item.icon" :name="item.icon" class="text-base" />
            <span>{{ item.label }}<span v-if="item.count !== undefined && item.count !== null"> ({{ item.count }})</span></span>
        </button>
    </div>
</template>
