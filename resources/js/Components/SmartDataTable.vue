<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppButton from './AppButton.vue';
import AppEmptyState from './AppEmptyState.vue';
import AppIcon from './AppIcon.vue';
import AppInput from './AppInput.vue';
import AppSelect from './AppSelect.vue';

const props = defineProps({
    rows: { type: Array, default: () => [] },
    columns: { type: Array, required: true },
    pagination: { type: Object, required: true },
    url: { type: String, required: true },
    search: { type: String, default: '' },
    searchPlaceholder: { type: String, default: 'Cari data...' },
    searchLabel: { type: String, default: 'Pencarian' },
    perPageOptions: { type: Array, default: () => [15, 30, 50, 100] },
    perPage: { type: [Number, String], default: 15 },
    sort: { type: String, default: '' },
    direction: { type: String, default: 'asc' },
    emptyTitle: { type: String, default: 'Belum ada data' },
    emptyDescription: { type: String, default: 'Belum ada data untuk ditampilkan.' },
});

const query = ref(props.search);
let timer;
const processing = computed(() => router.processing);
const currentPage = computed(() => Number(props.pagination.current_page || 1));
const lastPage = computed(() => Number(props.pagination.last_page || 1));
const pages = computed(() => {
    const start = Math.max(1, Math.min(currentPage.value - 2, lastPage.value - 4));
    const end = Math.min(lastPage.value, start + 4);
    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
});
const perPageOptions = computed(() => props.perPageOptions.map((value) => ({ value: Number(value), label: `${value} data` })));

watch(() => props.search, (value) => { query.value = value; });

function visit(parameters = {}) {
    router.get(props.url, {
        search: query.value || undefined,
        per_page: Number(props.perPage),
        sort: props.sort || undefined,
        direction: props.direction || undefined,
        ...parameters,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

function scheduleSearch() {
    clearTimeout(timer);
    timer = setTimeout(() => visit({ page: 1 }), 350);
}

function changePerPage(value) {
    visit({ per_page: Number(value), page: 1 });
}
function sortBy(column) {
    if (!column.sortable) return;
    const direction = props.sort === column.key && props.direction === 'asc' ? 'desc' : 'asc';
    visit({ sort: column.key, direction, page: 1 });
}
function goTo(page) { if (page >= 1 && page <= lastPage.value && page !== currentPage.value) visit({ page }); }
function resetSearch() { query.value = ''; visit({ search: undefined, page: 1 }); }

onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-4 border-b border-outline-variant pb-5 lg:flex-row lg:items-center lg:justify-between">
<div class="flex items-center gap-3"><AppSelect id="smart-table-per-page" label="Per halaman" hide-label :model-value="Number(perPage)" :options="perPageOptions" :disabled="processing" @update:model-value="changePerPage"/><slot name="toolbar" /></div>
            <div class="flex-1 lg:max-w-xl"><AppInput v-model="query" :label="searchLabel" hide-label icon="search" :placeholder="searchPlaceholder" @input="scheduleSearch"><template v-if="query" #trailing><button type="button" class="rounded-full p-1 text-outline hover:bg-surface-container-low hover:text-primary" aria-label="Hapus pencarian" @click="resetSearch"><AppIcon name="close" class="text-lg" /></button></template></AppInput></div>
        </div>
        <div class="relative overflow-x-auto">
            <div v-if="processing" class="absolute inset-0 z-10 flex items-start justify-center bg-surface-container-lowest/60 pt-16 text-sm text-primary">Memuat...</div>
            <table class="w-full text-left"><thead class="bg-surface-container-low text-sm"><tr><th v-for="column in columns" :key="column.key" class="px-6 py-4" :class="column.class"><button v-if="column.sortable" type="button" class="inline-flex items-center gap-1 font-bold text-primary" @click="sortBy(column)">{{ column.label }}<AppIcon :name="sort === column.key ? direction === 'asc' ? 'arrow_upward' : 'arrow_downward' : 'unfold_more'" class="text-lg text-outline" /></button><span v-else>{{ column.label }}</span></th><th v-if="$slots.actions" class="px-6 py-4 text-right">Aksi</th></tr></thead><tbody><tr v-for="(row, index) in rows" :key="row.row_id || row.id || index" class="border-t border-outline-variant"><td v-for="column in columns" :key="column.key" class="px-6 py-4" :class="column.class"><slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">{{ row[column.key] ?? '—' }}</slot></td><td v-if="$slots.actions" class="px-6 py-4 text-right"><slot name="actions" :row="row" /></td></tr></tbody></table>
            <div v-if="!rows.length && !processing" class="p-6"><AppEmptyState icon="database" :title="emptyTitle" :description="emptyDescription" /></div>
        </div>
        <div class="flex flex-col gap-4 text-sm text-on-surface-variant sm:flex-row sm:items-center sm:justify-between"><p>Menampilkan {{ pagination.from || 0 }}–{{ pagination.to || 0 }} dari {{ pagination.total || 0 }} data</p><nav v-if="lastPage > 1" class="flex items-center gap-1" aria-label="Pagination"><AppButton variant="ghost" size="compact" :disabled="currentPage === 1 || processing" aria-label="Halaman sebelumnya" @click="goTo(currentPage - 1)"><AppIcon name="chevron_left" /></AppButton><AppButton v-for="page in pages" :key="page" size="compact" :variant="page === currentPage ? 'primary' : 'ghost'" :disabled="processing" @click="goTo(page)">{{ page }}</AppButton><AppButton variant="ghost" size="compact" :disabled="currentPage === lastPage || processing" aria-label="Halaman berikutnya" @click="goTo(currentPage + 1)"><AppIcon name="chevron_right" /></AppButton></nav></div>
    </div>
</template>
