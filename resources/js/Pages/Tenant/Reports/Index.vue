<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppBadge from '../../../Components/AppBadge.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppCheckbox from '../../../Components/AppCheckbox.vue';
import AppEmptyState from '../../../Components/AppEmptyState.vue';
import AppSelect from '../../../Components/AppSelect.vue';
import ReportComparativeTable from '../../../Components/ReportComparativeTable.vue';

const props = defineProps({
    tenantApplications: { type: Array, default: () => [] },
    reportTypes: { type: Object, required: true },
    years: { type: Array, required: true },
    filters: { type: Object, required: true },
    report: { type: Object, default: null },
});

const form = ref({ ...props.filters, apps: [...props.filters.apps] });
const selectedApplications = computed(() => props.tenantApplications.filter((application) => form.value.apps.includes(application.id)));
const typeOptions = computed(() => Object.entries(props.reportTypes).map(([value, label]) => ({ value, label })));
const yearOptions = computed(() => props.years.map((year) => ({ value: year, label: String(year) })));
const monthOptions = computed(() => [
    { value: '', label: 'Tahunan' },
    ...Array.from({ length: 12 }, (_, index) => ({ value: index + 1, label: new Date(2024, index).toLocaleString('id-ID', { month: 'long' }) })),
]);
const hasSelection = computed(() => form.value.apps.length > 0);

function submit(force = false) {
    router.get(route('tenant.reports.show'), {
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
        <Head title="Laporan" />
        <AppCard class="mb-6">
            <template #header>
                <div>
                    <h1 class="text-xl font-semibold text-on-surface">Laporan Konsolidasi</h1>
                    <p class="text-sm text-on-surface-variant">Bandingkan laporan keuangan antar aplikasi subsidiary.</p>
                </div>
                <div class="flex gap-2">
                    <AppButton variant="secondary" icon="download" :disabled="!hasSelection || !report" :href="route('tenant.reports.export.csv', { apps: form.apps, type: form.type, year: form.year, month: form.month || null })">CSV</AppButton>
                    <AppButton variant="secondary" icon="picture_as_pdf" :disabled="!hasSelection || !report" :href="route('tenant.reports.export.pdf', { apps: form.apps, type: form.type, year: form.year, month: form.month || null })">PDF</AppButton>
                </div>
            </template>
            <div class="grid gap-4 md:grid-cols-4">
                <AppSelect v-model="form.type" label="Jenis Laporan" :options="typeOptions" />
                <AppSelect v-model="form.year" label="Tahun" :options="yearOptions" />
                <AppSelect v-model="form.month" label="Periode" :options="monthOptions" />
                <div class="flex items-end gap-2">
                    <AppButton icon="search" :disabled="!hasSelection" @click="submit(false)">Tampilkan</AppButton>
                    <AppButton variant="outline" icon="refresh" :disabled="!hasSelection" @click="submit(true)">Muat Ulang</AppButton>
                </div>
            </div>
            <div class="mt-6 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                <label
                    v-for="application in tenantApplications"
                    :key="application.id"
                    class="flex items-center gap-3 rounded-md border p-3"
                    :class="application.has_financial_report && application.is_active && !application.is_expired ? 'border-outline-variant bg-surface-container-lowest' : 'border-outline-variant/50 bg-surface-container-low opacity-60'"
                >
                    <AppCheckbox v-model="form.apps" :value="application.id" :label="application.label" :disabled="!(application.has_financial_report && application.is_active && !application.is_expired)" variant="cell" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-on-surface">{{ application.label }}</p>
                        <p class="truncate text-xs text-on-surface-variant">{{ application.application_name }}</p>
                    </div>
                    <AppBadge v-if="!application.is_active" tone="error" class="ml-auto">Nonaktif</AppBadge>
                    <AppBadge v-else-if="application.is_expired" tone="warning" class="ml-auto">Kadaluarsa</AppBadge>
                    <AppBadge v-else-if="!application.has_financial_report" tone="outline" class="ml-auto">Non-finansial</AppBadge>
                </label>
            </div>
        </AppCard>
        <ReportComparativeTable v-if="report && selectedApplications.length" :report="report" :applications="selectedApplications" :report-type="form.type" />
        <AppEmptyState v-else icon="monitoring" title="Belum ada laporan" description="Pilih minimal satu aplikasi aktif, lalu klik Tampilkan." />
    </AdminLayout>
</template>
