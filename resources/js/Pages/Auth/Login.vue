<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppButton from '../../Components/AppButton.vue';
import AppCheckbox from '../../Components/AppCheckbox.vue';
import AppIcon from '../../Components/AppIcon.vue';
import AppInput from '../../Components/AppInput.vue';
import { useTheme } from '../../Composables/useTheme';

defineProps({ status: { type: String, default: null } });
const { current, toggleTheme } = useTheme();
const showPassword = ref(false);
const form = useForm({ email: '', password: '', remember: false });

function submit() {
    form.post(route('login'), { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head title="Masuk — Holding" />
    <main class="grid min-h-screen bg-surface font-sans text-on-surface lg:grid-cols-2">
        <section class="relative hidden flex-col justify-between bg-gradient-to-br from-primary-deep via-primary to-primary-container p-12 text-on-primary lg:flex">
            <div class="flex items-center gap-3">
                <span class="grid size-12 place-items-center rounded-lg bg-primary-fixed text-primary"><AppIcon name="apartment" class="text-3xl" /></span>
                <div>
                    <p class="text-xl font-semibold text-on-primary">Holding Portal</p>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary-fixed-dim">BUMDesma Induk</p>
                </div>
            </div>
            <div class="space-y-6">
                <p class="text-3xl font-semibold leading-tight text-on-primary">Satu portal untuk mengelola aplikasi seluruh unit usaha.</p>
                <p class="max-w-md text-on-primary/80">Kelola tenant, registry aplikasi, dan quick access secara terpusat.</p>
            </div>
            <p class="text-sm text-primary-fixed-dim">Phase 1 Foundation</p>
        </section>
        <section class="flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-md space-y-8">
                <div class="flex items-center justify-between">
                    <div class="lg:hidden">
                        <p class="text-2xl font-semibold text-primary">Holding Portal</p>
                        <p class="text-sm text-on-surface-variant">BUMDesma Induk</p>
                    </div>
                    <AppIconButton :name="current === 'dark' ? 'light_mode' : 'dark_mode'" :aria-label="current === 'dark' ? 'Gunakan tema terang' : 'Gunakan tema gelap'" @click="toggleTheme" />
                </div>
                <div>
                    <h1 class="text-3xl font-semibold text-primary">Masuk</h1>
                    <p class="mt-2 text-sm text-on-surface-variant">Gunakan akun yang diberikan administrator holding.</p>
                </div>
                <p v-if="status" class="rounded-lg bg-secondary-container px-4 py-3 text-sm text-secondary">{{ status }}</p>
                <form class="space-y-5 rounded-lg border border-outline-variant/60 bg-surface-container-lowest p-6 shadow-sm sm:p-8" novalidate @submit.prevent="submit">
                    <AppInput v-model="form.email" label="Email" type="email" icon="mail" required autocomplete="username" :error="form.errors.email" />
                    <AppInput v-model="form.password" label="Password" :type="showPassword ? 'text' : 'password'" icon="lock" required autocomplete="current-password" :error="form.errors.password">
                        <template #trailing>
                            <AppIconButton :name="showPassword ? 'visibility_off' : 'visibility'" :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'" @click="showPassword = !showPassword" />
                        </template>
                    </AppInput>
                    <AppCheckbox v-model="form.remember" label="Ingat saya" variant="inline" />
                    <AppButton type="submit" class="w-full" :loading="form.processing">Masuk</AppButton>
                </form>
            </div>
        </section>
    </main>
</template>
