<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppBadge from '../../../Components/AppBadge.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppIcon from '../../../Components/AppIcon.vue';
import AppModal from '../../../Components/AppModal.vue';
import AppSelect from '../../../Components/AppSelect.vue';
import SmartDataTable from '../../../Components/SmartDataTable.vue';

const props = defineProps({
    logs: { type: Object, required: true },
    distinctActions: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
});

const selectedMetadata = ref(null);
const metadataModalOpen = ref(false);

const columns = [
    { key: 'created_at', label: 'Waktu', sortable: true, class: 'w-44' },
    { key: 'user', label: 'Pengguna' },
    { key: 'tenant', label: 'Tenant' },
    { key: 'action', label: 'Aktivitas', sortable: true, class: 'w-36' },
    { key: 'subject', label: 'Objek / Subjek' },
    { key: 'ip_address', label: 'Alamat IP', class: 'w-32' },
    { key: 'metadata', label: 'Detail', class: 'w-24' },
];

const actionOptions = computed(() => [
    { value: '', label: 'Semua Aktivitas' },
    ...props.distinctActions.map((action) => ({
        value: action,
        label: action,
    })),
]);

function getActionTone(action) {
    if (['create', 'assign_app', 'staff_created'].includes(action)) return 'primary';
    if (['login', 'access_app'].includes(action)) return 'info';
    if (['update', 'update_app_license', 'staff_updated', 'staff_password_reset', 'regenerate_api_secret', 'activate'].includes(action)) return 'warning';
    if (['delete', 'revoke_app', 'deactivate', 'staff_deactivated'].includes(action)) return 'danger';
    return 'neutral';
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function getSubjectDisplay(row) {
    if (!row.subject_type) return '—';
    const type = row.subject_type.split('\\').pop();
    return row.subject_id ? `${type} #${row.subject_id}` : type;
}

function showMetadata(row) {
    selectedMetadata.value = {
        action: row.action,
        user: row.user?.name,
        created_at: formatDate(row.created_at),
        data: row.metadata,
    };
    metadataModalOpen.value = true;
}

function filterByAction(newAction) {
    router.get(
        route('admin.activity-logs.index'),
        {
            ...props.filters,
            action: newAction,
            page: 1,
        },
        { preserveState: true, replace: true }
    );
}
</script>

<template>
    <Head title="Log Aktivitas" />
    <AdminLayout>
        <div class="space-y-6">
            <header>
                <h1 class="text-3xl font-semibold text-primary">Log Aktivitas</h1>
                <p class="mt-1 text-on-surface-variant">
                    Audit trail dan rekaman aktivitas sistem seluruh tenant dan superadmin.
                </p>
            </header>

            <AppCard :padded="false">
                <div class="p-6">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="w-64">
                            <AppSelect
                                :model-value="props.filters.action || ''"
                                :options="actionOptions"
                                placeholder="Filter Aksi"
                                @update:model-value="filterByAction"
                            />
                        </div>
                    </div>

                    <SmartDataTable
                        :rows="props.logs.data"
                        :columns="columns"
                        :pagination="props.logs"
                        :url="route('admin.activity-logs.index')"
                        :search="props.filters.search"
                        :per-page="props.filters.per_page"
                        :sort="props.filters.sort"
                        :direction="props.filters.direction"
                        empty-title="Belum ada catatan aktivitas"
                    >
                        <template #cell-created_at="{ row }">
                            <span class="text-xs font-mono text-on-surface-variant">{{ formatDate(row.created_at) }}</span>
                        </template>

                        <template #cell-user="{ row }">
                            <div v-if="row.user">
                                <p class="font-semibold text-on-surface text-xs">{{ row.user.name }}</p>
                                <p class="text-[11px] text-on-surface-variant">{{ row.user.email }}</p>
                            </div>
                            <span v-else class="text-xs text-on-surface-variant">Sistem / Tamu</span>
                        </template>

                        <template #cell-tenant="{ row }">
                            <span v-if="row.tenant" class="text-xs font-semibold text-primary">
                                {{ row.tenant.name }}
                            </span>
                            <span v-else class="text-xs text-on-surface-variant">
                                Vendor Holding
                            </span>
                        </template>

                        <template #cell-action="{ row }">
                            <AppBadge :tone="getActionTone(row.action)">
                                {{ row.action }}
                            </AppBadge>
                        </template>

                        <template #cell-subject="{ row }">
                            <span class="text-xs font-mono text-on-surface-variant">{{ getSubjectDisplay(row) }}</span>
                        </template>

                        <template #cell-ip_address="{ row }">
                            <span class="text-xs font-mono text-on-surface-variant">{{ row.ip_address || '—' }}</span>
                        </template>

                        <template #cell-metadata="{ row }">
                            <button
                                v-if="row.metadata"
                                type="button"
                                class="inline-flex items-center gap-1 text-xs text-primary underline hover:text-primary-deep cursor-pointer"
                                @click="showMetadata(row)"
                            >
                                <AppIcon name="info" class="text-xs" />
                                <span>Detail</span>
                            </button>
                            <span v-else class="text-xs text-on-surface-variant">—</span>
                        </template>
                    </SmartDataTable>
                </div>
            </AppCard>

            <!-- Metadata Dialog -->
            <AppModal :open="metadataModalOpen" :title="`Detail Metadata - ${selectedMetadata?.action}`" @close="metadataModalOpen = false">
                <div class="space-y-3">
                    <div class="text-xs text-on-surface-variant">
                        <p><span class="font-semibold text-on-surface">Pengguna:</span> {{ selectedMetadata?.user }}</p>
                        <p><span class="font-semibold text-on-surface">Waktu:</span> {{ selectedMetadata?.created_at }}</p>
                    </div>
                    <pre class="rounded-md bg-surface-container-lowest p-3 font-mono text-xs text-on-surface overflow-x-auto border border-outline-variant/40">{{ JSON.stringify(selectedMetadata?.data, null, 2) }}</pre>
                    <div class="mt-4 flex justify-end">
                        <AppButton variant="secondary" @click="metadataModalOpen = false">
                            Tutup
                        </AppButton>
                    </div>
                </div>
            </AppModal>
        </div>
    </AdminLayout>
</template>
