<script setup>
defineOptions({ inheritAttrs: false });

import { useId } from 'vue';
import AppIcon from './AppIcon.vue';
import AppTooltip from './AppTooltip.vue';

const model = defineModel({ type: [String, Number], default: '' });
const props = defineProps({
    id: { type: String, default: null },
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    icon: { type: String, default: null },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    placeholder: { type: String, default: null },
    readonly: { type: Boolean, default: false },
    hideLabel: { type: Boolean, default: false },
    tooltip: { type: String, default: null },
});

const generatedId = useId();
const inputId = props.id || generatedId;
</script>

<template>
    <div class="space-y-2">
        <div v-if="!hideLabel" class="flex items-center gap-1.5 ml-1">
            <label :for="inputId" class="block text-sm font-bold uppercase tracking-wider text-primary">{{ label }}</label>
            <AppTooltip v-if="tooltip" :id="`${inputId}-tooltip`" :text="tooltip" />
        </div>
        <label v-else :for="inputId" class="sr-only">{{ label }}</label>
        <div class="relative">
            <AppIcon v-if="icon" :name="icon" class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xl text-outline" />
            <input
                :id="inputId"
                v-model="model"
                :type="type"
                :aria-invalid="Boolean(error)"
                :aria-describedby="[
                    error && `${inputId}-error`,
                    hint && `${inputId}-hint`,
                    tooltip && `${inputId}-tooltip`
                ].filter(Boolean).join(' ') || undefined"
                :readonly="readonly"
                :placeholder="readonly ? undefined : (placeholder ?? `Masukkan ${label.toLowerCase()}`)"
                class="h-14 w-full rounded-xl border bg-surface-container-lowest px-4 text-primary transition placeholder:text-outline focus:border-primary-container focus:ring-2 focus:ring-primary-container/10 focus:outline-none read-only:cursor-default read-only:bg-surface-container-low read-only:text-on-surface-variant"
                :class="[icon && 'pl-12', $slots.trailing && 'pr-14', error ? 'border-error' : 'border-outline-variant']"
                v-bind="$attrs"
            >
            <div v-if="$slots.trailing" class="absolute right-2 top-1/2 flex h-10 -translate-y-1/2 items-center justify-center">
                <slot name="trailing" />
            </div>
        </div>
        <p v-if="error" :id="`${inputId}-error`" class="ml-1 text-sm text-error">{{ error }}</p>
        <p v-else-if="hint" :id="`${inputId}-hint`" class="ml-1 text-sm text-on-surface-variant">{{ hint }}</p>
    </div>
</template>
