<script setup>
import { computed, useAttrs } from 'vue';
import AppIcon from './AppIcon.vue';
import AppTooltip from './AppTooltip.vue';

const attrs = useAttrs();

const props = defineProps({
    name: { type: String, required: true },
    tone: {
        type: String,
        default: 'neutral',
        validator: (value) => ['neutral', 'success', 'warning', 'danger', 'error', 'info', 'primary', 'secondary', 'tertiary'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    rounded: {
        type: String,
        default: 'lg',
        validator: (value) => ['square', 'lg', 'full'].includes(value),
    },
    filled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    type: { type: String, default: 'button' },
    ariaLabel: { type: String, default: null },
    tooltip: { type: String, default: null },
});

const sizeClasses = {
    sm: 'size-8',
    md: 'size-10',
    lg: 'size-12',
};

const roundedMap = {
    square: 'rounded',
    lg: 'rounded-lg',
    full: 'rounded-full',
};

const toneClasses = {
    neutral: 'bg-transparent text-on-surface-variant hover:bg-surface-container-low hover:text-primary',
    primary: 'bg-primary-container text-on-primary-container hover:bg-primary-container/80',
    secondary: 'bg-secondary-container text-on-secondary-container hover:bg-secondary-container/80',
    success: 'bg-secondary-container text-on-secondary-container hover:bg-secondary-container/80',
    warning: 'bg-tertiary-fixed text-tertiary hover:bg-tertiary-fixed/80',
    danger: 'bg-error-container text-on-error-container hover:bg-error-container/80',
    error: 'bg-error-container text-on-error-container hover:bg-error-container/80',
    info: 'bg-primary-container text-on-primary-container hover:bg-primary-container/80',
    tertiary: 'bg-tertiary-fixed text-tertiary hover:bg-tertiary-fixed/80',
};

const toneClassesFilled = {
    neutral: 'bg-surface-container-low text-on-surface hover:bg-surface-container',
    primary: 'bg-primary text-on-primary hover:brightness-110',
    secondary: 'bg-secondary text-on-secondary hover:brightness-110',
    success: 'bg-secondary text-on-secondary hover:brightness-110',
    warning: 'bg-tertiary text-on-tertiary hover:brightness-110',
    danger: 'bg-error text-on-error hover:brightness-90',
    error: 'bg-error text-on-error hover:brightness-90',
    info: 'bg-primary text-on-primary hover:brightness-110',
    tertiary: 'bg-tertiary text-on-tertiary hover:brightness-110',
};

const buttonClass = computed(() => [
    'inline-flex shrink-0 items-center justify-center transition-all duration-150 active:scale-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-container focus-visible:ring-offset-2 focus-visible:ring-offset-surface-container-lowest',
    sizeClasses[props.size],
    roundedMap[props.rounded],
    (props.filled ? toneClassesFilled[props.tone] : toneClasses[props.tone]) || toneClasses.neutral,
    (props.disabled || props.loading) && 'cursor-not-allowed opacity-60 active:scale-100',
]);

const iconSize = computed(() => {
    if (props.size === 'sm') return 'text-lg';
    if (props.size === 'lg') return 'text-2xl';
    return 'text-xl';
});

const element = computed(() => (attrs.href || attrs.to ? 'a' : 'button'));
</script>

<template>
    <AppTooltip v-if="tooltip" :text="tooltip">
        <component
            :is="element"
            :type="type"
            :class="buttonClass"
            :disabled="disabled || loading"
            :aria-label="ariaLabel || name"
            :aria-busy="loading || undefined"
        >
            <AppIcon v-if="!loading" :name="name" :class="iconSize" />
            <span v-else class="material-symbols-outlined animate-spin" :class="iconSize">progress_activity</span>
        </component>
    </AppTooltip>
    <component
        :is="element"
        v-else
        :type="type"
        :class="buttonClass"
        :disabled="disabled || loading"
        :aria-label="ariaLabel || name"
        :aria-busy="loading || undefined"
    >
        <AppIcon v-if="!loading" :name="name" :class="iconSize" />
        <span v-else class="material-symbols-outlined animate-spin" :class="iconSize">progress_activity</span>
    </component>
</template>