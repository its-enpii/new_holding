<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppBadge from '../../../Components/AppBadge.vue';
import AppCard from '../../../Components/AppCard.vue';

defineProps({ tenant: { type: Object, required: true } });
</script>

<template>
    <Head :title="tenant.name" />
    <AdminLayout>
        <div class="space-y-6">
            <h1 class="text-3xl font-bold text-primary">{{ tenant.name }}</h1>
            <AppCard>
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div><dt class="text-sm text-on-surface-variant">Slug</dt><dd class="font-semibold text-primary">{{ tenant.slug }}</dd></div>
                    <div><dt class="text-sm text-on-surface-variant">Email</dt><dd class="font-semibold text-primary">{{ tenant.email }}</dd></div>
                    <div><dt class="text-sm text-on-surface-variant">Telepon</dt><dd class="font-semibold text-primary">{{ tenant.phone || '—' }}</dd></div>
                    <div><dt class="text-sm text-on-surface-variant">Status</dt><dd><AppBadge :tone="tenant.is_active ? 'success' : 'neutral'">{{ tenant.is_active ? 'Aktif' : 'Nonaktif' }}</AppBadge></dd></div>
                    <div class="sm:col-span-2"><dt class="text-sm text-on-surface-variant">Alamat</dt><dd class="font-semibold text-primary">{{ tenant.address || '—' }}</dd></div>
                </dl>
            </AppCard>
        </div>
    </AdminLayout>
</template>
