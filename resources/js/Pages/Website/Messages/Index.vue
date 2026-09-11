<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppBadge from '@/Components/AppBadge.vue';
import AppButton from '@/Components/AppButton.vue';
import AppCard from '@/Components/AppCard.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import AppIcon from '@/Components/AppIcon.vue';
import AppInput from '@/Components/AppInput.vue';
import AppModal from '@/Components/AppModal.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    messages: { type: Object, required: true },
    search: { type: String, default: '' },
    unreadCount: { type: Number, default: 0 },
});

const { confirm: confirmAction } = useConfirm();
const searchQuery = ref(props.search);
const selectedMessage = ref(null);
const showDetailModal = ref(false);

function doSearch() {
    router.get(route('website.messages.index'), { q: searchQuery.value }, { preserveState: true, replace: true });
}

function openDetail(msg) {
    selectedMessage.value = msg;
    showDetailModal.value = true;
    if (!msg.is_read) {
        markRead(msg);
    }
}

function markRead(msg) {
    router.post(route('website.messages.mark-read', msg.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            msg.is_read = true;
        },
    });
}

async function destroy(msg) {
    if (!await confirmAction({
        title: 'Hapus Pesan',
        message: `Hapus pesan dari "${msg.name}"?`,
    })) return;

    router.delete(route('website.messages.destroy', msg.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedMessage.value?.id === msg.id) {
                showDetailModal.value = false;
            }
        },
    });
}
</script>

<template>
    <Head title="Kotak Masuk Pesan" />
    <AdminLayout>
        <div class="space-y-6">
            <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-primary">Kotak Masuk Pesan</h1>
                    <p class="mt-1 text-on-surface-variant">
                        Pesan dan pertanyaan yang dikirimkan oleh pengunjung melalui formulir kontak publik.
                    </p>
                </div>
                <div v-if="unreadCount > 0" class="flex items-center gap-2">
                    <AppBadge tone="warning">{{ unreadCount }} Pesan Belum Dibaca</AppBadge>
                </div>
            </header>

            <AppCard padded>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <form class="flex w-full max-w-md items-center gap-2" @submit.prevent="doSearch">
                        <AppInput
                            v-model="searchQuery"
                            placeholder="Cari pengirim, subjek, atau pesan..."
                            icon="search"
                            class="flex-1"
                        />
                        <AppButton type="submit" variant="secondary" size="compact">Cari</AppButton>
                    </form>
                </div>

                <div v-if="messages.data.length === 0" class="py-8">
                    <AppEmptyState
                        icon="inbox"
                        title="Belum ada pesan"
                        description="Pesan yang dikirimkan melalui halaman kontak publik akan muncul di sini."
                    />
                </div>

                <div v-else class="mt-5 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-outline-variant text-xs font-bold uppercase tracking-wider text-on-surface-variant">
                            <tr>
                                <th class="px-4 py-3">Pengirim</th>
                                <th class="px-4 py-3">Subjek &amp; Pesan</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/60">
                            <tr
                                v-for="row in messages.data"
                                :key="row.id"
                                class="transition-colors hover:bg-surface-container-low/60"
                                :class="!row.is_read ? 'bg-primary-container/5 font-medium' : ''"
                            >
                                <td class="px-4 py-3.5">
                                    <p class="font-semibold text-primary">{{ row.name }}</p>
                                    <p v-if="row.email || row.phone" class="text-xs text-on-surface-variant">
                                        {{ [row.email, row.phone].filter(Boolean).join(' · ') }}
                                    </p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <p class="max-w-xs truncate font-medium sm:max-w-md">{{ row.subject || '(Tanpa subjek)' }}</p>
                                    <p class="max-w-xs truncate text-xs text-on-surface-variant sm:max-w-md">{{ row.message }}</p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <AppBadge :tone="row.is_read ? 'neutral' : 'warning'">
                                        {{ row.is_read ? 'Dibaca' : 'Baru' }}
                                    </AppBadge>
                                </td>
                                <td class="px-4 py-3.5 text-xs text-on-surface-variant whitespace-nowrap">
                                    {{ row.created_at ? new Date(row.created_at).toLocaleString('id-ID') : '—' }}
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <AppButton variant="ghost" size="compact" icon="visibility" @click="openDetail(row)">
                                            Lihat
                                        </AppButton>
                                        <AppButton
                                            v-if="!row.is_read"
                                            variant="ghost"
                                            size="compact"
                                            icon="mark_email_read"
                                            @click="markRead(row)"
                                        >
                                            Baca
                                        </AppButton>
                                        <AppButton
                                            variant="danger"
                                            size="compact"
                                            icon="delete"
                                            @click="destroy(row)"
                                        >
                                            Hapus
                                        </AppButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="messages.links?.length > 3" class="mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-outline-variant pt-4">
                        <p class="text-xs text-on-surface-variant">
                            Menampilkan {{ messages.from ?? 0 }} - {{ messages.to ?? 0 }} dari total {{ messages.total ?? 0 }} pesan
                        </p>
                        <div class="flex flex-wrap gap-1">
                            <Link
                                v-for="link in messages.links"
                                :key="link.label"
                                :href="link.url ?? '#'"
                                class="rounded-md px-3 py-1 text-xs font-semibold"
                                :class="[
                                    link.active ? 'bg-primary text-on-primary' : 'bg-surface-container-low text-on-surface hover:bg-surface-container',
                                    !link.url && 'pointer-events-none opacity-40'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </AppCard>

            <AppModal v-model="showDetailModal" :title="selectedMessage ? `Pesan dari ${selectedMessage.name}` : 'Detail Pesan'" size="md">
                <div v-if="selectedMessage" class="space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-3 rounded-lg bg-surface-container-low p-3 text-xs">
                        <div>
                            <p class="font-bold uppercase tracking-wider text-on-surface-variant">Email</p>
                            <p class="mt-0.5 font-medium text-primary">{{ selectedMessage.email || '—' }}</p>
                        </div>
                        <div>
                            <p class="font-bold uppercase tracking-wider text-on-surface-variant">Telepon</p>
                            <p class="mt-0.5 font-medium text-primary">{{ selectedMessage.phone || '—' }}</p>
                        </div>
                    </div>
                    <div v-if="selectedMessage.subject">
                        <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Subjek</p>
                        <p class="mt-1 font-semibold text-primary">{{ selectedMessage.subject }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Isi Pesan</p>
                        <div class="mt-1 whitespace-pre-wrap rounded-lg border border-outline-variant bg-surface-container-lowest p-3.5 leading-relaxed text-on-surface">
                            {{ selectedMessage.message }}
                        </div>
                    </div>
                    <p class="text-xs text-on-surface-variant">
                        Diterima pada: {{ selectedMessage.created_at ? new Date(selectedMessage.created_at).toLocaleString('id-ID') : '—' }}
                    </p>
                </div>
                <template #footer>
                    <div class="flex justify-between w-full">
                        <AppButton
                            variant="danger"
                            size="compact"
                            icon="delete"
                            @click="destroy(selectedMessage)"
                        >
                            Hapus
                        </AppButton>
                        <AppButton variant="secondary" @click="showDetailModal = false">Tutup</AppButton>
                    </div>
                </template>
            </AppModal>
        </div>
    </AdminLayout>
</template>
