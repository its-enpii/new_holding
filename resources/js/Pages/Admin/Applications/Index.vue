<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppBadge from '../../../Components/AppBadge.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppIconButton from '../../../Components/AppIconButton.vue';
import SmartDataTable from '../../../Components/SmartDataTable.vue';
import { useConfirm } from '../../../Composables/useConfirm';

const props = defineProps({ applications: { type: Object, required: true }, filters: { type: Object, required: true } });
const { confirm } = useConfirm();
const deleting = ref(null);

const columns = [
    { key: 'name', label: 'Nama', sortable: true },
    { key: 'base_url', label: 'URL' },
    { key: 'has_financial_report', label: 'Laporan', class: 'w-32' },
    { key: 'is_active', label: 'Status', sortable: true, class: 'w-32' },
];

function destroy(application) {
    confirm({
        title: 'Hapus aplikasi',
        message: `Aplikasi ${application.name} akan dihapus permanen. Lanjutkan?`,
        confirmLabel: 'Hapus',
    }).then((confirmed) => {
        if (! confirmed) return;
        deleting.value = application.id;
        router.delete(route('admin.applications.destroy', application.id), { onFinish: () => { deleting.value = null; } });
    });
}

function toggle(application) {
    router.patch(route('admin.applications.toggle', application.id));
}
</script>

<template>
    <Head title="Applications" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <header>
                    <h1 class="text-3xl font-bold text-primary">Applications</h1>
                    <p class="mt-2 text-on-surface-variant">Registry aplikasi subsidiary.</p>
                </header>
                <AppIconButton name="add" filled aria-label="Tambah aplikasi" :href="route('admin.applications.create')" />
            </div>
            <AppCard :padded="false">
                <div class="p-6">
                    <SmartDataTable
                        :rows="props.applications.data"
                        :columns="columns"
                        :pagination="props.applications"
                        :url="route('admin.applications.index')"
                        :search="props.filters.search"
                        :per-page="props.filters.per_page"
                        :sort="props.filters.sort"
                        :direction="props.filters.direction"
                        empty-title="Belum ada aplikasi"
                    >
                        <template #cell-has_financial_report="{ row }">
                            <AppBadge :tone="row.has_financial_report ? 'success' : 'neutral'">{{ row.has_financial_report ? 'Ya' : 'Tidak' }}</AppBadge>
                        </template>
                        <template #cell-is_active="{ row }">
                            <AppBadge :tone="row.is_active ? 'success' : 'neutral'">{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</AppBadge>
                        </template>
                        <template #actions="{ row }">
                            <div class="flex justify-end gap-2">
                                <AppIconButton name="visibility" :aria-label="`Lihat ${row.name}`" :href="route('admin.applications.show', row.id)" />
                                <AppIconButton name="edit" :aria-label="`Edit ${row.name}`" :href="route('admin.applications.edit', row.id)" />
                                <AppIconButton :name="row.is_active ? 'toggle_off' : 'toggle_on'" :aria-label="row.is_active ? 'Nonaktifkan' : 'Aktifkan'" @click="toggle(row)" />
                                <AppIconButton name="delete" tone="danger" :aria-label="`Hapus ${row.name}`" :loading="deleting === row.id" @click="destroy(row)" />
                            </div>
                        </template>
                    </SmartDataTable>
                </div>
            </AppCard>
        </div>
    </AdminLayout>
</template>
