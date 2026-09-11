<script setup>
import { computed, useId } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppIcon from './AppIcon.vue';
import AppInput from './AppInput.vue';
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

const form = useForm({ application_id: '', sub_tenant_code: '' });
const componentId = useId();
const selectId = `quick-assign-${componentId}`;
const subTenantCodeId = `quick-assign-code-${componentId}`;

const selectableApplications = computed(() => {
    const assignedIds = new Set(props.assignedApplicationIds.map((value) => Number(value)));

    return props.availableApplications
        .filter((application) => !assignedIds.has(Number(application.id)))
        .map((application) => ({
            value: application.id,
            label: application.name,
        }));
});

function assign() {
    if (!form.application_id || form.processing) return;

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
        <form class="grid items-end gap-3 sm:grid-cols-2" @submit.prevent="assign">
            <AppSelect
                :id="selectId"
                v-model="form.application_id"
                label="Tambah Aplikasi"
                hide-label
                placeholder="+ Tambah Aplikasi"
                :options="selectableApplications"
                :error="form.errors.application_id"
                :disabled="form.processing"
            />
            <AppInput
                :id="subTenantCodeId"
                v-model="form.sub_tenant_code"
                label="Kode Tenant (opsional)"
                placeholder="mis. sukamaju"
                hint="Kode tenant di dalam aplikasi usaha, bila aplikasi multi-tenant"
                :error="form.errors.sub_tenant_code"
                :disabled="form.processing"
            />
        </form>
        <p v-if="!selectableApplications.length && !availableApplications.length" class="text-xs text-on-surface-variant">
            Daftarkan aplikasi master dulu.
        </p>
    </div>
</template>
