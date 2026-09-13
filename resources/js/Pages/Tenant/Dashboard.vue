<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import AppBadge from '../../Components/AppBadge.vue';
import AppButton from '../../Components/AppButton.vue';
import AppCard from '../../Components/AppCard.vue';
import AppEmptyState from '../../Components/AppEmptyState.vue';
import AppIcon from '../../Components/AppIcon.vue';

defineProps({
    tenant: { type: Object, required: true },
    applications: { type: Array, default: () => [] },
});

const accessingId = ref(null);

function accessApp(app) {
    if (app.is_expired || !app.is_active) {
        return;
    }
    accessingId.value = app.id;
    router.post(route('app.access', app.id), {}, {
        onFinish: () => { accessingId.value = null; },
    });
}
</script>

<template>
    <Head title="Aplikasi Saya" />
    <AdminLayout>
        <div class="space-y-6">
            <header class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-semibold text-primary">Aplikasi Saya</h1>
                    <p class="mt-1 text-on-surface-variant">
                        Pusat kendali dan akses cepat ke seluruh sistem subsidiary <span class="font-semibold text-on-surface">{{ tenant.name }}</span>
                    </p>
                </div>
            </header>

            <div v-if="applications.length === 0">
                <AppCard>
                    <AppEmptyState
                        icon="widgets"
                        title="Belum Ada Aplikasi Terdaftar"
                        description="Unit usaha Anda belum memiliki aplikasi subsidiary yang di-assign oleh administrator holding. Hubungi vendor untuk aktivasi aplikasi."
                    />
                </AppCard>
            </div>

            <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <AppCard
                    v-for="app in applications"
                    :key="app.id"
                    class="group flex flex-col justify-between border border-outline-variant/60 transition hover:border-primary/50 hover:shadow-md"
                >
                    <!-- Header with Icon & Status -->
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="grid size-12 place-items-center rounded-lg bg-primary-container/40 text-primary shadow-xs transition group-hover:scale-105">
                                <AppIcon :name="app.application?.icon_path || 'widgets'" class="text-2xl" />
                            </div>
                            <div>
                                <AppBadge v-if="app.is_expired" tone="danger">Kadaluarsa</AppBadge>
                                <AppBadge v-else-if="app.is_expiring_soon" tone="warning">Segera Kadaluarsa</AppBadge>
                                <AppBadge v-else-if="!app.is_active" tone="neutral">Nonaktif</AppBadge>
                                <AppBadge v-else tone="success">Aktif</AppBadge>
                            </div>
                        </div>

                        <!-- Title & Description -->
                        <div class="mt-4 space-y-1">
                            <h2 class="text-lg font-semibold text-on-surface transition group-hover:text-primary">
                                {{ app.application?.name }}
                            </h2>
                            <p v-if="app.label" class="text-xs font-medium text-primary">
                                {{ app.label }}
                            </p>
                            <p class="line-clamp-2 text-xs text-on-surface-variant">
                                {{ app.application?.description || 'Aplikasi subsidiary operasional unit usaha.' }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer Action -->
                    <div class="mt-6 pt-4 border-t border-outline-variant/40">
                        <div v-if="app.is_expired" class="rounded-md bg-error-container/20 p-2.5 text-center">
                            <p class="text-xs font-medium text-error">
                                Masa aktif lisensi telah habis.
                            </p>
                            <p class="text-[11px] text-on-surface-variant mt-0.5">
                                Hubungi vendor holding untuk perpanjangan.
                            </p>
                        </div>
                        <div v-else-if="!app.is_active" class="rounded-md bg-surface-container p-2.5 text-center">
                            <p class="text-xs text-on-surface-variant">
                                Aplikasi dinonaktifkan oleh vendor.
                            </p>
                        </div>
                        <AppButton
                            v-else
                            variant="primary"
                            class="w-full"
                            :disabled="accessingId === app.id"
                            :loading="accessingId === app.id"
                            icon="launch"
                            @click="accessApp(app)"
                        >
                            Quick Access
                        </AppButton>
                    </div>
                </AppCard>
            </div>
        </div>
    </AdminLayout>
</template>
