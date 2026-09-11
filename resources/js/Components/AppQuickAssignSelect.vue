<script setup>
import { computed, ref, useId, watch } from 'vue';
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
const subTenantDisclosureId = `${subTenantCodeId}-disclosure`;
const isSubTenantCodeVisible = ref(Boolean(form.errors.sub_tenant_code));

watch(() => form.errors.sub_tenant_code, (error) => {
    if (error) {
        isSubTenantCodeVisible.value = true;
    }
}, { immediate: true });

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
            isSubTenantCodeVisible.value = false;
        },
    });
}
</script>

<template>
    <div>
        <form class="space-y-2" @submit.prevent="assign">
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
            <button
                :id="subTenantDisclosureId"
                type="button"
                class="inline-flex items-center gap-1 rounded-sm text-xs font-medium text-on-surface-variant transition hover:text-primary focus-visible:ring-2 focus-visible:ring-primary-container/20 focus-visible:outline-none"
                :aria-expanded="isSubTenantCodeVisible"
                :aria-controls="subTenantCodeId"
                :disabled="form.processing"
                @click="isSubTenantCodeVisible = !isSubTenantCodeVisible"
            >
                Pakai kode sub-tenant
                <AppIcon :name="isSubTenantCodeVisible ? 'expand_less' : 'expand_more'" class="text-base" />
            </button>
            <AppInput
                v-if="isSubTenantCodeVisible"
                :id="subTenantCodeId"
                v-model="form.sub_tenant_code"
                label="Kode Tenant"
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
