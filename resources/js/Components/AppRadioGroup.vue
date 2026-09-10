<script setup>
import { useId } from 'vue';
import AppIcon from './AppIcon.vue';

const model = defineModel({ default: '' });
const props = defineProps({
    id: { type: String, default: null },
    label: { type: String, required: true },
    options: { type: Array, required: true },
    error: { type: String, default: null },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const inputId = props.id || useId();
</script>

<template>
    <fieldset :disabled="disabled" class="space-y-2">
        <legend class="ml-1 text-sm font-bold uppercase tracking-wider text-primary">{{ label }}</legend>
        <div class="grid auto-cols-fr grid-flow-col overflow-hidden rounded-xl border bg-surface-container-lowest" :class="error ? 'border-error' : 'border-outline-variant'">
            <label v-for="(option, index) in options" :key="option.value" class="relative min-w-0" :class="index && 'border-l border-outline-variant'">
                <input
                    :id="`${inputId}-${option.value}`"
                    v-model="model"
                    type="radio"
                    :name="inputId"
                    :value="option.value"
                    :required="required"
                    :aria-invalid="Boolean(error)"
                    :aria-describedby="error ? `${inputId}-error` : undefined"
                    class="peer sr-only"
                >
                <span
                    class="flex h-14 cursor-pointer items-center justify-center gap-2 px-3 text-sm font-semibold transition-colors peer-focus-visible:ring-2 peer-focus-visible:ring-inset peer-focus-visible:ring-primary-container/40 peer-disabled:cursor-not-allowed peer-disabled:opacity-60"
                    :class="model === option.value
                        ? 'bg-primary text-on-primary hover:bg-primary'
                        : 'text-on-surface-variant hover:bg-surface-container-low hover:text-primary'"
                >
                    <AppIcon v-if="option.icon" :name="option.icon" class="text-xl" />
                    {{ option.label }}
                </span>
            </label>
        </div>
        <p v-if="error" :id="`${inputId}-error`" class="ml-1 text-sm text-error">{{ error }}</p>
    </fieldset>
</template>
