<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppSelect from '../../../Components/AppSelect.vue';
import AppSwitch from '../../../Components/AppSwitch.vue';

defineProps({
    tenant: { type: Object, required: true },
});

const form = useForm({
    name: '',
    email: '',
    role: 'tenant_staff',
    password: '',
    is_active: true,
});

const roleOptions = [
    { value: 'tenant_staff', label: 'Staff (Akses Dashboard Aplikasi)' },
    { value: 'tenant_owner', label: 'Owner (Akses Penuh & Manajemen Staff)' },
];

function submit() {
    form.post(route('tenant.staff.store'));
}
</script>

<template>
    <Head title="Tambah Staff Baru" />
    <AdminLayout>
        <div class="space-y-6">
            <div>
                <div class="flex items-center gap-2 text-xs text-on-surface-variant">
                    <Link :href="route('tenant.staff.index')" class="hover:text-primary">Manajemen Staff</Link>
                    <span>/</span>
                    <span>Tambah Staff</span>
                </div>
                <h1 class="mt-1 text-3xl font-semibold text-primary">Tambah Staff Baru</h1>
                <p class="mt-1 text-on-surface-variant">
                    Tambahkan pengguna baru untuk unit usaha {{ tenant.name }}
                </p>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <AppCard>
                    <div class="grid gap-5 md:grid-cols-2">
                        <AppInput
                            v-model="form.name"
                            label="Nama Lengkap"
                            placeholder="Nama pengguna"
                            :error="form.errors.name"
                            required
                        />

                        <AppInput
                            v-model="form.email"
                            label="Email Pengguna"
                            type="email"
                            placeholder="staff@unit.test"
                            :error="form.errors.email"
                            required
                        />

                        <AppSelect
                            v-model="form.role"
                            label="Peran Akun"
                            :options="roleOptions"
                            :error="form.errors.role"
                            required
                        />

                        <AppInput
                            v-model="form.password"
                            label="Password Awal"
                            type="password"
                            placeholder="Minimal 8 karakter"
                            :error="form.errors.password"
                            required
                        />

                        <div class="md:col-span-2">
                            <AppSwitch v-model="form.is_active" label="Status Akun Aktif" field />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <AppButton variant="secondary" type="button" :href="route('tenant.staff.index')">
                            Batal
                        </AppButton>
                        <AppButton type="submit" :loading="form.processing">
                            Tambah Staff
                        </AppButton>
                    </div>
                </AppCard>
            </form>
        </div>
    </AdminLayout>
</template>
