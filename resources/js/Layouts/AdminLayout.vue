<script setup>
import { computed, ref } from 'vue';
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

const navigation = [
    { label: 'Dashboard', icon: 'space_dashboard', href: route('dashboard') },
    { label: 'Tenants', icon: 'apartment', href: route('admin.tenants.index'), exact: false },
    { label: 'Applications', icon: 'widgets', href: route('admin.applications.index'), exact: false },
];

function isActive(item) {
    return item.exact === false ? currentPath.value.startsWith(item.href) : currentPath.value === item.href;
}

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <div class="min-h-screen bg-surface font-sans text-on-surface">
        <button v-if="mobileMenuOpen" type="button" class="fixed inset-0 z-40 bg-primary/45 backdrop-blur-xs lg:hidden" aria-label="Tutup navigasi" @click="mobileMenuOpen = false" />
        <aside class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-primary py-6 transition-transform duration-300 lg:translate-x-0" :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="mb-8 px-6">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-primary-fixed-dim">Holding Portal</p>
                <p class="mt-1 text-lg font-bold text-on-primary">{{ appName }}</p>
            </div>
            <nav class="flex-1 space-y-1 px-3">
                <Link v-for="item in navigation" :key="item.label" :href="item.href" class="flex items-center gap-3 rounded-lg px-4 py-2.5 transition-colors" :class="isActive(item) ? 'bg-primary-container text-on-primary' : 'text-primary-fixed-dim hover:bg-primary-container hover:text-on-primary'" @click="mobileMenuOpen = false">
                    <AppIcon :name="item.icon" />
                    <span>{{ item.label }}</span>
                </Link>
            </nav>
            <div class="flex items-center gap-3 rounded-xl bg-primary-container/50 p-3 mx-4 border-t border-primary-container pt-4">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-primary-fixed text-sm font-bold text-primary">{{ user?.name?.charAt(0).toUpperCase() || 'A' }}</span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-on-primary">{{ user?.name || 'Admin' }}</p>
                    <p class="truncate text-xs text-primary-fixed-dim">{{ user?.tenant?.name || 'Superadmin' }}</p>
                </div>
                <AppIconButton name="logout" aria-label="Keluar" class="text-primary-fixed-dim hover:bg-on-primary/10 hover:text-on-primary" @click="logout" />
            </div>
        </aside>
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-outline-variant bg-surface px-4 lg:ml-64 lg:px-6">
            <div class="flex items-center gap-3">
                <AppIconButton name="menu" aria-label="Buka navigasi" class="lg:hidden" @click="mobileMenuOpen = true" />
                <p class="text-sm font-bold text-primary">Panel Superadmin</p>
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
