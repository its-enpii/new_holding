<script setup>
import { computed } from 'vue';
import { useId } from 'vue';

const props = defineProps({
    /**
     * Use as boolean (v-model="bool") or array (v-model="array" + :value="...").
     * Component auto-detects which mode based on model type.
     */
    modelValue: { type: [Boolean, Array], default: false },
    value: { type: [String, Number, Boolean], default: undefined },
    label: { type: String, default: null },
    description: { type: String, default: null },
    icon: { type: String, default: null },
    indeterminate: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    /**
     * 'cell' (default) — bare checkbox + optional label next to it (table cells, lists).
     * 'inline' — slightly larger, sits next to inline text (login "remember me").
     * 'field' — full form-field with uppercase label above (parity with AppInput/AppSwitch field mode).
     */
    variant: {
        type: String,
        default: 'cell',
        validator: (value) => ['cell', 'inline', 'field'].includes(value),
    },
});

const emit = defineEmits(['update:modelValue']);

const uid = useId();
const id = computed(() => `cb-${uid}`);

const isChecked = computed(() => {
    if (Array.isArray(props.modelValue)) {
        return props.modelValue.includes(props.value);
    }
    return Boolean(props.modelValue);
});

function onChange(event) {
    const checked = event.target.checked;
    if (Array.isArray(props.modelValue)) {
        const next = [...props.modelValue];
        const idx = next.indexOf(props.value);
        if (checked && idx === -1) next.push(props.value);
        else if (!checked && idx !== -1) next.splice(idx, 1);
        emit('update:modelValue', next);
    } else {
        emit('update:modelValue', checked);
    }
}
</script>

<template>
    <!-- field variant: parity with AppInput / AppSwitch field mode -->
    <div v-if="variant === 'field'" class="min-w-0 space-y-2">
        <label :for="id" class="ml-1 block text-sm font-bold uppercase tracking-wider text-primary">
            {{ label }}<span v-if="description" class="ml-2 text-xs font-normal text-on-surface-variant">— {{ description }}</span>
        </label>
        <label
            :for="id"
            class="flex h-14 w-full cursor-pointer items-center gap-3 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 transition-all duration-150 active:scale-[0.99] focus-within:border-primary-container focus-within:ring-2 focus-within:ring-primary-container/10"
            :class="disabled && 'cursor-not-allowed opacity-60 active:scale-100'"
        >
            <input
                :id="id"
                type="checkbox"
                role="checkbox"
                class="size-5 shrink-0 rounded border-outline-variant accent-primary transition-transform duration-150 active:scale-90 focus:ring-primary"
                :checked="isChecked"
                :indeterminate.prop="indeterminate"
                :disabled="disabled"
                @change="onChange"
            />
            <span class="text-sm font-medium text-primary">
                <slot name="label">{{ label }}</slot>
            </span>
        </label>
    </div>

    <!-- inline variant: compact, sits next to text -->
    <label
        v-else-if="variant === 'inline'"
        :for="id"
        class="inline-flex cursor-pointer items-center gap-2 font-medium text-primary select-none transition-transform duration-150 active:scale-95"
        :class="disabled && 'cursor-not-allowed opacity-60 active:scale-100'"
    >
        <input
            :id="id"
            type="checkbox"
            role="checkbox"
            class="size-4 rounded border-outline-variant accent-primary transition-transform duration-150 active:scale-90 focus:ring-primary"
            :checked="isChecked"
            :indeterminate.prop="indeterminate"
            :disabled="disabled"
            @change="onChange"
        />
        <slot name="label">{{ label }}</slot>
    </label>

    <!-- cell variant: bare checkbox -->
    <input
        v-else
        :id="id"
        type="checkbox"
        role="checkbox"
        class="size-4 shrink-0 rounded border-outline-variant accent-primary transition-transform duration-150 active:scale-90 focus:ring-primary disabled:cursor-not-allowed disabled:opacity-60"
        :checked="isChecked"
        :indeterminate.prop="indeterminate"
        :disabled="disabled"
        :aria-label="label || undefined"
        @change="onChange"
    />
</template>