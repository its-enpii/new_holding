<script setup>
import { computed, useId } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppIcon from './AppIcon.vue';
import AppSelect from './AppSelect.vue';

const props = defineProps({
    tenantId: { type: Number, required: true },
    availableApplications: {
        type: Array,
        default: () => [],
    },
    assignedApplicationIds: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({ application_id: '' });
const componentId = useId();
const selectId = `quick-assign-${componentId}`;

const selectableApplications = computed(() => {
    const assignedIds = new Set(props.assignedApplicationIds.map((value) => Number(value)));

    return props.availableApplications
        .filter((application) => !assignedIds.has(Number(application.id)))
        .map((application) => ({
            value: application.id,
            label: application.name,
        }));
});

function assign(applicationId) {
    if (!applicationId || form.processing) return;

    form.application_id = applicationId;
    form.post(route('admin.tenants.applications.quick-assign', props.tenantId), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}
</script>

<template>
    <div>
        <AppSelect
            v-if="selectableApplications.length"
            :id="selectId"
            :model-value="form.application_id"
            label="Tambah Aplikasi"
            hide-label
            placeholder="+ Tambah Aplikasi"
            :options="selectableApplications"
            :disabled="form.processing"
            @update:model-value="assign"
        />
        <p v-else-if="!availableApplications.length" class="text-xs text-on-surface-variant">
            Daftarkan aplikasi master dulu.
        </p>
    </div>
</template>
