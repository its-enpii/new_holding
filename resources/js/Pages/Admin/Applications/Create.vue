<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import ApplicationForm from './ApplicationForm.vue';
</script>

<template>
    <Head title="Tambah Aplikasi" />
    <AdminLayout>
        <div class="space-y-6">
            <h1 class="text-3xl font-bold text-primary">Tambah Aplikasi</h1>
            <ApplicationForm submit-label="Buat Aplikasi" />
        </div>
    </AdminLayout>
</template>
