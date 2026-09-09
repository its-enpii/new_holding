<script setup>
import { computed, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

import AppIcon from './AppIcon.vue';

const attrs = useAttrs();

defineProps({
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'default' },
    icon: { type: String, default: null },
    iconOnly: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    type: { type: String, default: 'button' },
    ariaLabel: { type: String, default: null },
});

const variants = {
    primary: 'bg-gradient-to-r from-primary via-primary-deep to-primary-container text-on-primary shadow-sm hover:opacity-95 disabled:opacity-60',
    success: 'bg-secondary text-on-secondary hover:brightness-110 disabled:bg-secondary/60',
    secondary: 'border border-outline-variant bg-surface-container-lowest text-primary hover:bg-surface-container-low',
    outline: 'border border-primary bg-transparent text-primary hover:bg-primary-container/20 disabled:border-primary/40',
    ghost: 'bg-transparent text-primary hover:bg-surface-container-low',
    danger: 'bg-error text-on-error hover:brightness-90 disabled:bg-error/60',
    tertiary: 'bg-tertiary text-on-tertiary hover:brightness-110 disabled:bg-tertiary/60',
};

const sizes = {
    compact: 'min-h-10 px-3 text-sm',
    default: 'min-h-12 px-5',
    large: 'min-h-14 px-6 text-lg',
};

const element = computed(() => (attrs.href || attrs.to ? 'a' : 'button'));
</script>

<template>
    <component
        :is="element"
        :type="type"
        :disabled="disabled || loading"
        class="inline-flex items-center justify-center gap-2 rounded-md font-medium transition-all duration-150 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-surface-container-lowest disabled:cursor-not-allowed disabled:opacity-60 disabled:active:scale-100"
        :class="[variants[variant] || variants.primary, iconOnly ? 'aspect-square p-0' : sizes[size] || sizes.default]"
        :aria-busy="loading"
        :aria-label="ariaLabel || undefined"
        v-bind="$attrs"
    >
        <span v-if="loading" class="size-5 animate-spin rounded-full border-2 border-current/30 border-t-current" aria-hidden="true" />
        <AppIcon v-else-if="icon" :name="icon" class="text-xl" />
        <slot />
    </component>
</template>
