<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from './AdminLayout.vue';

const page = usePage();
const navigation = computed(() => (page.props.auth?.user?.role === 'superadmin' ? 'admin' : 'tenant'));
</script>

<template>
    <AdminLayout v-if="navigation === 'admin'">
        <slot />
    </AdminLayout>
    <AdminLayout v-else>
        <slot />
    </AdminLayout>
</template>
