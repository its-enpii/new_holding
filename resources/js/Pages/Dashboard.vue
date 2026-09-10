<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../Layouts/AdminLayout.vue';
import AppBadge from '../Components/AppBadge.vue';
import AppCard from '../Components/AppCard.vue';
import AppEmptyState from '../Components/AppEmptyState.vue';
import AppIcon from '../Components/AppIcon.vue';

defineProps({
    stats: { type: Object, required: true },
    licenseAlerts: {
        type: Object,
        default: () => ({ items: [], total: 0 }),
    },
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
