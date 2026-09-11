<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import AppIconButton from '@/Components/AppIconButton.vue';
import AppToast from '@/Components/AppToast.vue';
import { useTheme } from '@/Composables/useTheme';

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
});

const page = usePage();
const { current, toggleTheme } = useTheme();
const mobileMenuOpen = ref(false);

const appName = computed(() => page.props.appName || 'Holding Portal');
const user = computed(() => page.props.auth?.user);

const navLinks = [
    { label: 'Beranda', href: route('home'), exact: true },
    { label: 'Berita', href: route('public.posts'), exact: false },
    { label: 'Kontak', href: route('public.contact'), exact: true },
];

function isNavActive(link) {
    if (link.exact) {
        return page.url === '/' || page.url === link.href;
    }
    return page.url.startsWith(link.href);
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-surface font-sans text-on-surface antialiased transition-colors duration-200">
        <!-- Top Navigation -->
        <header class="sticky top-0 z-40 border-b border-outline-variant/60 bg-surface-container-lowest/90 backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3.5 sm:px-6 lg:px-8">
                <!-- Logo & Brand -->
                <Link :href="route('home')" class="flex items-center gap-3 group">
                    <div class="grid size-10 place-items-center rounded-xl bg-gradient-primary-soft text-on-primary shadow-sm group-hover:scale-105 transition-transform">
                        <AppIcon name="account_balance" class="text-xl" />
                    </div>
                    <div>
                        <p class="text-base font-bold leading-tight text-on-surface tracking-tight">{{ appName }}</p>
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-primary">Holding Management</p>
                    </div>
                </Link>

                <!-- Desktop Nav -->
                <nav class="hidden md:flex items-center gap-1">
                    <Link
                        v-for="link in navLinks"
                        :key="link.label"
                        :href="link.href"
                        class="rounded-full px-4 py-2 text-sm font-semibold transition-colors"
                        :class="isNavActive(link) ? 'bg-primary/10 text-primary dark:bg-primary/20' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface'"
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <!-- Right Actions -->
                <div class="flex items-center gap-2">
                    <AppIconButton
                        :name="current === 'dark' ? 'light_mode' : 'dark_mode'"
                        :aria-label="current === 'dark' ? 'Gunakan tema terang' : 'Gunakan tema gelap'"
                        @click="toggleTheme"
                    />

                    <Link
                        v-if="user"
                        :href="route('dashboard')"
                        class="hidden sm:inline-flex min-h-10 items-center gap-2 rounded-full bg-primary px-5 text-sm font-semibold text-on-primary shadow-sm hover:bg-primary-deep transition"
                    >
                        <AppIcon name="dashboard" />
                        Dashboard
                    </Link>
                    <Link
                        v-else
                        :href="route('login')"
                        class="hidden sm:inline-flex min-h-10 items-center gap-2 rounded-full bg-primary px-5 text-sm font-semibold text-on-primary shadow-sm hover:bg-primary-deep transition"
                    >
                        <AppIcon name="login" />
                        Masuk Portal
                    </Link>

                    <!-- Mobile Menu Button -->
                    <AppIconButton
                        name="menu"
                        aria-label="Buka navigasi"
                        class="md:hidden"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    />
                </div>
            </div>

            <!-- Mobile Dropdown Menu -->
            <div v-if="mobileMenuOpen" class="border-t border-outline-variant/60 bg-surface-container-lowest px-4 py-4 md:hidden">
                <nav class="space-y-2">
                    <Link
                        v-for="link in navLinks"
                        :key="link.label"
                        :href="link.href"
                        class="block rounded-lg px-4 py-2.5 text-sm font-semibold transition"
                        :class="isNavActive(link) ? 'bg-primary/10 text-primary' : 'text-on-surface-variant hover:bg-surface-container'"
                        @click="mobileMenuOpen = false"
                    >
                        {{ link.label }}
                    </Link>
                    <div class="pt-2 border-t border-outline-variant/40">
                        <Link
                            v-if="user"
                            :href="route('dashboard')"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-semibold text-on-primary shadow"
                            @click="mobileMenuOpen = false"
                        >
                            <AppIcon name="dashboard" />
                            Dashboard ({{ user.name }})
                        </Link>
                        <Link
                            v-else
                            :href="route('login')"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-2.5 text-sm font-semibold text-on-primary shadow"
                            @click="mobileMenuOpen = false"
                        >
                            <AppIcon name="login" />
                            Masuk Portal
                        </Link>
                    </div>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Public Footer -->
        <footer class="border-t border-outline-variant/60 bg-surface-container-lowest py-12 transition-colors">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Brand column -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="grid size-10 place-items-center rounded-xl bg-gradient-primary-soft text-on-primary shadow-sm">
                                <AppIcon name="account_balance" class="text-xl" />
                            </div>
                            <p class="text-lg font-bold text-on-surface">{{ appName }}</p>
                        </div>
                        <p class="text-xs leading-relaxed text-on-surface-variant">
                            {{ settings?.about_short || 'Portal holding terpadu untuk monitoring ekosistem bisnis, tata kelola aplikasi unit usaha, dan pelaporan keuangan terintegrasi.' }}
                        </p>
                    </div>

                    <!-- Navigation column -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-primary">Navigasi</h3>
                        <ul class="mt-4 space-y-2 text-sm text-on-surface-variant">
                            <li><Link :href="route('home')" class="hover:text-primary transition">Beranda</Link></li>
                            <li><Link :href="route('public.posts')" class="hover:text-primary transition">Berita &amp; Informasi</Link></li>
                            <li><Link :href="route('public.contact')" class="hover:text-primary transition">Hubungi Kami</Link></li>
                            <li><Link :href="route('login')" class="hover:text-primary transition">Masuk Sistem</Link></li>
                        </ul>
                    </div>

                    <!-- Contact column -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-primary">Kontak</h3>
                        <ul class="mt-4 space-y-2.5 text-xs text-on-surface-variant">
                            <li v-if="settings?.contact_address" class="flex items-start gap-2">
                                <AppIcon name="place" class="text-base text-primary shrink-0 mt-0.5" />
                                <span>{{ settings.contact_address }}</span>
                            </li>
                            <li v-if="settings?.contact_phone" class="flex items-center gap-2">
                                <AppIcon name="call" class="text-base text-primary shrink-0" />
                                <span>{{ settings.contact_phone }}</span>
                            </li>
                            <li v-if="settings?.contact_email" class="flex items-center gap-2">
                                <AppIcon name="mail" class="text-base text-primary shrink-0" />
                                <span>{{ settings.contact_email }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Social / Media column -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-primary">Media Sosial</h3>
                        <div class="mt-4 flex gap-2">
                            <a
                                v-if="settings?.social?.facebook"
                                :href="settings.social.facebook"
                                target="_blank"
                                rel="noopener"
                                class="grid size-9 place-items-center rounded-full bg-surface-container text-on-surface-variant transition hover:bg-primary hover:text-on-primary"
                                aria-label="Facebook"
                            >
                                <span class="font-bold text-sm">f</span>
                            </a>
                            <a
                                v-if="settings?.social?.instagram"
                                :href="settings.social.instagram"
                                target="_blank"
                                rel="noopener"
                                class="grid size-9 place-items-center rounded-full bg-surface-container text-on-surface-variant transition hover:bg-primary hover:text-on-primary"
                                aria-label="Instagram"
                            >
                                <AppIcon name="photo_camera" class="text-base" />
                            </a>
                            <a
                                v-if="settings?.social?.youtube"
                                :href="settings.social.youtube"
                                target="_blank"
                                rel="noopener"
                                class="grid size-9 place-items-center rounded-full bg-surface-container text-on-surface-variant transition hover:bg-primary hover:text-on-primary"
                                aria-label="YouTube"
                            >
                                <AppIcon name="smart_display" class="text-base" />
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-10 border-t border-outline-variant/60 pt-6 text-center text-xs text-on-surface-variant">
                    <p v-if="settings?.footer_note">{{ settings.footer_note }}</p>
                    <p v-else>© {{ new Date().getFullYear() }} {{ appName }}. Seluruh hak cipta dilindungi.</p>
                </div>
            </div>
        </footer>
        <AppToast />
    </div>
</template>
