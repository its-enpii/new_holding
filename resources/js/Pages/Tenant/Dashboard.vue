<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import AppCard from '../../Components/AppCard.vue';
import AppEmptyState from '../../Components/AppEmptyState.vue';

defineProps({ tenant: { type: Object, required: true } });
</script>

<template>
    <Head title="Aplikasi saya" />
    <AdminLayout>
        <div class="space-y-6">
            <header>
                <h1 class="text-3xl font-semibold text-primary">Aplikasi saya</h1>
                <p class="mt-2 text-on-surface-variant">{{ tenant.name }}</p>
            </header>
            <AppCard>
                <AppEmptyState icon="widgets" title="Aplikasi menyusul" description="Pendaftaran aplikasi tenant akan tersedia pada Phase 2." />
            </AppCard>
        </div>
    </AdminLayout>
</template>
