<script setup>
defineOptions({ inheritAttrs: false });

import { useId } from 'vue';
import AppIcon from './AppIcon.vue';

const model = defineModel({ type: [String, Number], default: '' });
const props = defineProps({
    id: { type: String, default: null },
    label: { type: String, required: true },
    options: { type: Array, default: () => [] },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    hideLabel: { type: Boolean, default: false },
});

const selectId = props.id || useId();
</script>

<template>
    <div class="space-y-2">
        <label v-if="!hideLabel" :for="selectId" class="ml-1 block text-sm font-bold uppercase tracking-wider text-primary">{{ label }}</label>
        <label v-else :for="selectId" class="sr-only">{{ label }}</label>
        <div class="relative">
            <select
                :id="selectId"
                v-model="model"
                class="h-14 w-full appearance-none rounded-xl border bg-surface-container-lowest px-4 pr-12 text-primary transition focus:border-primary-container focus:ring-2 focus:ring-primary-container/10 focus:outline-none"
                :class="error ? 'border-error' : 'border-outline-variant'"
                :disabled="disabled"
                :aria-invalid="Boolean(error)"
                :aria-describedby="error ? `${selectId}-error` : hint ? `${selectId}-hint` : undefined"
                v-bind="$attrs"
            >
                <option v-for="option in options" :key="String(option.value)" :value="option.value">{{ option.label }}</option>
            </select>
            <AppIcon name="expand_more" class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xl text-outline" />
        </div>
        <p v-if="error" :id="`${selectId}-error`" class="ml-1 text-sm text-error">{{ error }}</p>
        <p v-else-if="hint" :id="`${selectId}-hint`" class="ml-1 text-sm text-on-surface-variant">{{ hint }}</p>
    </div>
</template>
