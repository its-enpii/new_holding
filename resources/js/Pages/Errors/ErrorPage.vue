<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppBadge from '../../Components/AppBadge.vue';
import AppButton from '../../Components/AppButton.vue';
import AppIcon from '../../Components/AppIcon.vue';

const props = defineProps({
    status: { type: Number, required: true },
});

const message = computed(() => ({
    401: {
        title: 'Autentikasi Diperlukan',
        description: 'Sesi akses Anda belum aktif atau tidak dapat diverifikasi.',
        icon: 'lock_person',
        tone: 'warning',
        steps: ['Masuk ulang menggunakan akun Holding Anda', 'Periksa apakah akun Anda masih memiliki akses aktif'],
        action: 'Masuk Kembali',
    },
    403: {
        title: 'Akses Ditolak',
        description: 'Akun Anda tidak memiliki izin untuk membuka modul ini.',
        icon: 'no_encryption',
        tone: 'error',
        steps: ['Kembali ke dashboard utama', 'Hubungi superadmin jika Anda memerlukan akses modul ini'],
        action: 'Kembali ke Dashboard',
    },
    404: {
        title: 'Halaman Tidak Ditemukan',
        description: 'Tautan yang Anda buka mungkin sudah berubah, dihapus, atau salah ketik.',
        icon: 'explore_off',
        tone: 'info',
        steps: ['Periksa kembali alamat halaman', 'Gunakan dashboard untuk navigasi ke modul yang tepat'],
        action: 'Kembali ke Beranda',
    },
    419: {
        title: 'Sesi Telah Berakhir',
        description: 'Sesi keamanan Anda sudah tidak valid karena halaman dibiarkan terlalu lama.',
        icon: 'history_toggle_off',
        tone: 'warning',
        steps: ['Muat ulang halaman sebelumnya', 'Masuk ulang jika diminta oleh sistem'],
        action: 'Kembali ke Beranda',
    },
    429: {
        title: 'Terlalu Banyak Permintaan',
        description: 'Sistem sedang membatasi permintaan Anda untuk menjaga stabilitas portal holding.',
        icon: 'hourglass_top',
        tone: 'warning',
        steps: ['Tunggu beberapa saat sebelum mencoba lagi', 'Hindari melakukan banyak aksi secara bersamaan'],
        action: 'Kembali ke Dashboard',
    },
    500: {
        title: 'Kesalahan Server',
        description: 'Sistem gagal memproses permintaan Anda karena gangguan internal.',
        icon: 'dns',
        tone: 'error',
        steps: ['Coba beberapa saat lagi', 'Laporkan ke tim teknis jika masalah berlanjut'],
        action: 'Kembali ke Dashboard',
    },
    503: {
        title: 'Layanan Sedang Dalam Pemeliharaan',
        description: 'Portal holding sementara tidak tersedia dan akan segera kembali normal.',
        icon: 'construction',
        tone: 'warning',
        steps: ['Tunggu proses pemeliharaan selesai', 'Hubungi administrator jika Anda butuh akses darurat'],
        action: 'Kembali ke Beranda',
    },
})[props.status] ?? {
    title: 'Terjadi Kesalahan',
    description: 'Sistem gagal memproses permintaan Anda.',
    icon: 'report_problem',
    tone: 'info',
    steps: ['Gunakan tombol tindakan di bawah untuk kembali ke halaman utama'],
    action: 'Kembali ke Beranda',
});

const actionHref = computed(() => (message.value.action === 'Masuk Kembali' ? route('login') : returnUrl.value));

const returnUrl = computed(() => (usePage().props.auth?.user ? route('dashboard') : route('home')));
</script>

<template>
    <main class="grid min-h-screen place-items-center bg-gradient-surface-soft bg-brand-glow px-4 py-10 font-sans text-on-surface sm:px-6">
        <div class="w-full max-w-3xl overflow-hidden rounded-3xl border border-outline-variant/60 bg-surface-container-lowest shadow-2xl">
            <div class="bg-gradient-primary-soft px-6 py-10 text-center sm:px-12">
                <span class="mx-auto grid size-20 place-items-center rounded-3xl bg-white/15 text-on-primary shadow-inner">
                    <AppIcon :name="message.icon" class="text-5xl" />
                </span>
                <p class="mt-6 text-5xl font-bold tracking-tight text-on-primary sm:text-6xl">{{ status }}</p>
                <h1 class="mt-3 text-2xl font-semibold text-on-primary sm:text-3xl">{{ message.title }}</h1>
                <p class="mx-auto mt-3 max-w-xl text-sm text-on-primary-container sm:text-base">{{ message.description }}</p>
            </div>
            <div class="px-6 py-8 sm:px-12 sm:py-10">
                <div class="flex flex-col items-center justify-between gap-4 border-b border-outline-variant/50 pb-6 sm:flex-row">
                    <div class="flex items-center gap-3">
                        <AppBadge tone="primary-soft">HTTP {{ status }}</AppBadge>
                        <AppBadge :tone="message.tone === 'info' ? 'info-soft' : `${message.tone}-soft`">{{ message.title }}</AppBadge>
                    </div>
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-fixed-dim">Holding Portal</span>
                </div>
                <ol class="mt-6 space-y-3">
                    <li v-for="step in message.steps" :key="step" class="flex items-start gap-3 rounded-2xl bg-surface-container-low/70 p-4 text-sm text-on-surface-variant">
                        <AppIcon name="check_circle" tone="success" container-shape="pill" class="mt-0.5" />
                        <span>{{ step }}</span>
                    </li>
                </ol>
                <div class="mt-8 flex justify-center">
                    <AppButton :href="actionHref">{{ message.action }}</AppButton>
                </div>
            </div>
        </div>
    </main>
</template>
