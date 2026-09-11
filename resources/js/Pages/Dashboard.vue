<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../Layouts/AdminLayout.vue';
import AppBadge from '../Components/AppBadge.vue';
import AppButton from '../Components/AppButton.vue';
import AppCard from '../Components/AppCard.vue';
import AppEmptyState from '../Components/AppEmptyState.vue';
import AppIcon from '../Components/AppIcon.vue';

defineProps({
    stats: { type: Object, required: true },
    licenseAlerts: {
        type: Object,
        default: () => ({ items: [], total: 0 }),
    },
    applicationList: { type: Array, default: () => [] },
    tenantList: { type: Array, default: () => [] },
});

function formatExpiredAt(value) {
    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function connectionTone(application) {
    if (application.connection_issues_count > 0) return 'error-soft';
    if (application.connected_count > 0) return 'success-soft';

    return 'neutral';
}
</script>

<template>
    <Head title="Dashboard" />
    <AdminLayout>
        <div class="space-y-6">
            <header>
                <h1 class="text-3xl font-semibold text-primary">Dashboard Superadmin</h1>
                <p class="mt-2 text-on-surface-variant">Kelola aplikasi vendor dan pantau pemakaiannya di setiap usaha.</p>
            </header>

            <div class="grid gap-4 sm:grid-cols-3">
                <AppCard class="border border-outline-variant/60 border-l-[3px] border-l-primary bg-gradient-to-br from-surface-container-lowest to-surface-container-low shadow-sm">
                    <div class="flex items-center gap-4">
                        <AppIcon name="apartment" tone="primary" container-size="12" />
                        <div>
                            <p class="text-sm text-on-surface-variant">Tenant aktif</p>
                            <p class="text-3xl font-semibold text-primary">{{ stats.activeTenants }}</p>
                        </div>
                    </div>
                </AppCard>
                <AppCard class="border border-outline-variant/60 border-l-[3px] border-l-secondary bg-gradient-to-br from-surface-container-lowest to-surface-container-low shadow-sm">
                    <div class="flex items-center gap-4">
                        <AppIcon name="widgets" tone="secondary" container-size="12" />
                        <div>
                            <p class="text-sm text-on-surface-variant">Aplikasi</p>
                            <p class="text-3xl font-semibold text-secondary">{{ stats.applications }}</p>
                        </div>
                    </div>
                </AppCard>
                <AppCard class="border border-outline-variant/60 border-l-[3px] border-l-tertiary bg-gradient-to-br from-surface-container-lowest to-surface-container-low shadow-sm">
                    <div class="flex items-center gap-4">
                        <AppIcon name="verified_user" tone="info" container-size="12" />
                        <div>
                            <p class="text-sm text-on-surface-variant">Lisensi aktif</p>
                            <p class="text-3xl font-semibold text-tertiary">{{ stats.assignedApps }}</p>
                        </div>
                    </div>
                </AppCard>
            </div>

            <AppCard>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <AppIcon name="widgets" tone="secondary" container-size="10" />
                        <div>
                            <h2 class="text-lg font-semibold text-on-surface">Daftar Aplikasi</h2>
                            <p class="text-sm text-on-surface-variant">Aplikasi vendor Anda dan pemakaiannya di seluruh usaha.</p>
                        </div>
                    </div>
                    <AppBadge tone="primary-soft">{{ applicationList.length }} aplikasi</AppBadge>
                </div>

                <div v-if="applicationList.length === 0" class="mt-4">
                    <AppEmptyState
                        icon="widgets"
                        title="Belum Ada Aplikasi"
                        description="Daftarkan aplikasi master terlebih dahulu untuk mengisikan katalog vendor."
                    />
                </div>

                <div v-else class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <AppCard
                        v-for="application in applicationList"
                        :key="application.id"
                        class="flex h-full flex-col border border-outline-variant/60 transition hover:border-primary/50 hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <AppIcon :name="application.icon_path || 'widgets'" tone="primary" container-size="11" />
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-semibold text-on-surface">{{ application.name }}</h3>
                                    <p class="mt-0.5 truncate text-xs text-on-surface-variant">{{ application.description || 'Katalog aplikasi vendor' }}</p>
                                </div>
                            </div>
                            <AppBadge :tone="application.is_active ? 'success-soft' : 'neutral'">
                                {{ application.is_active ? 'Aktif' : 'Nonaktif' }}
                            </AppBadge>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-2">
                            <div class="rounded-md bg-surface-container-low px-3 py-2">
                                <dt class="text-[11px] font-medium text-on-surface-variant">Dipakai usaha</dt>
                                <dd class="text-xl font-semibold text-on-surface">{{ application.tenants_using_count }}</dd>
                            </div>
                            <div class="rounded-md bg-surface-container-low px-3 py-2">
                                <dt class="text-[11px] font-medium text-on-surface-variant">Lisensi aktif</dt>
                                <dd class="text-xl font-semibold text-primary">{{ application.active_licenses_count }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4 flex-1">
                            <p
                                v-if="application.tenants_using_count === 0"
                                class="rounded-md border border-dashed border-outline-variant bg-surface-container-low px-3 py-2 text-xs text-outline"
                            >
                                Belum dipakai
                            </p>
                            <AppBadge v-else :tone="connectionTone(application)">
                                <span class="inline-flex items-center gap-1.5">
                                    <AppIcon :name="application.connection_issues_count > 0 ? 'error' : 'link'" class="text-sm" />
                                    {{ application.connected_count }} terhubung · {{ application.connection_issues_count }} bermasalah
                                </span>
                            </AppBadge>
                        </div>

                        <div class="mt-5 border-t border-outline-variant/40 pt-4">
                            <AppButton
                                variant="secondary"
                                size="compact"
                                icon="settings"
                                :href="route('admin.applications.show', application.id)"
                                class="w-full"
                            >
                                Kelola
                            </AppButton>
                        </div>
                    </AppCard>
                </div>
            </AppCard>

            <AppCard>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <AppIcon name="apartment" tone="primary" container-size="10" />
                        <div>
                            <h2 class="text-lg font-semibold text-on-surface">Daftar Usaha</h2>
                            <p class="text-sm text-on-surface-variant">Tenant holding yang memakai aplikasi Anda.</p>
                        </div>
                    </div>
                    <AppBadge tone="primary-soft">{{ tenantList.length }} usaha</AppBadge>
                </div>

                <div v-if="tenantList.length === 0" class="mt-4">
                    <AppEmptyState
                        icon="apartment"
                        title="Belum Ada Usaha"
                        description="Tenant holding belum tersedia untuk ditampilkan di dashboard."
                    />
                </div>

                <ul v-else class="mt-4 divide-y divide-outline-variant/40 overflow-hidden rounded-md border border-outline-variant/60">
                    <li
                        v-for="tenant in tenantList"
                        :key="tenant.id"
                        class="flex flex-col gap-3 bg-surface-container-lowest px-4 py-4 transition hover:bg-surface-container-low sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <AppIcon name="apartment" tone="primary" container-size="9" />
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-on-surface">{{ tenant.name }}</p>
                                <p class="text-xs text-on-surface-variant">{{ tenant.applications_count }} aplikasi terpasang</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            <AppBadge :tone="tenant.is_active ? 'success-soft' : 'neutral'">
                                {{ tenant.is_active ? 'Aktif' : 'Nonaktif' }}
                            </AppBadge>
                            <AppBadge v-if="tenant.expired_count > 0" tone="error-soft">
                                {{ tenant.expired_count }} bermasalah
                            </AppBadge>
                            <AppBadge v-else-if="tenant.expiring_count > 0" tone="warning-soft">
                                {{ tenant.expiring_count }} mendekati
                            </AppBadge>
                            <AppButton variant="outline" size="compact" icon="visibility" :href="route('admin.tenants.show', tenant.id)">
                                Detail
                            </AppButton>
                        </div>
                    </li>
                </ul>
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
                    <AppBadge v-if="licenseAlerts.total > 0" tone="warning-soft">{{ licenseAlerts.total }} lisensi</AppBadge>
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
