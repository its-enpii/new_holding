<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import ApplicationForm from './ApplicationForm.vue';

defineProps({ application: { type: Object, required: true } });
</script>

<template>
    <Head title="Edit Aplikasi" />
    <AdminLayout>
        <div class="space-y-6">
            <h1 class="text-3xl font-bold text-primary">Edit Aplikasi</h1>
            <ApplicationForm :application="application" />
        </div>
    </AdminLayout>
</template>
