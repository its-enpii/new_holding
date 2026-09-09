<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppBadge from '../../../Components/AppBadge.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
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
        </div>
    </AdminLayout>
</template>
