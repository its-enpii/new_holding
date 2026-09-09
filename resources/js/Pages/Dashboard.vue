<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../Layouts/AdminLayout.vue';
import AppCard from '../Components/AppCard.vue';
import AppIcon from '../Components/AppIcon.vue';

defineProps({ stats: { type: Object, required: true } });
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
                <AppCard class="border border-outline-variant/60 bg-gradient-to-br from-surface-container-lowest to-surface-container-low shadow-sm">
                    <div class="flex items-center gap-4">
                        <AppIcon name="apartment" tone="primary" container-size="12" />
                        <div><p class="text-sm text-on-surface-variant">Tenant aktif</p><p class="text-3xl font-semibold text-primary">{{ stats.activeTenants }}</p></div>
                    </div>
                </AppCard>
                <AppCard class="border border-outline-variant/60 bg-gradient-to-br from-surface-container-lowest to-surface-container-low shadow-sm">
                    <div class="flex items-center gap-4">
                        <AppIcon name="widgets" tone="secondary" container-size="12" />
                        <div><p class="text-sm text-on-surface-variant">Aplikasi</p><p class="text-3xl font-semibold text-primary">{{ stats.applications }}</p></div>
                    </div>
                </AppCard>
                <AppCard class="border border-outline-variant/60 bg-gradient-to-br from-surface-container-lowest to-surface-container-low shadow-sm">
                    <div class="flex items-center gap-4">
                        <AppIcon name="group" tone="info" container-size="12" />
                        <div><p class="text-sm text-on-surface-variant">Pengguna</p><p class="text-3xl font-semibold text-primary">{{ stats.users }}</p></div>
                    </div>
                </AppCard>
            </div>
        </div>
    </AdminLayout>
</template>
