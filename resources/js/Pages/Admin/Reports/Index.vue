<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppCheckbox from '../../../Components/AppCheckbox.vue';
import AppEmptyState from '../../../Components/AppEmptyState.vue';
import AppSelect from '../../../Components/AppSelect.vue';
import ReportComparativeTable from '../../../Components/ReportComparativeTable.vue';

const props = defineProps({
    tenants: { type: Array, required: true },
    tenantApplications: { type: Array, required: true },
    reportTypes: { type: Object, required: true },
    years: { type: Array, required: true },
    filters: { type: Object, required: true },
    report: { type: Object, default: null },
});

const form = ref({ ...props.filters, apps: [...props.filters.apps] });
const tenantOptions = computed(() => props.tenants.map((tenant) => ({ value: tenant.id, label: tenant.name })));
const applicationOptions = computed(() => props.tenantApplications);
const selectedApplications = computed(() => props.tenantApplications.filter((application) => form.value.apps.includes(application.id)));
const typeOptions = computed(() => Object.entries(props.reportTypes).map(([value, label]) => ({ value, label })));
const yearOptions = computed(() => props.years.map((year) => ({ value: year, label: String(year) })));
const monthOptions = computed(() => [
    { value: '', label: 'Tahunan' },
    ...Array.from({ length: 12 }, (_, index) => ({ value: index + 1, label: new Date(2024, index).toLocaleString('id-ID', { month: 'long' }) })),
]);

watch(() => form.value.tenant_id, () => {
    form.value.apps = [];
    router.get(route('admin.reports.index'), { tenant_id: form.value.tenant_id }, { preserveState: true });
});

function submit(force = false) {
    router.get(route('admin.reports.show'), {
        tenant_id: form.value.tenant_id,
        apps: form.value.apps,
        type: form.value.type,
        year: form.value.year,
        month: form.value.month || null,
        force,
    }, { preserveState: true });
}
</script>

<template>
    <AdminLayout>
        <Head title="Laporan Tenant" />
        <AppCard class="mb-6">
            <template #header>
                <div>
                    <h1 class="text-xl font-semibold text-on-surface">Preview Laporan Tenant</h1>
                    <p class="text-sm text-on-surface-variant">Tinjau laporan lintas aplikasi pada tenant terpilih.</p>
                </div>
            </template>
            <div class="grid gap-4 md:grid-cols-4">
                <AppSelect v-model="form.tenant_id" label="Tenant" :options="tenantOptions" />
                <AppSelect v-model="form.type" label="Jenis Laporan" :options="typeOptions" />
                <AppSelect v-model="form.year" label="Tahun" :options="yearOptions" />
                <AppSelect v-model="form.month" label="Periode" :options="monthOptions" />
            </div>
            <div class="mt-6 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                <label v-for="application in applicationOptions" :key="application.id" class="flex items-center gap-3 rounded-md border border-outline-variant bg-surface-container-lowest p-3">
                    <AppCheckbox v-model="form.apps" :value="application.id" :label="application.label" :disabled="!(application.has_financial_report && application.is_active && !application.is_expired)" variant="cell" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-on-surface">{{ application.label }}</p>
                        <p class="truncate text-xs text-on-surface-variant">{{ application.application_name }}</p>
                    </div>
                </label>
            </div>
            <div class="mt-6 flex gap-2">
                <AppButton icon="search" :disabled="!form.tenant_id || form.apps.length === 0" @click="submit(false)">Tampilkan</AppButton>
                <AppButton variant="outline" icon="refresh" :disabled="!form.tenant_id || form.apps.length === 0" @click="submit(true)">Muat Ulang</AppButton>
            </div>
        </AppCard>
        <ReportComparativeTable v-if="report && selectedApplications.length" :report="report" :applications="selectedApplications" :report-type="form.type" />
        <AppEmptyState v-else icon="monitoring" title="Preview tidak tersedia" description="Pilih tenant dan minimal satu aplikasi yang memenuhi syarat." />
    </AdminLayout>
</template>
