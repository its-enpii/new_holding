<script setup>
defineOptions({ inheritAttrs: false });

import { useId } from 'vue';

const model = defineModel({ type: [File, Array], default: null });
const props = defineProps({
    id: { type: String, default: null },
    label: { type: String, required: true },
    accept: { type: String, default: null },
    hint: { type: String, default: null },
    error: { type: String, default: null },
});

const inputId = props.id || useId();

function clearFile() {
    model.value = null;
}

function onChange(event) {
    model.value = event.target.files?.[0] ?? null;
}
</script>

<template>
    <div class="space-y-2">
        <label :for="inputId" class="ml-1 block text-sm font-bold uppercase tracking-wider text-primary">{{ label }}</label>
        <input
            :id="inputId"
            type="file"
            :accept="accept"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined"
            :key="String(model)"
            class="block w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm text-on-surface transition focus:border-primary-container focus:ring-2 focus:ring-primary-container/10 focus:outline-none file:mr-3 file:rounded-full file:border-0 file:bg-primary-container file:px-4 file:py-1.5 file:text-sm file:font-semibold file:text-on-primary-container"
            :class="error && 'border-error'"
            v-bind="$attrs"
            @change="onChange"
        >
        <p v-if="error" :id="`${inputId}-error`" class="ml-1 text-sm text-error">{{ error }}</p>
        <p v-else-if="hint" :id="`${inputId}-hint`" class="ml-1 text-sm text-on-surface-variant">{{ hint }}</p>
        <p v-else-if="model" class="ml-1 flex items-center gap-2 text-sm text-on-surface-variant">
            {{ model.name }}
            <button type="button" class="font-semibold text-primary hover:underline" @click="clearFile">Hapus</button>
        </p>
    </div>
</template>
