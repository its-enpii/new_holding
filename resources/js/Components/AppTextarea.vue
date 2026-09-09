<script setup>
defineOptions({ inheritAttrs: false });

import { useId } from 'vue';
import AppIcon from './AppIcon.vue';

const model = defineModel({ type: String, default: '' });
const props = defineProps({
    id: { type: String, default: null },
    label: { type: String, required: true },
    icon: { type: String, default: null },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    placeholder: { type: String, default: null },
    readonly: { type: Boolean, default: false },
});

const inputId = props.id || useId();
</script>

<template>
    <div class="space-y-2">
        <label :for="inputId" class="ml-1 block text-sm font-bold uppercase tracking-wider text-primary">{{ label }}</label>
        <div class="relative">
            <AppIcon v-if="icon" :name="icon" class="pointer-events-none absolute left-4 top-4 text-xl text-outline" />
            <textarea
                :id="inputId"
                v-model="model"
                :aria-invalid="Boolean(error)"
                :aria-describedby="error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined"
                :readonly="readonly"
                :placeholder="readonly ? undefined : (placeholder ?? `Masukkan ${label.toLowerCase()}`)"
                class="min-h-28 w-full resize-y rounded-xl border bg-surface-container-lowest px-4 py-3 text-primary transition placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/10 focus:outline-none read-only:cursor-default read-only:bg-surface-container-low read-only:text-on-surface-variant"
                :class="[icon && 'pl-12', error ? 'border-error' : 'border-outline-variant']"
                v-bind="$attrs"
            />
        </div>
        <p v-if="error" :id="`${inputId}-error`" class="ml-1 text-sm text-error">{{ error }}</p>
        <p v-else-if="hint" :id="`${inputId}-hint`" class="ml-1 text-sm text-on-surface-variant">{{ hint }}</p>
    </div>
</template>
