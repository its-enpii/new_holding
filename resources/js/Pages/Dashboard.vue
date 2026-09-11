<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../Layouts/AdminLayout.vue';
import AppBadge from '../Components/AppBadge.vue';
import AppButton from '../Components/AppButton.vue';
import AppCard from '../Components/AppCard.vue';
import AppEmptyState from '../Components/AppEmptyState.vue';
import AppIcon from '../Components/AppIcon.vue';
import AppQuickAssignSelect from '../Components/AppQuickAssignSelect.vue';

defineProps({
    stats: { type: Object, required: true },
    licenseAlerts: {
        type: Object,
        default: () => ({ items: [], total: 0 }),
    },
    tenantList: { type: Array, default: () => [] },
    availableApplications: { type: Array, default: () => [] },
});

function formatExpiredAt(value) {
    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Dashboard" />
    <AdminLayout>
        <div class="space-y-6">
            <header>
                <h1 class="text-3xl font-semibold text-primary">Dashboard Superadmin</h1>
                <p class="mt-2 text-on-surface-variant">Ringkasan tenant, aplikasi, dan pengguna.</p>
            </header>
            <div class="grid gap-4 sm:grid-cols-3">
                <AppCard class="border border-outline-variant/60 border-l-[3px] border-l-primary bg-gradient-to-br from-surface-container-lowest to-surface-container-low shadow-sm">
                    <div class="flex items-center gap-4">
                        <AppIcon name="apartment" tone="primary" container-size="12" />
                        <div><p class="text-sm text-on-surface-variant">Tenant aktif</p><p class="text-3xl font-semibold text-primary">{{ stats.activeTenants }}</p></div>
                    </div>
                </AppCard>
                <AppCard class="border border-outline-variant/60 border-l-[3px] border-l-secondary bg-gradient-to-br from-surface-container-lowest to-surface-container-low shadow-sm">
                    <div class="flex items-center gap-4">
                        <AppIcon name="widgets" tone="secondary" container-size="12" />
                        <div><p class="text-sm text-on-surface-variant">Aplikasi</p><p class="text-3xl font-semibold text-secondary">{{ stats.applications }}</p></div>
                    </div>
                </AppCard>
                <AppCard class="border border-outline-variant/60 border-l-[3px] border-l-tertiary bg-gradient-to-br from-surface-container-lowest to-surface-container-low shadow-sm">
                    <div class="flex items-center gap-4">
                        <AppIcon name="group" tone="info" container-size="12" />
                        <div><p class="text-sm text-on-surface-variant">Pengguna</p><p class="text-3xl font-semibold text-tertiary">{{ stats.users }}</p></div>
                    </div>
                </AppCard>
            </div>

            <AppCard>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <AppIcon name="apartment" tone="primary" container-size="10" />
                        <div>
                            <h2 class="text-lg font-semibold text-on-surface">Daftar Usaha</h2>
                            <p class="text-sm text-on-surface-variant">Seluruh tenant beserta aplikasi terpasang dan status lisensi.</p>
                        </div>
                    </div>
                    <AppBadge tone="primary-soft">{{ tenantList.length }} usaha</AppBadge>
                </div>

                <div v-if="tenantList.length === 0" class="mt-4">
                    <AppEmptyState
                        icon="apartment"
                        title="Belum Ada Usaha"
                        description="Tenant belum tersedia untuk ditampilkan di dashboard."
                    />
                </div>

                <div v-else class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <AppCard
                        v-for="tenant in tenantList"
                        :key="tenant.id"
                        class="border border-outline-variant/60 transition hover:border-primary/50 hover:shadow-md"
                    >
                        <div class="flex h-full flex-col">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate text-lg font-semibold text-primary">{{ tenant.name }}</h3>
                                    <p class="mt-1 text-xs text-on-surface-variant">{{ tenant.applications_count }} aplikasi terpasang</p>
                                </div>
                                <AppBadge :tone="tenant.is_active ? 'success' : 'neutral'">
                                    {{ tenant.is_active ? 'Aktif' : 'Nonaktif' }}
                                </AppBadge>
                            </div>

                            <dl class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-md bg-surface-container-low p-3">
                                    <dt class="text-[11px] font-medium text-on-surface-variant">Expiring ≤7 hari</dt>
                                    <dd class="mt-1 text-xl font-semibold" :class="tenant.expiring_count > 0 ? 'text-tertiary' : 'text-on-surface'">
                                        {{ tenant.expiring_count }}
                                    </dd>
                                </div>
                                <div class="rounded-md bg-surface-container-low p-3">
                                    <dt class="text-[11px] font-medium text-on-surface-variant">Kadaluarsa</dt>
                                    <dd class="mt-1 text-xl font-semibold" :class="tenant.expired_count > 0 ? 'text-error' : 'text-on-surface'">
                                        {{ tenant.expired_count }}
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-4 flex-1">
                                <p v-if="tenant.applications.length === 0" class="rounded-md bg-surface-container-low p-3 text-xs text-on-surface-variant">
                                    Belum ada aplikasi aktif.
                                </p>
                                <ul v-else class="space-y-2">
                                    <li
                                        v-for="application in tenant.applications"
                                        :key="application.id"
                                        class="flex items-center justify-between gap-3 rounded-md bg-surface-container-low px-3 py-2"
                                    >
                                        <span class="flex min-w-0 items-center gap-2 text-xs font-medium text-on-surface">
                                            <AppIcon :name="application.icon_path || 'widgets'" class="text-primary" />
                                            <span class="truncate">
                                                {{ application.label || application.application_name || 'Aplikasi' }}
                                                <span v-if="application.sub_tenant_code" class="font-normal text-on-surface-variant">
                                                    · {{ application.sub_tenant_code }}
                                                </span>
                                            </span>
                                        </span>
                                        <AppButton
                                            variant="ghost"
                                            size="compact"
                                            icon="open_in_new"
                                            :href="route('admin.tenants.applications.sso', [tenant.id, application.id])"
                                            aria-label="Buka Aplikasi"
                                        />
                                    </li>
                                </ul>
                            </div>

                            <div class="mt-5 border-t border-outline-variant/40 pt-4">
                                <AppQuickAssignSelect
                                    v-if="availableApplications.length"
                                    class="mb-3"
                                    :tenant-id="tenant.id"
                                    :available-applications="availableApplications"
                                    :assigned-application-ids="tenant.assigned_application_ids"
                                />
                                <AppButton variant="secondary" size="compact" :href="route('admin.tenants.show', tenant.id)" class="w-full">
                                    <AppIcon name="visibility" />
                                    Detail Usaha
                                </AppButton>
                            </div>
                        </div>
                    </AppCard>
                </div>
            </AppCard>

            <AppCard>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <AppIcon name="event_available" tone="tertiary" container-size="10" />
                        <div>
                            <h2 class="text-lg font-semibold text-on-surface">Lisensi</h2>
                            <p class="text-sm text-on-surface-variant">Mendekati kadaluarsa dalam 7 hari atau sudah kadaluarsa.</p>
                        </div>
                    </div>
                    <AppBadge v-if="licenseAlerts.total > 0" tone="warning-soft">
                        {{ licenseAlerts.total }} lisensi
                    </AppBadge>
                </div>

                <div v-if="licenseAlerts.items.length === 0" class="mt-4">
                    <AppEmptyState
                        icon="verified"
                        title="Lisensi Terpantau"
                        description="Tidak ada lisensi aktif yang mendekati kadaluarsa atau sudah kadaluarsa."
                    />
                </div>

                <ul v-else class="mt-4 divide-y divide-outline-variant/40">
                    <li v-for="license in licenseAlerts.items" :key="`${license.status}-${license.id}`" class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-on-surface">
                                {{ license.application_name }}
                                <span v-if="license.label" class="font-normal text-on-surface-variant">· {{ license.label }}</span>
                            </p>
                            <p class="text-sm text-on-surface-variant">{{ license.tenant_name }} · {{ formatExpiredAt(license.expired_at) }}</p>
                        </div>
                        <AppBadge :tone="license.status === 'expired' ? 'error' : 'warning'">
                            {{ license.status === 'expired' ? 'Kadaluarsa' : 'Mendekati' }}
                        </AppBadge>
                    </li>
                </ul>

                <p v-if="licenseAlerts.total > licenseAlerts.items.length" class="mt-3 text-sm text-on-surface-variant">
                    + {{ licenseAlerts.total - licenseAlerts.items.length }} lisensi lainnya
                </p>
            </AppCard>
        </div>
    </AdminLayout>
</template>
