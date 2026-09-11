<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppCard from '@/Components/AppCard.vue';
import SmartDataTable from '@/Components/SmartDataTable.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    posts: { type: Object, required: true },
    search: { type: String, default: '' },
    perPage: { type: Number, default: 15 },
    sort: { type: String, default: 'published_at' },
    direction: { type: String, default: 'desc' },
});

const { confirm: confirmAction } = useConfirm();

const columns = [
    { key: 'title', label: 'Judul', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'published_at', label: 'Tanggal Terbit', sortable: true },
];

function formatDateTime(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' });
}

async function destroy(post) {
    if (!await confirmAction({
        title: 'Hapus Berita',
        message: `Pindahkan berita "${post.title}" ke sampah?`,
    })) return;
    router.delete(route('website.posts.destroy', post.id), { preserveScroll: true });
}

async function restore(post) {
    if (!await confirmAction({
        title: 'Pulihkan Berita',
        message: `Pulihkan berita "${post.title}"?`,
    })) return;
    router.post(route('website.posts.restore', post.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Berita Website" />
    <AdminLayout>
        <div class="space-y-6">
            <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-primary">Berita Website</h1>
                    <p class="mt-1 text-on-surface-variant">Kelola berita yang tampil di situs publik holding.</p>
                </div>
                <div>
                    <Link :href="route('website.posts.create')">
                        <AppButton icon="add">Tulis Berita</AppButton>
                    </Link>
                </div>
            </header>

            <AppCard :padded="false">
                <div class="p-6">
                    <SmartDataTable
                        :rows="posts.data"
                        :columns="columns"
                        :pagination="posts"
                        :url="route('website.posts.index')"
                        :search="search"
                        :per-page="perPage"
                        :sort="sort"
                        :direction="direction"
                        search-label="Cari berita"
                        search-placeholder="Judul atau slug"
                        empty-title="Belum ada berita"
                        empty-description="Tulis berita pertama untuk ditampilkan di situs publik."
                    >
                        <template #cell-title="{ row }">
                            <Link :href="route('website.posts.edit', row.id)" class="font-semibold text-primary hover:underline">
                                {{ row.title }}
                            </Link>
                            <span class="block text-xs text-on-surface-variant">/{{ row.slug }}</span>
                        </template>
                        <template #cell-status="{ row }">
                            <AppBadge :tone="row.status === 'published' ? 'success' : 'neutral'">
                                {{ row.status === 'published' ? 'Terbit' : 'Draf' }}
                            </AppBadge>
                            <span v-if="row.deleted_at" class="ml-1 inline-flex">
                                <AppBadge tone="error">Terhapus</AppBadge>
                            </span>
                        </template>
                        <template #cell-published_at="{ row }">
                            {{ formatDateTime(row.published_at) }}
                        </template>
                        <template #actions="{ row }">
                            <div class="flex justify-end gap-1">
                                <template v-if="row.deleted_at">
                                    <AppButton variant="ghost" size="compact" icon="restore" aria-label="Pulihkan berita" @click="restore(row)">
                                        Pulihkan
                                    </AppButton>
                                </template>
                                <template v-else>
                                    <Link :href="route('website.posts.edit', row.id)">
                                        <AppButton variant="ghost" size="compact" icon="edit" aria-label="Edit berita">
                                            Edit
                                        </AppButton>
                                    </Link>
                                    <AppButton variant="danger" size="compact" icon="delete" aria-label="Hapus berita" @click="destroy(row)">
                                        Hapus
                                    </AppButton>
                                </template>
                            </div>
                        </template>
                    </SmartDataTable>
                </div>
            </AppCard>
        </div>
    </AdminLayout>
</template>
