<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppButton from '@/Components/AppButton.vue';
import AppCard from '@/Components/AppCard.vue';
import AppDatePicker from '@/Components/AppDatePicker.vue';
import AppFileUpload from '@/Components/AppFileUpload.vue';
import AppInput from '@/Components/AppInput.vue';
import AppRichEditor from '@/Components/AppRichEditor.vue';
import AppTextarea from '@/Components/AppTextarea.vue';
import SmartSelect from '@/Components/SmartSelect.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    post: { type: Object, default: null },
});

const editing = Boolean(props.post);

const form = useForm({
    title: props.post?.title ?? '',
    slug: props.post?.slug ?? '',
    excerpt: props.post?.excerpt ?? '',
    content: props.post?.content ?? '',
    status: props.post?.status ?? 'draft',
    published_at: props.post?.published_at ? props.post.published_at.slice(0, 10) : '',
    meta_description: props.post?.meta_description ?? '',
    cover_image: null,
    _method: editing ? 'PUT' : 'POST',
});

const statusOptions = [
    { value: 'draft', label: 'Draf (belum tampil)' },
    { value: 'published', label: 'Terbit' },
];

function submit() {
    if (editing) {
        form.post(route('website.posts.update', props.post.id), {
            forceFormData: true,
        });
    } else {
        form.post(route('website.posts.store'), {
            forceFormData: true,
        });
    }
}
</script>

<template>
    <Head :title="editing ? 'Edit Berita' : 'Tulis Berita'" />
    <AdminLayout>
        <div class="space-y-6">
            <header class="mb-2">
                <Link :href="route('website.posts.index')" class="text-sm font-semibold text-primary hover:underline">
                    ← Kembali ke daftar berita
                </Link>
                <h1 class="mt-3 text-2xl font-bold text-primary">{{ editing ? 'Edit Berita' : 'Tulis Berita' }}</h1>
                <p class="mt-1 text-on-surface-variant">{{ editing ? 'Perbarui isi berita lalu simpan.' : 'Isi detail berita yang akan tampil di situs publik.' }}</p>
            </header>

            <AppCard padded>
                <form class="space-y-6" @submit.prevent="submit">
                    <section class="space-y-4">
                        <h2 class="font-semibold text-primary">Konten</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <AppInput v-model="form.title" label="Judul Berita" icon="title" required :error="form.errors.title" />
                            <AppInput
                                v-model="form.slug"
                                label="Slug (opsional)"
                                icon="link"
                                hint="Kosongkan untuk dibuat otomatis dari judul."
                                :error="form.errors.slug"
                            />
                        </div>
                        <div class="space-y-2">
                            <label class="ml-1 block text-sm font-bold uppercase tracking-wider text-primary">Isi Berita</label>
                            <AppRichEditor v-model="form.content" placeholder="Tulis isi berita…" />
                            <p v-if="form.errors.content" class="mt-1 text-sm text-error">{{ form.errors.content }}</p>
                        </div>
                    </section>

                    <section class="space-y-4 border-t border-outline-variant pt-5">
                        <h2 class="font-semibold text-primary">Ringkasan &amp; Gambar Sampul</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <AppTextarea
                                v-model="form.excerpt"
                                label="Ringkasan (excerpt)"
                                placeholder="Ringkasan singkat yang tampil di daftar berita…"
                                :error="form.errors.excerpt"
                            />
                            <div class="space-y-3">
                                <AppFileUpload
                                    v-model="form.cover_image"
                                    label="Gambar Sampul"
                                    accept="image/png,image/jpeg,image/webp"
                                    hint="PNG / JPG / WebP · Maks 2 MB"
                                    :error="form.errors.cover_image"
                                />
                                <div v-if="post?.cover_image_url" class="flex items-center gap-3">
                                    <img :src="post.cover_image_url" alt="Cover saat ini" class="h-16 w-24 rounded-lg border border-outline-variant object-cover">
                                    <Link :href="route('website.posts.remove-cover', post.id)" method="delete" as="button" class="text-xs font-semibold text-error hover:underline">
                                        Hapus gambar sampul
                                    </Link>
                                </div>
                            </div>
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
                        <Link :href="route('website.posts.index')">
                            <AppButton variant="secondary">Batal</AppButton>
                        </Link>
                        <AppButton type="submit" :loading="form.processing" icon="save">Simpan</AppButton>
                    </div>
                </form>
            </AppCard>
        </div>
    </AdminLayout>
</template>
