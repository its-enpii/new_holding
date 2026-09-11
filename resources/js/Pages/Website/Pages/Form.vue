<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppButton from '@/Components/AppButton.vue';
import AppCard from '@/Components/AppCard.vue';
import AppDatePicker from '@/Components/AppDatePicker.vue';
import AppInput from '@/Components/AppInput.vue';
import AppRichEditor from '@/Components/AppRichEditor.vue';
import SmartSelect from '@/Components/SmartSelect.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    page: { type: Object, default: null },
});

const editing = Boolean(props.page);

const form = useForm({
    title: props.page?.title ?? '',
    slug: props.page?.slug ?? '',
    content: props.page?.content ?? '',
    status: props.page?.status ?? 'draft',
    published_at: props.page?.published_at ? props.page.published_at.slice(0, 10) : '',
    meta_description: props.page?.meta_description ?? '',
});

const statusOptions = [
    { value: 'draft', label: 'Draf (belum tampil)' },
    { value: 'published', label: 'Terbit' },
];

function submit() {
    if (editing) {
        form.put(route('website.pages.update', props.page.id));
    } else {
        form.post(route('website.pages.store'));
    }
}
</script>

<template>
    <Head :title="editing ? 'Edit Halaman' : 'Tambah Halaman'" />
    <AdminLayout>
        <div class="space-y-6">
            <header class="mb-2">
                <Link :href="route('website.pages.index')" class="text-sm font-semibold text-primary hover:underline">
                    ← Kembali ke daftar halaman
                </Link>
                <h1 class="mt-3 text-2xl font-bold text-primary">{{ editing ? 'Edit Halaman' : 'Tambah Halaman' }}</h1>
                <p class="mt-1 text-on-surface-variant">{{ editing ? 'Perbarui isi halaman lalu simpan.' : 'Isi konten halaman statis yang akan tampil pada URL /p/{slug}.' }}</p>
            </header>

            <AppCard padded>
                <form class="space-y-6" @submit.prevent="submit">
                    <section class="space-y-4">
                        <h2 class="font-semibold text-primary">Konten</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <AppInput v-model="form.title" label="Judul Halaman" icon="title" required :error="form.errors.title" />
                            <AppInput
                                v-model="form.slug"
                                label="Slug (opsional)"
                                icon="link"
                                hint="Kosongkan untuk dibuat otomatis dari judul. Hasil URL: /p/{slug}"
                                :error="form.errors.slug"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="ml-1 block text-sm font-bold uppercase tracking-wider text-primary">Isi Halaman</label>
                            <AppRichEditor v-model="form.content" placeholder="Tulis isi halaman…" />
                            <p v-if="form.errors.content" class="mt-1 text-sm text-error">{{ form.errors.content }}</p>
                        </div>
                    </section>

                    <section class="space-y-4 border-t border-outline-variant pt-5">
                        <h2 class="font-semibold text-primary">Publikasi &amp; SEO</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <SmartSelect
                                v-model="form.status"
                                label="Status"
                                :options="statusOptions"
                                :error="form.errors.status"
                            />
                            <AppDatePicker
                                v-model="form.published_at"
                                label="Tanggal Terbit"
                                icon="event"
                                hint="Kosongkan saat mempublikasikan untuk memakai waktu sekarang."
                                :error="form.errors.published_at"
                            />
                            <AppInput
                                v-model="form.meta_description"
                                label="Meta Deskripsi"
                                icon="manage_search"
                                class="sm:col-span-2"
                                hint="Deskripsi singkat untuk mesin pencari (maks. 255 karakter)."
                                :error="form.errors.meta_description"
                            />
                        </div>
                    </section>

                    <div class="flex justify-end gap-3 border-t border-outline-variant pt-5">
                        <Link :href="route('website.pages.index')">
                            <AppButton variant="secondary">Batal</AppButton>
                        </Link>
                        <AppButton type="submit" :loading="form.processing" icon="save">Simpan</AppButton>
                    </div>
                </form>
            </AppCard>
        </div>
    </AdminLayout>
</template>
