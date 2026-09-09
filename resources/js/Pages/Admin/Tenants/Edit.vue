<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import TenantForm from './TenantForm.vue';

defineProps({ tenant: { type: Object, required: true } });
</script>

<template>
    <Head title="Edit Tenant" />
    <AdminLayout>
        <div class="space-y-6">
            <h1 class="text-3xl font-bold text-primary">Edit Tenant</h1>
            <TenantForm :tenant="tenant" />
        </div>
    </AdminLayout>
</template>
