<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    data: { type: Array, required: true },
});

const money = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
const compact = new Intl.NumberFormat('id-ID', {
    notation: 'compact',
    compactDisplay: 'short',
    maximumFractionDigits: 1,
});

const root = ref(null);
const hover = ref(null);

const W = 720;
const H = 260;
const pad = { top: 12, right: 12, bottom: 32, left: 56 };
const plotW = W - pad.left - pad.right;
const plotH = H - pad.top - pad.bottom;

const yMax = computed(() => {
    let max = 0;
    for (const row of props.data) {
        max = Math.max(max, Number(row.disbursed || 0), Number(row.collected || 0));
    }
    return niceCeiling(max || 1);
});

const ticks = computed(() => {
    const max = yMax.value;
    return Array.from({ length: 5 }, (_, i) => (max * i) / 4);
});

const groups = computed(() => {
    const n = Math.max(props.data.length, 1);
    const band = plotW / n;
    const gap = Math.min(24, band * 0.28);
    const inner = Math.max(band - gap, 16);
    const barW = Math.min(22, (inner - 2) / 2);

    return props.data.map((row, i) => {
        const cx = pad.left + i * band + band / 2;
        const d = Number(row.disbursed || 0);
        const c = Number(row.collected || 0);
        const hD = (d / yMax.value) * plotH;
        const hC = (c / yMax.value) * plotH;
        return {
            key: row.key,
            label: row.label,
            disbursed: d,
            collected: c,
            pathD: columnPath(cx - barW - 1, pad.top + plotH, barW, hD),
            pathC: columnPath(cx + 1, pad.top + plotH, barW, hC),
            hitX: pad.left + i * band,
            hitW: band,
        };
    });
});

/** Top-rounded column, square on baseline. */
function columnPath(x, baseline, width, height) {
    if (height <= 0) return '';
    const r = Math.min(4, width / 2, height);
    const y = baseline - height;
    return [
        `M ${x} ${baseline}`,
        `L ${x} ${y + r}`,
        `Q ${x} ${y} ${x + r} ${y}`,
        `L ${x + width - r} ${y}`,
        `Q ${x + width} ${y} ${x + width} ${y + r}`,
        `L ${x + width} ${baseline}`,
        'Z',
    ].join(' ');
}

function niceCeiling(value) {
    if (value <= 0) return 1;
    const exp = 10 ** Math.floor(Math.log10(value));
    const n = value / exp;
    const nice = n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10;
    return nice * exp;
}

function y(v) {
    return pad.top + plotH - (Number(v) / yMax.value) * plotH;
}

function formatMoney(v) {
    return money.format(Math.round(Number(v || 0)));
}

function onMove(event, group) {
    const box = root.value?.getBoundingClientRect();
    if (!box) return;
    hover.value = {
        ...group,
        left: event.clientX - box.left,
        top: event.clientY - box.top,
    };
}

function onLeave() {
    hover.value = null;
}

function onFocus(group) {
    const box = root.value?.getBoundingClientRect();
    if (!box) {
        hover.value = group;
        return;
    }
    const scale = box.width / W;
    hover.value = {
        ...group,
        left: (group.hitX + group.hitW / 2) * scale,
        top: (pad.top + 28) * scale,
    };
}

function tooltipStyle(h) {
    const box = root.value?.getBoundingClientRect();
    const w = box?.width || 1;
    const left = Math.min(Math.max(h.left ?? w / 2, 80), w - 80);
    return {
        left: `${left}px`,
        top: `${Math.max((h.top ?? 40) - 12, 8)}px`,
        transform: 'translate(-50%, -100%)',
    };
}
</script>

<template>
    <div ref="root" class="relative min-h-[14rem] w-full">
        <svg
            :viewBox="`0 0 ${W} ${H}`"
            class="h-full min-h-[14rem] w-full select-none"
            role="img"
            aria-label="Tren pencairan dan penerimaan angsuran 6 bulan"
        >
            <g>
                <line
                    v-for="t in ticks"
                    :key="`g-${t}`"
                    :x1="pad.left"
                    :x2="W - pad.right"
                    :y1="y(t)"
                    :y2="y(t)"
                    stroke="var(--color-outline-variant)"
                    stroke-width="1"
                    :opacity="t === 0 ? 1 : 0.5"
                />
                <text
                    v-for="t in ticks"
                    :key="`t-${t}`"
                    :x="pad.left - 8"
                    :y="y(t)"
                    text-anchor="end"
                    dominant-baseline="middle"
                    fill="var(--color-on-surface-variant)"
                    font-size="11"
                    font-weight="600"
                >
                    {{ compact.format(t) }}
                </text>
            </g>

            <g v-for="g in groups" :key="g.key">
                <rect
                    :x="g.hitX"
                    :y="pad.top"
                    :width="g.hitW"
                    :height="plotH"
                    fill="transparent"
                    class="cursor-pointer"
                    tabindex="0"
                    @pointermove="onMove($event, g)"
                    @pointerleave="onLeave"
                    @focus="onFocus(g)"
                    @blur="onLeave"
                />
                <path
                    v-if="g.pathD"
                    :d="g.pathD"
                    fill="var(--color-primary)"
                    class="transition-opacity"
                    :opacity="hover && hover.key !== g.key ? 0.35 : 1"
                    pointer-events="none"
                />
                <path
                    v-if="g.pathC"
                    :d="g.pathC"
                    fill="var(--color-secondary)"
                    class="transition-opacity"
                    :opacity="hover && hover.key !== g.key ? 0.35 : 1"
                    pointer-events="none"
                />
                <text
                    :x="g.hitX + g.hitW / 2"
                    :y="H - 10"
                    text-anchor="middle"
                    fill="var(--color-on-surface-variant)"
                    font-size="11"
                    font-weight="600"
                >
                    {{ g.label }}
                </text>
            </g>
        </svg>

        <div
            v-if="hover"
            class="pointer-events-none absolute z-10 min-w-[10.5rem] rounded-lg border border-outline-variant bg-surface-container-lowest px-3 py-2 shadow-md"
            :style="tooltipStyle(hover)"
            role="tooltip"
        >
            <p class="mb-1.5 text-xs font-bold text-primary">{{ hover.label }}</p>
            <div class="space-y-1 text-xs">
                <p class="flex items-center justify-between gap-4">
                    <span class="inline-flex items-center gap-1.5 text-on-surface-variant">
                        <span class="size-2 rounded-sm bg-primary" aria-hidden="true" />Cair
                    </span>
                    <span class="font-semibold tabular-nums text-on-surface">{{ formatMoney(hover.disbursed) }}</span>
                </p>
                <p class="flex items-center justify-between gap-4">
                    <span class="inline-flex items-center gap-1.5 text-on-surface-variant">
                        <span class="size-2 rounded-sm bg-secondary" aria-hidden="true" />Terima
                    </span>
                    <span class="font-semibold tabular-nums text-on-surface">{{ formatMoney(hover.collected) }}</span>
                </p>
            </div>
        </div>

        <table class="sr-only">
            <caption>Tren 6 bulan pencairan vs penerimaan</caption>
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Cair</th>
                    <th>Terima</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in data" :key="`t-${row.key}`">
                    <td>{{ row.label }}</td>
                    <td>{{ formatMoney(row.disbursed) }}</td>
                    <td>{{ formatMoney(row.collected) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
