<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppCard from '@/Components/AppCard.vue';
import AppIcon from '@/Components/AppIcon.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    settings: { type: Object, required: true },
    applications: { type: Array, default: () => [] },
    latestPosts: { type: Array, default: () => [] },
});

function formatDateTime(value) {
    if (!value) return '';
    return new Date(value).toLocaleDateString('id-ID', { dateStyle: 'long' });
}

const features = [
    {
        title: 'Tata Kelola Multi-Unit',
        description: 'Registrasi dan manajemen unit bisnis (tenant) dalam satu wadah holding dengan isolasi kredensial dan konfigurasi independen.',
        icon: 'apartment',
    },
    {
        title: 'Pemberian & Monitoring Lisensi',
        description: 'Manajemen hak akses aplikasi anak usaha, validasi token API, dan monitoring masa aktif lisensi secara berkala.',
        icon: 'verified_user',
    },
    {
        title: 'Laporan Keuangan Konsolidasi',
        description: 'Agregasi laporan laba rugi, neraca, dan kinerja keuangan antar unit bisnis secara cepat melalui integrasi API terpusat.',
        icon: 'monitoring',
    },
    {
        title: 'Audit & Log Aktivitas',
        description: 'Pencatatan riwayat aksi manajerial, perubahan lisensi, dan aktivitas pengguna untuk memastikan transparansi tata kelola.',
        icon: 'history',
    },
];
</script>

