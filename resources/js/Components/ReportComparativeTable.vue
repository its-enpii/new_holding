<script setup>
import { computed } from 'vue';
import AppBadge from './AppBadge.vue';
import { useMoney } from '../Composables/useMoney';

const props = defineProps({
    applications: { type: Array, required: true },
    report: { type: Object, required: true },
    reportType: { type: String, required: true },
});

const { format } = useMoney();
const columns = ['prior', 'current', 'ytd'];

const variant = computed(() => props.report.meta?.variant || null);

const stateLabels = {
    ok: 'OK',
    cache: 'Cache',
    offline: 'Offline',
    auth_error: 'Auth gagal',
};

const stateTones = {
    ok: 'success',
    cache: 'outline',
    offline: 'error',
    auth_error: 'warning',
};

function isNumber(value) {
    return typeof value === 'number' ? Number.isFinite(value) : value !== null && value !== '' && Number.isFinite(Number(value));
}

/**
 * Bentuk nilai menentukan sajian sel: triple kontrak v1 tampil tiga kolom, skalar satu
 * kolom. Deteksi dilakukan per sel (bukan per laporan) agar payload campuran tetap aman.
 */
function isTriple(value) {
    return value !== null && typeof value === 'object';
}

function valueFor(row, application, column = null) {
    const value = row.values?.[application.id];

    if (value === null || value === undefined) return null;
    if (column === null) return value;
    if (!isTriple(value)) return isNumber(value) ? value : null;

    const columnValue = value[column];

    return isNumber(columnValue) ? columnValue : null;
}

function displayValue(row, application, column = null) {
    const value = valueFor(row, application, column);

    return value === null ? '-' : format(value);
}

function normalizedTotal(totals) {
    const afterTax = totals?.laba_rugi_normalized_after_tax;

    if (isNumber(afterTax)) return afterTax;
    if (!afterTax || typeof afterTax !== 'object') return null;

    return isNumber(afterTax.ytd) ? afterTax.ytd : null;
}

/**
 * Total footer: angka warisan sumber (`net_income`/`assets`) lebih dulu, lalu hasil
 * normalisasi laba rugi. Holding tidak pernah mengarang angka yang tidak dikirim sumber.
 */
function footerValue(application) {
    const totals = props.report.totals?.[application.id];

    if (!totals || typeof totals !== 'object') return null;

    const candidates = props.reportType === 'income_statement'
        ? [normalizedTotal(totals), totals.net_income]
        : [totals.net_income, totals.assets];

    return candidates.find(isNumber) ?? null;
}
</script>

<template>
    <div class="overflow-auto rounded-lg border border-outline-variant bg-surface-container-lowest">
        <table class="min-w-full border-collapse text-sm">
            <thead class="sticky top-0 z-10 bg-surface-container-low">
                <tr>
                    <th class="min-w-[280px] border-b border-outline-variant px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-primary">
                        <div class="flex flex-wrap items-center gap-2">
                            <span>Kode / Nama</span>
                            <AppBadge v-if="variant" tone="neutral">{{ variant }}</AppBadge>
                        </div>
                    </th>
                    <th
                        v-for="application in applications"
                        :key="application.id"
                        class="border-b border-outline-variant px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-primary"
                    >
                        <div class="flex flex-col items-end gap-1">
                            <span class="normal-case text-on-surface">{{ application.label }}</span>
                            <AppBadge :tone="stateTones[report.appStates?.[application.id] || 'offline']">
                                {{ stateLabels[report.appStates?.[application.id] || 'offline'] }}
                            </AppBadge>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="row in report.rows"
                    :key="row.key"
                    class="border-b border-outline-variant/60"
                    :class="row.level === 1 ? 'bg-surface-container-low' : row.level === 2 ? 'bg-surface-container-lowest' : 'bg-surface-container-lowest/60'"
                >
                    <td class="px-4 py-2.5" :class="row.level === 1 ? 'font-semibold text-on-surface' : 'text-on-surface-variant'" :style="{ paddingLeft: `${16 * row.level}px` }">
                        <span class="mr-2 text-xs text-outline">{{ row.code }}</span>{{ row.name }}
                    </td>
                    <td
                        v-for="application in applications"
                        :key="application.id"
                        class="px-4 py-2.5 text-right tabular-nums"
                        :class="row.level === 1 ? 'font-semibold text-on-surface' : 'text-on-surface-variant'"
                    >
                        <div v-if="isTriple(valueFor(row, application))" class="grid grid-cols-3 gap-2">
                            <span v-for="column in columns" :key="column">{{ displayValue(row, application, column) }}</span>
                        </div>
                        <template v-else>{{ displayValue(row, application) }}</template>
                    </td>
                </tr>
            </tbody>
            <tfoot v-if="report.totals">
                <tr class="border-t-2 border-primary bg-surface-container-low">
                    <td class="px-4 py-3 text-left font-semibold text-on-surface">Total</td>
                    <td
                        v-for="application in applications"
                        :key="application.id"
                        class="px-4 py-3 text-right font-semibold tabular-nums text-on-surface"
                    >
                        <span v-if="footerValue(application) !== null">{{ format(footerValue(application)) }}</span>
                        <span v-else>-</span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</template>
