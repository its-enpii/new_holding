<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import TenantForm from './TenantForm.vue';
</script>

<template>
    <Head title="Tambah Tenant" />
    <AdminLayout>
        <div class="space-y-6">
            <h1 class="text-3xl font-bold text-primary">Tambah Tenant</h1>
            <TenantForm submit-label="Buat Tenant" />
        </div>
    </AdminLayout>
</template>
