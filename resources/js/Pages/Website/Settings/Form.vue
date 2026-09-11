<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import AppButton from '@/Components/AppButton.vue';
import AppCard from '@/Components/AppCard.vue';
import AppFileUpload from '@/Components/AppFileUpload.vue';
import AppInput from '@/Components/AppInput.vue';
import AppTextarea from '@/Components/AppTextarea.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    settings: { type: Object, required: true },
    heroImageUrl: { type: String, default: null },
});

const form = useForm({
    hero_tagline: props.settings.hero_tagline ?? '',
    hero_description: props.settings.hero_description ?? '',
    about_short: props.settings.about_short ?? '',
    facebook_url: props.settings.facebook_url ?? '',
    instagram_url: props.settings.instagram_url ?? '',
    youtube_url: props.settings.youtube_url ?? '',
    contact_phone: props.settings.contact_phone ?? '',
    contact_email: props.settings.contact_email ?? '',
    contact_address: props.settings.contact_address ?? '',
    footer_note: props.settings.footer_note ?? '',
    hero_image: null,
    remove_hero_image: false,
    _method: 'PUT',
});

watch(() => form.hero_image, (file) => {
    if (file) {
        form.remove_hero_image = false;
    }
});

function submit() {
    form.post(route('website.settings.update'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.hero_image = null;
            form.remove_hero_image = false;
        },
    });
}
</script>

<template>
    <Head title="Pengaturan Situs" />

    <AdminLayout>
        <div class="space-y-6">
            <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-primary">Pengaturan Situs</h1>
                    <p class="mt-1 text-on-surface-variant">
                        Tampilan halaman depan, kanal media sosial, dan kontak pada situs publik holding.
                    </p>
                </div>
            </header>

            <form class="space-y-6" @submit.prevent="submit">
                <AppCard padded>
                    <h2 class="text-base font-bold text-primary">Hero Halaman Depan</h2>
                    <div class="mt-4 space-y-4">
                        <AppInput
                            v-model="form.hero_tagline"
                            label="Tagline"
                            icon="campaign"
                            placeholder="Contoh: Portal Terpadu & Ekosistem Digital Multi-Usaha"
                            :error="form.errors.hero_tagline"
                        />
                        <AppTextarea
                            v-model="form.hero_description"
                            label="Deskripsi Hero"
                            icon="notes"
                            placeholder="Kalimat pengantar yang tampil di hero landing page"
                            :error="form.errors.hero_description"
                        />
                        <AppFileUpload
                            v-model="form.hero_image"
                            label="Gambar Hero"
                            accept="image/png,image/jpeg,image/webp"
                            :error="form.errors.hero_image"
                        />
                        <div v-if="heroImageUrl && !form.remove_hero_image" class="flex items-center gap-4">
                            <img :src="heroImageUrl" alt="Gambar hero saat ini" class="h-24 w-40 rounded-xl border border-outline-variant object-cover">
                            <AppButton variant="secondary" size="compact" type="button" @click="form.remove_hero_image = true">
                                Hapus gambar
                            </AppButton>
                        </div>
                        <p v-if="form.remove_hero_image" class="ml-1 text-sm text-tertiary">
                            Gambar akan dihapus saat disimpan.
                            <button type="button" class="font-semibold underline" @click="form.remove_hero_image = false">Batalkan</button>
                        </p>
                    </div>
                </AppCard>

                <AppCard padded>
                    <h2 class="text-base font-bold text-primary">Tentang Singkat</h2>
                    <div class="mt-4">
                        <AppTextarea
                            v-model="form.about_short"
                            label="Ringkasan Profil"
                            icon="info"
                            placeholder="Paragraf singkat tentang holding dan ekosistem usaha"
                            :error="form.errors.about_short"
                        />
                    </div>
                </AppCard>

                <AppCard padded>
                    <h2 class="text-base font-bold text-primary">Media Sosial</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <AppInput
                            v-model="form.facebook_url"
                            label="Facebook"
                            icon="share"
                            placeholder="https://facebook.com/..."
                            :error="form.errors.facebook_url"
                        />
                        <AppInput
                            v-model="form.instagram_url"
                            label="Instagram"
                            icon="photo_camera"
                            placeholder="https://instagram.com/..."
                            :error="form.errors.instagram_url"
                        />
                        <AppInput
                            v-model="form.youtube_url"
                            label="YouTube"
                            icon="smart_display"
                            placeholder="https://youtube.com/..."
                            :error="form.errors.youtube_url"
                        />
                    </div>
                </AppCard>

                <AppCard padded>
                    <h2 class="text-base font-bold text-primary">Kontak &amp; Footer</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <AppInput
                            v-model="form.contact_phone"
                            label="Nomor Telepon"
                            icon="call"
                            placeholder="+62 812-xxxx-xxxx"
                            :error="form.errors.contact_phone"
                        />
                        <AppInput
                            v-model="form.contact_email"
                            label="Email Kontak"
                            icon="mail"
                            placeholder="kontak@holding.local"
                            :error="form.errors.contact_email"
                        />
                        <AppTextarea
                            v-model="form.contact_address"
                            label="Alamat Kantor"
                            icon="place"
                            placeholder="Alamat lengkap kantor pusat holding"
                            class="sm:col-span-2"
                            :error="form.errors.contact_address"
                        />
                        <AppInput
                            v-model="form.footer_note"
                            label="Catatan Footer"
                            icon="copyright"
                            class="sm:col-span-2"
                            placeholder="Contoh: © 2026 Holding Portal. Seluruh hak cipta dilindungi."
                            :error="form.errors.footer_note"
                        />
                    </div>
                </AppCard>

                <div class="flex justify-end gap-3 pt-2">
                    <AppButton type="submit" :loading="form.processing" icon="save">
                        Simpan Pengaturan
                    </AppButton>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
