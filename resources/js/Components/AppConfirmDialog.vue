<script setup>
import { useConfirm } from '../Composables/useConfirm';
import AppButton from './AppButton.vue';
import AppIcon from './AppIcon.vue';
import AppModal from './AppModal.vue';

const { confirmState, handleConfirm, handleCancel } = useConfirm();
</script>

<template>
    <AppModal :model-value="confirmState.open" :title="confirmState.title" size="sm" @update:model-value="handleCancel">
        <div class="flex flex-col items-center gap-4 text-center">
            <div
                class="grid size-14 place-items-center rounded-full"
                :class="confirmState.variant === 'danger' ? 'bg-error-container text-error' : 'bg-primary-container text-primary'"
            >
                <AppIcon :name="confirmState.icon" class="text-3xl" />
            </div>
            <p class="text-sm text-on-surface-variant">{{ confirmState.message }}</p>
        </div>
        <template #footer>
            <AppButton v-if="confirmState.cancelLabel" variant="ghost" @click="handleCancel">{{ confirmState.cancelLabel }}</AppButton>
            <AppButton :variant="confirmState.variant" @click="handleConfirm">{{ confirmState.confirmLabel }}</AppButton>
        </template>
    </AppModal>
</template>