<template>
    <Head title="Portal Terpadu Holding Multi-Usaha">
        <meta head-key="description" name="description" :content="settings.hero_description ?? settings.about_short ?? 'Portal terpadu holding untuk tata kelola unit bisnis, monitoring lisensi aplikasi, dan pelaporan keuangan konsolidasi.'" />
        <meta head-key="og:title" property="og:title" content="Portal Terpadu Holding Multi-Usaha" />
        <meta head-key="og:description" property="og:description" :content="settings.hero_description ?? settings.about_short ?? 'Platform tata kelola ekosistem unit bisnis.'" />
    </Head>

    <PublicLayout :settings="settings">
        <!-- Hero Section -->
        <section class="relative overflow-hidden bg-gradient-primary-soft text-on-primary py-20 lg:py-28">
            <div class="pointer-events-none absolute -top-24 -right-24 size-96 rounded-full bg-white/10 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-32 -left-16 size-80 rounded-full bg-secondary/20 blur-3xl" />

            <div class="relative mx-auto flex max-w-7xl flex-col items-center gap-6 px-4 text-center sm:px-6 lg:px-8">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-bold tracking-widest uppercase text-primary-fixed-dim backdrop-blur-xs">
                    <AppIcon name="hub" />
                    Ekosistem Digital Holding
                </span>

                <h1 class="max-w-4xl text-3xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl text-on-primary leading-tight">
                    {{ settings.hero_tagline || 'Portal Terpadu & Ekosistem Digital Multi-Usaha' }}
                </h1>

                <p class="max-w-2xl text-base sm:text-lg leading-relaxed text-on-primary-container">
                    {{ settings.hero_description || 'Platform holding terintegrasi untuk pengelolaan unit bisnis, monitoring lisensi aplikasi, dan pelaporan keuangan konsolidasi secara transparan.' }}
                </p>

                <div class="mt-4 flex flex-wrap items-center justify-center gap-3">
                    <a href="#aplikasi" class="inline-flex min-h-12 items-center gap-2 rounded-full bg-white px-7 text-sm font-bold text-primary shadow-lg hover:bg-slate-100 transition">
                        <AppIcon name="widgets" />
                        Katalog Aplikasi
                    </a>
                    <Link :href="route('public.posts')" class="inline-flex min-h-12 items-center gap-2 rounded-full bg-white/15 px-6 text-sm font-bold text-on-primary backdrop-blur-xs hover:bg-white/25 transition">
                        <AppIcon name="newspaper" />
                        Berita Terkini
                    </Link>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-16 sm:py-20 bg-surface">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-14">
                    <span class="text-xs font-bold uppercase tracking-wider text-primary">Fitur Unggulan</span>
                    <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold text-on-surface">Tata Kelola &amp; Integrasi Holistik</h2>
                    <p class="mt-3 text-sm sm:text-base text-on-surface-variant">
                        Mendukung percepatan pertumbuhan dan tata kelola unit usaha yang akuntabel melalui otomasi platform.
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="group rounded-2xl border border-outline-variant/60 bg-surface-container-lowest p-6 shadow-sm hover:shadow-md hover:border-primary/40 transition duration-200"
                    >
                        <div class="mb-4 grid size-12 place-items-center rounded-xl bg-primary/10 text-primary group-hover:bg-primary group-hover:text-on-primary transition">
                            <AppIcon :name="feature.icon" class="text-2xl" />
                        </div>
                        <h3 class="text-base font-bold text-on-surface">{{ feature.title }}</h3>
                        <p class="mt-2 text-xs leading-relaxed text-on-surface-variant">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Application Catalog Section -->
        <section id="aplikasi" class="py-16 sm:py-20 bg-surface-container-low border-y border-outline-variant/40">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-12">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-primary">Katalog Aplikasi</span>
                        <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold text-on-surface">Aplikasi Terintegrasi</h2>
                        <p class="mt-2 text-sm text-on-surface-variant">
                            Daftar aplikasi operasional yang tersedia untuk unit usaha di dalam ekosistem holding.
                        </p>
                    </div>
                    <Link :href="route('login')">
                        <AppButton variant="secondary" size="compact" icon="login">Masuk ke Unit Anda</AppButton>
                    </Link>
                </div>

                <div v-if="applications.length === 0" class="rounded-2xl border border-outline-variant/60 bg-surface-container-lowest p-8 text-center">
                    <p class="text-sm text-on-surface-variant">Belum ada aplikasi yang terdaftar aktif di katalog.</p>
                </div>

                <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="app in applications"
                        :key="app.id"
                        class="flex flex-col justify-between rounded-2xl border border-outline-variant/60 bg-surface-container-lowest p-6 shadow-sm hover:shadow-md transition"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <div class="grid size-12 place-items-center rounded-xl bg-primary/10 text-primary">
                                    <AppIcon :name="app.icon_path || 'widgets'" class="text-2xl" />
                                </div>
                                <AppBadge v-if="app.has_financial_report" tone="success">
                                    Laporan Keuangan
                                </AppBadge>
                            </div>
                            <h3 class="text-lg font-bold text-on-surface">{{ app.name }}</h3>
                            <p class="mt-2 text-xs leading-relaxed text-on-surface-variant">
                                {{ app.description || 'Aplikasi pendukung operasional unit bisnis dalam ekosistem portal holding.' }}
                            </p>
                        </div>

                        <div class="mt-6 pt-4 border-t border-outline-variant/40 flex items-center justify-between">
                            <span class="text-xs text-on-surface-variant font-mono truncate max-w-[180px]">{{ app.base_url }}</span>
                            <a
                                :href="app.base_url"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline"
                            >
                                Buka
                                <AppIcon name="open_in_new" class="text-sm" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Latest News Section -->
        <section v-if="latestPosts.length > 0" class="py-16 sm:py-20 bg-surface">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-12">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-primary">Kabar &amp; Warta</span>
                        <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold text-on-surface">Berita &amp; Informasi Terbaru</h2>
                    </div>
                    <Link :href="route('public.posts')">
                        <AppButton variant="ghost" size="compact" icon="arrow_forward">Semua Berita</AppButton>
                    </Link>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="post in latestPosts"
                        :key="post.slug"
                        class="flex flex-col overflow-hidden rounded-2xl border border-outline-variant/60 bg-surface-container-lowest shadow-sm hover:shadow-md transition"
                    >
                        <div v-if="post.cover_image_url" class="aspect-video w-full overflow-hidden bg-surface-container">
                            <img :src="post.cover_image_url" :alt="post.title" class="size-full object-cover">
                        </div>
                        <div v-else class="flex aspect-video w-full items-center justify-center bg-primary-container/20 text-primary">
                            <AppIcon name="newspaper" class="text-4xl" />
                        </div>

                        <div class="flex flex-1 flex-col justify-between p-5">
                            <div>
                                <p class="text-xs font-medium text-on-surface-variant">{{ formatDateTime(post.published_at) }}</p>
                                <h3 class="mt-2 text-base font-bold text-on-surface line-clamp-2">
                                    <Link :href="route('public.post', post.slug)" class="hover:text-primary transition">
                                        {{ post.title }}
                                    </Link>
                                </h3>
                                <p v-if="post.excerpt" class="mt-2 text-xs leading-relaxed text-on-surface-variant line-clamp-3">
                                    {{ post.excerpt }}
                                </p>
                            </div>
                            <div class="mt-4 pt-3 border-t border-outline-variant/40">
                                <Link :href="route('public.post', post.slug)" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                                    Baca selengkapnya
                                    <AppIcon name="arrow_forward" class="text-sm" />
                                </Link>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- About & Contact CTA -->
        <section class="py-16 bg-gradient-to-r from-primary-deep to-primary text-on-primary">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-on-primary">Mulai Kelola Unit Usaha Anda</h2>
                <p class="mt-3 max-w-2xl mx-auto text-sm sm:text-base text-on-primary-container">
                    Hubungi tim kami untuk konsultasi integrasi sistem, pendaftaran unit usaha baru, atau pertanyaan teknis lainnya.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    <Link :href="route('public.contact')" class="inline-flex min-h-12 items-center gap-2 rounded-full bg-white px-7 text-sm font-bold text-primary shadow-lg hover:bg-slate-100 transition">
                        <AppIcon name="mail" />
                        Hubungi Kami
                    </Link>
                    <Link :href="route('login')" class="inline-flex min-h-12 items-center gap-2 rounded-full bg-white/15 px-6 text-sm font-bold text-on-primary backdrop-blur-xs hover:bg-white/25 transition">
                        <AppIcon name="login" />
                        Masuk Portal
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
