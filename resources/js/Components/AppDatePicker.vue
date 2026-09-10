<script setup>
defineOptions({ inheritAttrs: false });

import { useId } from 'vue';
import AppIcon from './AppIcon.vue';

const model = defineModel({ type: String, default: '' });
const props = defineProps({
    id: { type: String, default: null },
    label: { type: String, required: true },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    min: { type: String, default: null },
    max: { type: String, default: null },
});

const inputId = props.id || useId();
</script>

<template>
    <div class="space-y-2">
        <label :for="inputId" class="ml-1 block text-sm font-semibold uppercase tracking-wider text-primary">{{ label }}</label>
        <div class="relative">
            <input
                :id="inputId"
                v-model="model"
                type="date"
                :aria-invalid="Boolean(error)"
                :aria-describedby="error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined"
                :disabled="disabled"
                :min="min"
                :max="max"
                class="h-14 w-full rounded-md border border-outline-variant bg-surface-container-lowest px-4 transition focus:border-primary-container focus:ring-2 focus:ring-primary-container/10 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                :class="[model ? 'text-primary' : 'text-on-surface-variant', error && 'border-error']"
                v-bind="$attrs"
            >
            <AppIcon
                name="calendar_month"
                class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xl text-outline"
            />
        </div>
        <p v-if="error" :id="`${inputId}-error`" class="ml-1 text-sm text-error">{{ error }}</p>
        <p v-else-if="hint" :id="`${inputId}-hint`" class="ml-1 text-sm text-on-surface-variant">{{ hint }}</p>
    </div>
</template>
