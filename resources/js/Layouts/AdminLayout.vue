<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppConfirmDialog from '../Components/AppConfirmDialog.vue';
import AppIcon from '../Components/AppIcon.vue';
import AppIconButton from '../Components/AppIconButton.vue';
import AppToast from '../Components/AppToast.vue';
import { useTheme } from '../Composables/useTheme';

const page = usePage();
const mobileMenuOpen = ref(false);
const { current, toggleTheme } = useTheme();
const appName = computed(() => page.props.appName || 'Holding');
const user = computed(() => page.props.auth?.user);
const currentPath = computed(() => page.url);

const websiteOpen = ref(page.url.startsWith('/website'));

watch(currentPath, (newPath) => {
    if (newPath.startsWith('/website')) {
        websiteOpen.value = true;
    }
});

const navigation = computed(() => {
    if (user.value?.role === 'superadmin') {
        return [
            { label: 'Dashboard', icon: 'space_dashboard', href: route('dashboard') },
            { label: 'Tenants', icon: 'apartment', href: route('admin.tenants.index'), exact: false },
            { label: 'Master Aplikasi', icon: 'widgets', href: route('admin.applications.index'), exact: false },
            { label: 'Laporan', icon: 'monitoring', href: route('admin.reports.index'), exact: false },
            { label: 'Log Aktivitas', icon: 'history', href: route('admin.activity-logs.index'), exact: false },
        ];
    }

    if (user.value?.role === 'tenant_owner') {
        return [
            { label: 'Aplikasi Saya', icon: 'widgets', href: route('dashboard') },
            { label: 'Laporan', icon: 'monitoring', href: route('tenant.reports.index'), exact: false },
            { label: 'Manajemen Staff', icon: 'group', href: route('tenant.staff.index'), exact: false },
        ];
    }

    return [
        { label: 'Aplikasi Saya', icon: 'widgets', href: route('dashboard') },
        { label: 'Laporan', icon: 'monitoring', href: route('tenant.reports.index'), exact: false },
    ];
});

const websiteNavigation = [
    { label: 'Berita', icon: 'newspaper', href: route('website.posts.index'), exact: false },
    { label: 'Halaman', icon: 'description', href: route('website.pages.index'), exact: false },
    { label: 'Pengaturan', icon: 'settings', href: route('website.settings.edit'), exact: false },
    { label: 'Pesan', icon: 'mail', href: route('website.messages.index'), exact: false },
];

const isWebsiteActive = computed(() => currentPath.value.startsWith('/website'));

const panelTitle = computed(() => {
    if (user.value?.role === 'superadmin') return 'Panel Superadmin';
    if (user.value?.role === 'tenant_owner') return `Panel Owner — ${user.value?.tenant?.name || 'Unit Usaha'}`;
    return `Portal Staff — ${user.value?.tenant?.name || 'Unit Usaha'}`;
});

function isActive(item) {
    return item.exact === false ? currentPath.value.startsWith(item.href) : currentPath.value === item.href;
}

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <div class="min-h-screen bg-surface font-sans text-on-surface">
        <button v-if="mobileMenuOpen" type="button" class="fixed inset-0 z-40 bg-primary-deep/60 backdrop-blur-xs lg:hidden" aria-label="Tutup navigasi" @click="mobileMenuOpen = false" />
        <aside class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-gradient-primary-soft py-6 transition-transform duration-300 lg:translate-x-0 dark:border-r dark:border-outline-variant/40 dark:bg-gradient-to-b dark:from-surface-container-lowest dark:to-surface-container dark:shadow-[inset_-1px_0_0_rgb(139_127_245/15%)]" :class="[mobileMenuOpen ? 'translate-x-0' : '-translate-x-full']">
            <div class="mb-6 px-6">
                <div class="mb-3 grid size-10 place-items-center rounded-md bg-white/15 text-on-primary">
                    <AppIcon name="account_balance" />
                </div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-fixed-dim">Holding Portal</p>
                <p class="mt-1 text-lg font-medium text-on-primary">{{ appName }}</p>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto px-3">
                <Link v-for="item in navigation" :key="item.label" :href="item.href" class="relative flex items-center gap-3 rounded-md px-4 py-2.5 transition-colors" :class="isActive(item) ? 'bg-white/15 text-on-primary font-medium' : 'text-on-primary-container hover:bg-white/10'" @click="mobileMenuOpen = false">
                    <span v-if="isActive(item)" class="absolute inset-y-1 left-0 w-[3px] rounded-sm bg-tertiary-fixed" aria-hidden="true" />
                    <AppIcon :name="item.icon" />
                    <span>{{ item.label }}</span>
                </Link>

                <div v-if="user?.role === 'superadmin'" class="pt-2">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-md px-4 py-2.5 text-left transition-colors"
                        :class="isWebsiteActive ? 'bg-white/15 text-on-primary font-medium' : 'text-on-primary-container hover:bg-white/10'"
                        @click="websiteOpen = !websiteOpen"
                    >
                        <span class="flex items-center gap-3">
                            <AppIcon name="language" />
                            <span>Website</span>
                        </span>
                        <AppIcon :name="websiteOpen ? 'expand_less' : 'expand_more'" class="text-sm transition-transform" />
                    </button>
                    <div v-show="websiteOpen" class="mt-1 space-y-1 pl-4">
                        <Link
                            v-for="sub in websiteNavigation"
                            :key="sub.label"
                            :href="sub.href"
                            class="relative flex items-center gap-3 rounded-md px-4 py-2 text-sm transition-colors"
                            :class="isActive(sub) ? 'bg-white/20 text-on-primary font-semibold' : 'text-on-primary-container/80 hover:bg-white/10 hover:text-on-primary'"
                            @click="mobileMenuOpen = false"
                        >
                            <span v-if="isActive(sub)" class="absolute inset-y-1 left-0 w-[3px] rounded-sm bg-tertiary-fixed" aria-hidden="true" />
                            <AppIcon :name="sub.icon" class="text-base" />
                            <span>{{ sub.label }}</span>
                        </Link>
                    </div>
                </div>
            </nav>
            <div class="mx-4 flex items-center gap-3 rounded-md border-t border-white/15 bg-white/10 p-3 pt-4">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-white/15 text-sm font-semibold text-on-primary">{{ user?.name?.charAt(0).toUpperCase() || 'A' }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-on-primary">{{ user?.name || 'Pengguna' }}</p>
                    <p class="truncate text-xs text-on-primary-container">{{ user?.tenant?.name || 'Superadmin' }}</p>
                </div>
                <AppIconButton name="logout" aria-label="Keluar" class="text-on-primary-container hover:bg-white/10 hover:text-on-primary" @click="logout" />
            </div>
        </aside>
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-outline-variant/50 bg-gradient-to-r from-surface-container-lowest/90 to-surface-container-low/90 px-4 backdrop-blur-sm lg:ml-64 lg:px-6">
            <div class="flex items-center gap-3">
                <AppIconButton name="menu" aria-label="Buka navigasi" class="lg:hidden" @click="mobileMenuOpen = true" />
                <p class="text-sm font-medium text-primary">{{ panelTitle }}</p>
            </div>
            <AppIconButton :name="current === 'dark' ? 'light_mode' : 'dark_mode'" :aria-label="current === 'dark' ? 'Gunakan tema terang' : 'Gunakan tema gelap'" @click="toggleTheme" />
        </header>
        <main class="p-4 sm:p-6 lg:ml-64 lg:p-8">
            <slot />
        </main>
        <AppConfirmDialog />
        <AppToast />
    </div>
</template>
