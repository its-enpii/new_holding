<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppBadge from '../../../Components/AppBadge.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppIcon from '../../../Components/AppIcon.vue';
import AppIconButton from '../../../Components/AppIconButton.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppModal from '../../../Components/AppModal.vue';
import SmartDataTable from '../../../Components/SmartDataTable.vue';
import { useConfirm } from '../../../Composables/useConfirm';

const props = defineProps({
    staff: { type: Object, required: true },
    tenant: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const page = usePage();
const { confirm } = useConfirm();
const currentUserId = page.props.auth?.user?.id;
const deleting = ref(null);

const resetModalOpen = ref(false);
const resetTargetUser = ref(null);
const resetForm = useForm({
    password: '',
    password_confirmation: '',
});

const columns = [
    { key: 'name', label: 'Nama Pengguna', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'role', label: 'Peran', sortable: true, class: 'w-32' },
    { key: 'is_active', label: 'Status', sortable: true, class: 'w-28' },
    { key: 'last_login_at', label: 'Login Terakhir', sortable: true },
];

function formatDate(dateStr) {
    if (!dateStr) return 'Belum pernah';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function openResetModal(user) {
    resetTargetUser.value = user;
    resetForm.reset();
    resetModalOpen.value = true;
}

function submitResetPassword() {
    if (!resetTargetUser.value) return;
    resetForm.post(route('tenant.staff.reset-password', resetTargetUser.value.id), {
        onSuccess: () => {
            resetModalOpen.value = false;
            resetForm.reset();
            resetTargetUser.value = null;
        },
    });
}

function toggle(user) {
    if (user.id === currentUserId) return;
    router.patch(route('tenant.staff.toggle', user.id));
}

function destroy(user) {
    if (user.id === currentUserId) return;
    confirm({
        title: 'Hapus staff',
        message: `Akun staff ${user.name} (${user.email}) akan dihapus permanen. Lanjutkan?`,
        confirmLabel: 'Hapus Akun',
    }).then((confirmed) => {
        if (!confirmed) return;
        deleting.value = user.id;
        router.delete(route('tenant.staff.destroy', user.id), {
            onFinish: () => { deleting.value = null; },
        });
    });
}
</script>

<template>
    <Head title="Manajemen Staff" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-semibold text-primary">Manajemen Staff</h1>
                    <p class="mt-1 text-on-surface-variant">
                        Kelola akun pengguna internal untuk unit usaha <span class="font-semibold text-on-surface">{{ tenant.name }}</span>
                    </p>
                </div>
                <AppButton variant="primary" :href="route('tenant.staff.create')">
                    <AppIcon name="person_add" class="mr-1" />
                    Tambah Staff
                </AppButton>
            </div>

            <AppCard :padded="false">
                <div class="p-6">
                    <SmartDataTable
                        :rows="props.staff.data"
                        :columns="columns"
                        :pagination="props.staff"
                        :url="route('tenant.staff.index')"
                        :search="props.filters.search"
                        :per-page="props.filters.per_page"
                        :sort="props.filters.sort"
                        :direction="props.filters.direction"
                        empty-title="Belum ada staff terdaftar"
                    >
                        <template #cell-name="{ row }">
                            <div class="flex items-center gap-3">
                                <span class="grid size-8 shrink-0 place-items-center rounded-full bg-primary-container text-xs font-semibold text-primary">
                                    {{ row.name?.charAt(0).toUpperCase() }}
                                </span>
                                <div>
                                    <p class="font-semibold text-on-surface flex items-center gap-1.5">
                                        {{ row.name }}
                                        <span v-if="row.id === currentUserId" class="text-[11px] rounded bg-secondary-container/40 px-1.5 py-0.5 text-secondary font-normal">
                                            (Anda)
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </template>

                        <template #cell-role="{ row }">
                            <AppBadge :tone="row.role === 'tenant_owner' ? 'info' : 'neutral'">
                                {{ row.role === 'tenant_owner' ? 'Owner' : 'Staff' }}
                            </AppBadge>
                        </template>

                        <template #cell-is_active="{ row }">
                            <AppBadge :tone="row.is_active ? 'success' : 'neutral'">
                                {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                            </AppBadge>
                        </template>

                        <template #cell-last_login_at="{ row }">
                            <span class="text-xs text-on-surface-variant">{{ formatDate(row.last_login_at) }}</span>
                        </template>

                        <template #actions="{ row }">
                            <div class="flex justify-end gap-1.5">
                                <AppIconButton
                                    name="lock_reset"
                                    :aria-label="`Reset password ${row.name}`"
                                    @click="openResetModal(row)"
                                />
                                <AppIconButton
                                    name="edit"
                                    :aria-label="`Edit ${row.name}`"
                                    :href="route('tenant.staff.edit', row.id)"
                                />
                                <AppIconButton
                                    v-if="row.id !== currentUserId"
                                    :name="row.is_active ? 'toggle_off' : 'toggle_on'"
                                    :aria-label="row.is_active ? 'Nonaktifkan' : 'Aktifkan'"
                                    @click="toggle(row)"
                                />
                                <AppIconButton
                                    v-if="row.id !== currentUserId"
                                    name="delete"
                                    tone="danger"
                                    :aria-label="`Hapus ${row.name}`"
                                    :loading="deleting === row.id"
                                    @click="destroy(row)"
                                />
                            </div>
                        </template>
                    </SmartDataTable>
                </div>
            </AppCard>

            <!-- Reset Password Modal -->
            <AppModal :open="resetModalOpen" :title="`Reset Password - ${resetTargetUser?.name || 'Staff'}`" @close="resetModalOpen = false">
                <form class="space-y-4" @submit.prevent="submitResetPassword">
                    <p class="text-xs text-on-surface-variant">
                        Masukkan password baru untuk akun <span class="font-semibold text-on-surface">{{ resetTargetUser?.email }}</span>.
                    </p>
                    <AppInput
                        v-model="resetForm.password"
                        label="Password Baru"
                        type="password"
                        placeholder="Minimal 8 karakter"
                        :error="resetForm.errors.password"
                        required
                    />
                    <AppInput
                        v-model="resetForm.password_confirmation"
                        label="Konfirmasi Password Baru"
                        type="password"
                        placeholder="Ulangi password baru"
                        :error="resetForm.errors.password_confirmation"
                        required
                    />
                    <div class="mt-6 flex justify-end gap-3">
                        <AppButton variant="secondary" type="button" @click="resetModalOpen = false">
                            Batal
                        </AppButton>
                        <AppButton type="submit" :loading="resetForm.processing">
                            Simpan Password Baru
                        </AppButton>
                    </div>
                </form>
            </AppModal>
        </div>
    </AdminLayout>
</template>
