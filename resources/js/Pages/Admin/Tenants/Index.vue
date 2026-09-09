<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppBadge from '../../../Components/AppBadge.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppIconButton from '../../../Components/AppIconButton.vue';
import SmartDataTable from '../../../Components/SmartDataTable.vue';
import { useConfirm } from '../../../Composables/useConfirm';

const props = defineProps({ tenants: { type: Object, required: true }, filters: { type: Object, required: true } });
const { confirm } = useConfirm();
const deleting = ref(null);

const columns = [
    { key: 'name', label: 'Nama', sortable: true },
    { key: 'slug', label: 'Slug', sortable: true },
    { key: 'email', label: 'Email' },
    { key: 'is_active', label: 'Status', sortable: true, class: 'w-32' },
];

function destroy(tenant) {
    confirm({
        title: 'Hapus tenant',
        message: `Tenant ${tenant.name} akan dihapus permanen. Lanjutkan?`,
        confirmLabel: 'Hapus',
    }).then((confirmed) => {
        if (! confirmed) return;
        deleting.value = tenant.id;
        router.delete(route('admin.tenants.destroy', tenant.id), { onFinish: () => { deleting.value = null; } });
    });
}

function toggle(tenant) {
    router.patch(route('admin.tenants.toggle', tenant.id));
}
</script>

<template>
    <Head title="Tenants" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <header>
                    <h1 class="text-3xl font-semibold text-primary">Tenants</h1>
                    <p class="mt-2 text-on-surface-variant">Kelola unit usaha terdaftar.</p>
                </header>
                <AppIconButton name="add" filled aria-label="Tambah tenant" :href="route('admin.tenants.create')" />
            </div>
            <AppCard :padded="false">
                <div class="p-6">
                    <SmartDataTable
                        :rows="props.tenants.data"
                        :columns="columns"
                        :pagination="props.tenants"
                        :url="route('admin.tenants.index')"
                        :search="props.filters.search"
                        :per-page="props.filters.per_page"
                        :sort="props.filters.sort"
                        :direction="props.filters.direction"
                        empty-title="Belum ada tenant"
                    >
                        <template #cell-is_active="{ row }">
                            <AppBadge :tone="row.is_active ? 'success' : 'neutral'">{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</AppBadge>
                        </template>
                        <template #actions="{ row }">
                            <div class="flex justify-end gap-2">
                                <AppIconButton name="widgets" :aria-label="`Lisensi Aplikasi ${row.name}`" :href="route('admin.tenants.applications.index', row.id)" />
                                <AppIconButton name="visibility" :aria-label="`Lihat ${row.name}`" :href="route('admin.tenants.show', row.id)" />
                                <AppIconButton name="edit" :aria-label="`Edit ${row.name}`" :href="route('admin.tenants.edit', row.id)" />
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
