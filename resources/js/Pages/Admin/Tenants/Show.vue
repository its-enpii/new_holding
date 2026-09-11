<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppBadge from '../../../Components/AppBadge.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppEmptyState from '../../../Components/AppEmptyState.vue';
import AppIcon from '../../../Components/AppIcon.vue';

defineProps({ tenant: { type: Object, required: true } });
</script>

<template>
    <Head :title="tenant.name" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-on-surface-variant">
                        <Link :href="route('admin.tenants.index')" class="hover:text-primary">Tenants</Link>
                        <span>/</span>
                        <span>{{ tenant.name }}</span>
                    </div>
                    <h1 class="mt-1 text-3xl font-semibold text-primary">{{ tenant.name }}</h1>
                </div>
                <div class="flex items-center gap-3">
                    <AppButton variant="secondary" :href="route('admin.tenants.edit', tenant.id)">
                        <AppIcon name="edit" class="mr-1" />
                        Edit Tenant
                    </AppButton>
                    <AppButton variant="primary" :href="route('admin.tenants.applications.index', tenant.id)">
                        <AppIcon name="widgets" class="mr-1" />
                        Kelola Lisensi Aplikasi
                    </AppButton>
                </div>
            </div>

            <AppCard>
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div><dt class="text-sm text-on-surface-variant">Slug</dt><dd class="font-semibold text-primary">{{ tenant.slug }}</dd></div>
                    <div><dt class="text-sm text-on-surface-variant">Domain</dt><dd class="font-semibold text-primary">{{ tenant.domain || '—' }}</dd></div>
                    <div><dt class="text-sm text-on-surface-variant">Email</dt><dd class="font-semibold text-primary">{{ tenant.email }}</dd></div>
                    <div><dt class="text-sm text-on-surface-variant">Telepon</dt><dd class="font-semibold text-primary">{{ tenant.phone || '—' }}</dd></div>
                    <div><dt class="text-sm text-on-surface-variant">Status</dt><dd><AppBadge :tone="tenant.is_active ? 'success' : 'neutral'">{{ tenant.is_active ? 'Aktif' : 'Nonaktif' }}</AppBadge></dd></div>
                    <div class="sm:col-span-2"><dt class="text-sm text-on-surface-variant">Alamat</dt><dd class="font-semibold text-primary">{{ tenant.address || '—' }}</dd></div>
                </dl>
            </AppCard>

            <AppCard>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <AppIcon name="widgets" tone="secondary" container-size="10" />
                        <div>
                            <h2 class="text-lg font-semibold text-on-surface">Lisensi Aktif</h2>
                            <p class="text-sm text-on-surface-variant">Akses instance tanpa memasukkan ulang kredensial.</p>
                        </div>
                    </div>
                    <AppBadge tone="primary-soft">{{ tenant.tenantApplications?.length || 0 }} aplikasi</AppBadge>
                </div>

                <div v-if="!tenant.tenantApplications?.length" class="mt-4">
                    <AppEmptyState
                        icon="widgets"
                        title="Belum Ada Lisensi Aktif"
                        description="Tenant ini belum memiliki aplikasi aktif yang bisa dibuka melalui SSO."
                    />
                </div>

                <ul v-else class="mt-4 grid gap-3 md:grid-cols-2">
                    <li
                        v-for="tenantApplication in tenant.tenantApplications"
                        :key="tenantApplication.id"
                        class="flex items-center justify-between gap-3 rounded-md border border-outline-variant/50 bg-surface-container-low px-4 py-3"
                    >
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <AppIcon :name="tenantApplication.icon_path || 'widgets'" class="text-primary" />
                                <p class="truncate text-sm font-semibold text-on-surface">
                                    {{ tenantApplication.label || tenantApplication.application_name || 'Aplikasi' }}
                                </p>
                            </div>
                            <p class="mt-1 truncate font-mono text-[11px] text-on-surface-variant">
                                {{ tenantApplication.instance_url }}
                            </p>
                        </div>
                        <AppButton
                            variant="outline"
                            size="compact"
                            icon="open_in_new"
                            :href="route('admin.tenants.applications.sso', [tenant.id, tenantApplication.id])"
                        >
                            Buka
                        </AppButton>
                    </li>
                </ul>
            </AppCard>
        </div>
    </AdminLayout>
</template>
