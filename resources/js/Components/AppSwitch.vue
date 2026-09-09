<script setup>
import { useId } from 'vue';
import AppIcon from './AppIcon.vue';

const model = defineModel({ type: Boolean, default: false });
const props = defineProps({
    id: { type: String, default: null },
    label: { type: String, default: '' },
    description: { type: String, default: null },
    icon: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    /** When true, renders only the switch toggle pill without outer container border/padding. */
    bare: { type: Boolean, default: false },
    /** When true, matches AppInput: uppercase label above + h-14 control row. */
    field: { type: Boolean, default: false },
});

const switchId = props.id || useId();
</script>

<template>
    <!-- Bare switch only (no container / inline within custom layout) -->
    <label
        v-if="bare || (!label && !description && !icon && !field)"
        :for="switchId"
        class="relative inline-flex shrink-0 cursor-pointer items-center"
        :class="disabled && 'cursor-not-allowed opacity-60'"
    >
        <input :id="switchId" v-model="model" type="checkbox" role="switch" class="peer sr-only" :disabled="disabled">
        <span class="h-7 w-12 rounded-full bg-outline-variant transition-colors duration-200 ease-out peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary-container peer-focus-visible:ring-offset-2" />
        <span class="pointer-events-none absolute left-1 top-1 size-5 rounded-full bg-surface-container-lowest shadow-sm transition-all duration-200 cubic-bezier(0.4, 0, 0.2, 1) peer-checked:translate-x-5 peer-active:w-6" />
    </label>

    <!-- Form-field layout (grid-aligned with AppInput / SmartSelect) -->
    <div v-else-if="field" class="min-w-0 space-y-2">
        <label v-if="label" :for="switchId" class="ml-1 block text-sm font-bold uppercase tracking-wider text-primary">{{ label }}</label>
        <label
            :for="switchId"
            class="flex h-14 w-full cursor-pointer items-center justify-between gap-4 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 transition-all duration-150 active:scale-[0.99] focus-within:border-primary-container focus-within:ring-2 focus-within:ring-primary-container/10"
            :class="disabled && 'cursor-not-allowed opacity-60 active:scale-100'"
        >
            <span class="flex min-w-0 items-center gap-3">
                <AppIcon v-if="icon" :name="icon" class="shrink-0 text-xl text-outline" />
                <span class="truncate text-sm font-medium text-primary">{{ model ? (description || 'Aktif') : (description || 'Nonaktif') }}</span>
            </span>
            <span class="relative inline-flex shrink-0">
                <input :id="switchId" v-model="model" type="checkbox" role="switch" class="peer sr-only" :disabled="disabled">
                <span class="h-7 w-12 rounded-full bg-outline-variant transition-colors duration-200 ease-out peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary-container peer-focus-visible:ring-offset-2" />
                <span class="pointer-events-none absolute left-1 top-1 size-5 rounded-full bg-surface-container-lowest shadow-sm transition-all duration-200 cubic-bezier(0.4, 0, 0.2, 1) peer-checked:translate-x-5 peer-active:w-6" />
            </span>
        </label>
    </div>

    <!-- Compact inline (settings lists, etc.) -->
    <label
        v-else
        :for="switchId"
        class="flex min-h-14 cursor-pointer items-center justify-between gap-4 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 transition-all duration-150 active:scale-[0.99] focus-within:border-primary-container focus-within:ring-2 focus-within:ring-primary-container/10"
        :class="disabled && 'cursor-not-allowed opacity-60 active:scale-100'"
    >
        <span class="flex items-center gap-3">
            <AppIcon v-if="icon" :name="icon" class="text-xl text-outline" />
            <span>
                <span v-if="label" class="block text-sm font-semibold text-primary">{{ label }}</span>
                <span v-if="description" class="mt-0.5 block text-xs text-on-surface-variant">{{ description }}</span>
            </span>
        </span>
        <span class="relative inline-flex shrink-0">
            <input :id="switchId" v-model="model" type="checkbox" role="switch" class="peer sr-only" :disabled="disabled">
            <span class="h-7 w-12 rounded-full bg-outline-variant transition-colors duration-200 ease-out peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary-container peer-focus-visible:ring-offset-2" />
            <span class="pointer-events-none absolute left-1 top-1 size-5 rounded-full bg-surface-container-lowest shadow-sm transition-all duration-200 cubic-bezier(0.4, 0, 0.2, 1) peer-checked:translate-x-5 peer-active:w-6" />
        </span>
    </label>
</template>