<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppSelect from '../../../Components/AppSelect.vue';
import AppSwitch from '../../../Components/AppSwitch.vue';

const props = defineProps({
    staff: { type: Object, required: true },
    tenant: { type: Object, required: true },
    isSelf: { type: Boolean, default: false },
});

const form = useForm({
    name: props.staff.name ?? '',
    email: props.staff.email ?? '',
    role: props.staff.role ?? 'tenant_staff',
    is_active: props.staff.is_active ?? true,
});

const roleOptions = [
    { value: 'tenant_staff', label: 'Staff (Akses Dashboard Aplikasi)' },
    { value: 'tenant_owner', label: 'Owner (Akses Penuh & Manajemen Staff)' },
];

function submit() {
    form.put(route('tenant.staff.update', props.staff.id));
}
</script>

<template>
    <Head :title="`Edit Staff - ${staff.name}`" />
    <AdminLayout>
        <div class="space-y-6">
            <div>
                <div class="flex items-center gap-2 text-xs text-on-surface-variant">
                    <Link :href="route('tenant.staff.index')" class="hover:text-primary">Manajemen Staff</Link>
                    <span>/</span>
                    <span>Edit Staff</span>
                </div>
                <h1 class="mt-1 text-3xl font-semibold text-primary">Edit Staff</h1>
                <p class="mt-1 text-on-surface-variant">
                    Perbarui data akun <span class="font-semibold text-on-surface">{{ staff.name }}</span>
                </p>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <AppCard>
                    <div class="grid gap-5 md:grid-cols-2">
                        <AppInput
                            v-model="form.name"
                            label="Nama Lengkap"
                            :error="form.errors.name"
                            required
                        />

                        <AppInput
                            v-model="form.email"
                            label="Email Pengguna"
                            type="email"
                            :error="form.errors.email"
                            required
                        />

                        <AppSelect
                            v-model="form.role"
                            label="Peran Akun"
                            :options="roleOptions"
                            :error="form.errors.role"
                            :disabled="isSelf"
                            required
                        />

                        <AppSwitch
                            v-model="form.is_active"
                            label="Status Akun Aktif"
                            :disabled="isSelf"
                            field
                        />

                        <div v-if="isSelf" class="md:col-span-2 rounded-md bg-secondary-container/20 p-3 text-xs text-on-surface-variant">
                            Catatan: Anda tidak dapat mengubah peran atau menonaktifkan akun yang sedang digunakan saat ini.
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <AppButton variant="secondary" type="button" :href="route('tenant.staff.index')">
                            Batal
                        </AppButton>
                        <AppButton type="submit" :loading="form.processing">
                            Simpan Perubahan
                        </AppButton>
                    </div>
                </AppCard>
            </form>
        </div>
    </AdminLayout>
</template>
