<script setup>
import { ref } from 'vue';

defineProps({
    padded: { type: Boolean, default: true },
    bordered: { type: Boolean, default: false },
});

const root = ref(null);
defineExpose({ root });
</script>

<template>
    <section ref="root" class="card-shadow rounded-xl bg-surface-container-lowest" :class="[padded && 'p-6', bordered && 'border border-outline-variant']">
        <header v-if="$slots.header" class="mb-6 flex items-center justify-between gap-4">
            <slot name="header" />
        </header>
        <slot />
    </section>
</template>
