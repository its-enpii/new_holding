<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppButton from '../../Components/AppButton.vue';
import AppIcon from '../../Components/AppIcon.vue';

const props = defineProps({
    status: { type: Number, required: true },
});

const messages = computed(() => ({
    403: { title: 'Akses ditolak', description: 'Anda tidak memiliki izin untuk membuka halaman ini.' },
    404: { title: 'Halaman tidak ditemukan', description: 'Tautan mungkin sudah tidak tersedia atau salah ketik.' },
    419: { title: 'Sesi berakhir', description: 'Silakan coba lagi dari halaman sebelumnya.' },
    500: { title: 'Terjadi kesalahan server', description: 'Sistem gagal memproses permintaan Anda. Coba beberapa saat lagi.' },
}[props.status] ?? { title: 'Terjadi kesalahan', description: 'Sistem gagal memproses permintaan Anda.' }));

const returnUrl = computed(() => (usePage().props.auth?.user ? route('dashboard') : route('home')));
</script>

<template>
    <main class="grid min-h-screen place-items-center bg-surface px-6 py-10 font-sans text-on-surface">
        <div class="w-full max-w-xl rounded-lg border border-outline-variant/60 bg-surface-container-lowest p-8 text-center shadow-sm sm:p-12">
            <span class="mx-auto grid size-16 place-items-center rounded-md bg-primary-container/20 text-primary">
                <AppIcon name="report_problem" class="text-4xl" />
            </span>
            <p class="mt-8 text-7xl font-semibold tracking-tight text-primary">{{ status }}</p>
            <h1 class="mt-3 text-2xl font-semibold text-on-surface">{{ messages.title }}</h1>
            <p class="mt-3 text-on-surface-variant">{{ messages.description }}</p>
            <AppButton class="mt-8" :href="returnUrl">Kembali ke Beranda</AppButton>
        </div>
    </main>
</template>
