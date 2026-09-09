<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppBadge from '../../../Components/AppBadge.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppIcon from '../../../Components/AppIcon.vue';
import AppIconButton from '../../../Components/AppIconButton.vue';
import SmartDataTable from '../../../Components/SmartDataTable.vue';
import { useConfirm } from '../../../Composables/useConfirm';
import { useToast } from '../../../Composables/useToast';

const props = defineProps({
    tenant: { type: Object, required: true },
    tenantApplications: { type: Object, required: true },
    filters: { type: Object, required: true },
    flash: { type: Object, default: () => ({}) },
});

const page = usePage();
const { confirm } = useConfirm();
const { toast } = useToast();
const deleting = ref(null);
const regenerating = ref(null);

const flashSecret = computed(() => props.flash?.new_api_secret || page.props.flash?.new_api_secret);

const columns = [
    { key: 'application', label: 'Aplikasi' },
    { key: 'label', label: 'Label Instance' },
    { key: 'instance_url', label: 'URL Instance' },
    { key: 'status', label: 'Status Lisensi', class: 'w-36' },
    { key: 'expired_at', label: 'Kadaluarsa' },
];

function isAppExpired(app) {
    if (!app.expired_at) return false;
    return new Date(app.expired_at) < new Date();
}

function getStatusTone(app) {
    if (isAppExpired(app)) return 'danger';
    return app.is_active ? 'success' : 'neutral';
}

function getStatusLabel(app) {
    if (isAppExpired(app)) return 'Kadaluarsa';
    return app.is_active ? 'Aktif' : 'Nonaktif';
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'short', day: 'numeric' });
}

function destroy(app) {
    confirm({
        title: 'Cabut lisensi aplikasi',
        message: `Lisensi aplikasi ${app.application?.name || 'ini'} untuk tenant ${props.tenant.name} akan dicabut. Lanjutkan?`,
        confirmLabel: 'Cabut Lisensi',
    }).then((confirmed) => {
        if (!confirmed) return;
        deleting.value = app.id;
        router.delete(route('admin.tenants.applications.destroy', [props.tenant.id, app.id]), {
            onFinish: () => { deleting.value = null; },
        });
    });
}

function regenerate(app) {
    confirm({
        title: 'Regenerate API Secret',
        message: 'Secret lama tidak akan berlaku lagi setelah digenerate ulang. Pastikan untuk memperbarui konfigurasi di instance aplikasi.',
        confirmLabel: 'Regenerate Secret',
    }).then((confirmed) => {
        if (!confirmed) return;
        regenerating.value = app.id;
        router.post(route('admin.tenants.applications.regenerate-secret', [props.tenant.id, app.id]), {}, {
            onFinish: () => { regenerating.value = null; },
        });
    });
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
        toast({ message: 'API Secret disalin ke clipboard!', type: 'success' });
    }
}
</script>

<template>
    <Head :title="`Lisensi Aplikasi - ${tenant.name}`" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-on-surface-variant">
                        <Link :href="route('admin.tenants.index')" class="hover:text-primary">Tenants</Link>
                        <span>/</span>
                        <Link :href="route('admin.tenants.show', tenant.id)" class="hover:text-primary">{{ tenant.name }}</Link>
                        <span>/</span>
                        <span>Lisensi Aplikasi</span>
                    </div>
                    <h1 class="mt-1 text-3xl font-semibold text-primary">Lisensi Aplikasi</h1>
                    <p class="mt-1 text-on-surface-variant">Daftar aplikasi yang di-assign untuk <span class="font-semibold text-on-surface">{{ tenant.name }}</span></p>
                </div>
                <div class="flex items-center gap-3">
                    <AppButton variant="secondary" :href="route('admin.tenants.show', tenant.id)">
                        Detail Tenant
                    </AppButton>
                    <AppButton variant="primary" :href="route('admin.tenants.applications.create', tenant.id)">
                        <AppIcon name="add" class="mr-1" />
                        Assign Aplikasi
                    </AppButton>
                </div>
            </div>

            <div v-if="flashSecret" class="flex flex-wrap items-center justify-between gap-4 rounded-md border border-tertiary/40 bg-tertiary-container/30 p-4 text-on-tertiary-container">
                <div class="space-y-1">
                    <p class="text-sm font-semibold flex items-center gap-2">
                        <AppIcon name="key" />
                        API Secret Baru Telah Dibuat
                    </p>
                    <p class="font-mono text-xs break-all select-all">{{ flashSecret }}</p>
                </div>
                <AppButton variant="secondary" size="sm" @click="copyToClipboard(flashSecret)">
                    <AppIcon name="content_copy" class="mr-1" />
                    Salin Secret
                </AppButton>
            </div>

            <AppCard :padded="false">
                <div class="p-6">
                    <SmartDataTable
                        :rows="props.tenantApplications.data"
                        :columns="columns"
                        :pagination="props.tenantApplications"
                        :url="route('admin.tenants.applications.index', props.tenant.id)"
                        :search="props.filters.search"
                        :per-page="props.filters.per_page"
                        :sort="props.filters.sort"
                        :direction="props.filters.direction"
                        empty-title="Belum ada aplikasi yang di-assign"
                    >
                        <template #cell-application="{ row }">
                            <div class="flex items-center gap-3">
                                <div class="grid size-9 shrink-0 place-items-center rounded-md bg-primary-container/40 text-primary">
                                    <AppIcon :name="row.application?.icon_path || 'widgets'" />
                                </div>
                                <div>
                                    <p class="font-semibold text-on-surface">{{ row.application?.name }}</p>
                                    <p class="text-xs text-on-surface-variant">{{ row.application?.slug }}</p>
                                </div>
                            </div>
                        </template>

                        <template #cell-label="{ row }">
                            <span class="text-on-surface">{{ row.label || '—' }}</span>
                        </template>

                        <template #cell-instance_url="{ row }">
                            <a :href="row.instance_url" target="_blank" rel="noopener noreferrer" class="truncate text-xs font-mono text-primary underline">
                                {{ row.instance_url }}
                            </a>
                        </template>

                        <template #cell-status="{ row }">
                            <AppBadge :tone="getStatusTone(row)">{{ getStatusLabel(row) }}</AppBadge>
                        </template>

                        <template #cell-expired_at="{ row }">
                            <span class="text-xs text-on-surface-variant">{{ formatDate(row.expired_at) }}</span>
                        </template>

                        <template #actions="{ row }">
                            <div class="flex justify-end gap-1.5">
                                <AppIconButton
                                    name="visibility"
                                    :aria-label="`Detail lisensi ${row.application?.name}`"
                                    :href="route('admin.tenants.applications.show', [props.tenant.id, row.id])"
                                />
                                <AppIconButton
                                    name="edit"
                                    :aria-label="`Edit lisensi ${row.application?.name}`"
                                    :href="route('admin.tenants.applications.edit', [props.tenant.id, row.id])"
                                />
                                <AppIconButton
                                    name="refresh"
                                    :aria-label="`Regenerate secret ${row.application?.name}`"
                                    :loading="regenerating === row.id"
                                    @click="regenerate(row)"
                                />
                                <AppIconButton
                                    name="delete"
                                    tone="danger"
                                    :aria-label="`Cabut lisensi ${row.application?.name}`"
                                    :loading="deleting === row.id"
                                    @click="destroy(row)"
                                />
                            </div>
                        </template>
                    </SmartDataTable>
                </div>
            </AppCard>
        </div>
    </AdminLayout>
</template>
