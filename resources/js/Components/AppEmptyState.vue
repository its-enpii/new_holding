<script setup>
import AppIcon from './AppIcon.vue';

defineProps({
    icon: { type: String, required: true },
    title: { type: String, required: true },
    description: { type: String, default: null },
});
</script>

<template>
    <div class="grid min-h-40 place-items-center rounded-xl bg-surface-container-low p-6 text-center text-on-surface-variant">
        <div>
            <AppIcon :name="icon" class="text-4xl" />
            <p class="mt-2 font-semibold">{{ title }}</p>
            <p v-if="description" class="mt-1 text-sm">{{ description }}</p>
            <div v-if="$slots.default" class="mt-4"><slot /></div>
        </div>
    </div>
</template>
