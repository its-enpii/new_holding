<script setup>
const props = defineProps({
    name: { type: String, required: true },
    filled: { type: Boolean, default: false },
    label: { type: String, default: null },
    /**
     * Semantic color tone. When set (not 'neutral'), the icon is rendered inside
     * a rounded container with MD3 token classes. Useful for stat tiles, status
     * badges, and inline alerts. Backwards-compatible: omit to keep raw icon.
     */
    tone: {
        type: String,
        default: 'neutral',
        validator: (value) => ['neutral', 'success', 'warning', 'danger', 'error', 'info', 'primary', 'secondary', 'tertiary'].includes(value),
    },
    /**
     * Optional container size when tone is set. Maps to Tailwind size-* utility.
     * Default: 9 (36px). Used in stat tiles / status pills.
     */
    containerSize: { type: [Number, String], default: 9 },
    /**
     * Optional container shape. 'rounded' = rounded-lg (default), 'pill' = rounded-full.
     */
    containerShape: {
        type: String,
        default: 'rounded',
        validator: (value) => ['rounded', 'pill'].includes(value),
    },
});

const containerClasses = {
    neutral: 'bg-surface-container-low text-on-surface-variant',
    success: 'bg-secondary-container text-on-secondary-container',
    warning: 'bg-tertiary-fixed text-tertiary',
    danger: 'bg-error-container text-on-error-container',
    error: 'bg-error-container text-on-error-container',
    info: 'bg-primary-container text-on-primary-container',
    primary: 'bg-primary-container text-on-primary-container',
    secondary: 'bg-secondary-container text-on-secondary-container',
    tertiary: 'bg-tertiary-fixed text-tertiary',
};
</script>

<template>
    <!-- Raw icon (tone === 'neutral') — preserves existing call sites -->
    <span
        v-if="tone === 'neutral'"
        class="material-symbols-outlined shrink-0"
        :class="{ 'is-filled': filled }"
        :aria-hidden="label ? undefined : 'true'"
        :aria-label="label || undefined"
        :role="label ? 'img' : undefined"
    >{{ name }}</span>

    <!-- Toned icon: render inside a colored container -->
    <span
        v-else
        class="grid shrink-0 place-items-center"
        :class="[
            `size-${containerSize}`,
            containerShape === 'pill' ? 'rounded-full' : 'rounded-lg',
            containerClasses[tone],
        ]"
        :aria-hidden="label ? undefined : 'true'"
        :aria-label="label || undefined"
        :role="label ? 'img' : undefined"
    >
        <span
            class="material-symbols-outlined"
            :class="{ 'is-filled': filled }"
        >{{ name }}</span>
    </span>
</template>
