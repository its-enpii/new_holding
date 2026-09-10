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
const isIncomeStatement = computed(() => props.reportType === 'income_statement');

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

function valueFor(row, application, column = null) {
    const value = row.values?.[application.id];

    if (value === null || value === undefined) return null;
    if (column === null) return value;

    return value[column] ?? null;
}

function displayValue(row, application, column = null) {
    const value = valueFor(row, application, column);

    return value === null ? '-' : format(value);
}
</script>

<template>
    <div class="overflow-auto rounded-lg border border-outline-variant bg-surface-container-lowest">
        <table class="min-w-full border-collapse text-sm">
            <thead class="sticky top-0 z-10 bg-surface-container-low">
                <tr>
                    <th class="min-w-[280px] border-b border-outline-variant px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-primary">Kode / Nama</th>
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
                        <template v-if="isIncomeStatement">
                            <div class="grid grid-cols-3 gap-2">
                                <span v-for="column in ['prior', 'current', 'ytd']" :key="column">{{ displayValue(row, application, column) }}</span>
                            </div>
                        </template>
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
                        <span v-if="report.totals[application.id]">
                            {{ isIncomeStatement ? format(report.totals[application.id].net_income) : format(report.totals[application.id].assets) }}
                        </span>
                        <span v-else>-</span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</template>
