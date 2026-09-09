<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppIcon from '../../../Components/AppIcon.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppSwitch from '../../../Components/AppSwitch.vue';
import AppTextarea from '../../../Components/AppTextarea.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    tenantApplication: { type: Object, required: true },
});

const formatDateForInput = (dateStr) => {
    if (!dateStr) return '';
    return dateStr.substring(0, 10);
};

const form = useForm({
    label: props.tenantApplication.label ?? '',
    instance_url: props.tenantApplication.instance_url ?? '',
    expired_at: formatDateForInput(props.tenantApplication.expired_at),
    notes: props.tenantApplication.notes ?? '',
    is_active: props.tenantApplication.is_active ?? true,
});

function submit() {
    form.put(route('admin.tenants.applications.update', [props.tenant.id, props.tenantApplication.id]));
}
</script>

<template>
    <Head :title="`Edit Lisensi - ${tenantApplication.application?.name}`" />
    <AdminLayout>
        <div class="space-y-6">
            <div>
                <div class="flex items-center gap-2 text-xs text-on-surface-variant">
                    <Link :href="route('admin.tenants.index')" class="hover:text-primary">Tenants</Link>
                    <span>/</span>
                    <Link :href="route('admin.tenants.show', tenant.id)" class="hover:text-primary">{{ tenant.name }}</Link>
                    <span>/</span>
                    <Link :href="route('admin.tenants.applications.index', tenant.id)" class="hover:text-primary">Lisensi</Link>
                    <span>/</span>
                    <span>Edit</span>
                </div>
                <h1 class="mt-1 text-3xl font-semibold text-primary">Edit Lisensi Aplikasi</h1>
                <p class="mt-1 text-on-surface-variant">
                    Perbarui konfigurasi <span class="font-semibold text-on-surface">{{ tenantApplication.application?.name }}</span> untuk {{ tenant.name }}
                </p>
            </div>

            <form class="space-y-6" @submit.prevent="submit">
                <AppCard>
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2 rounded-md bg-surface-container-low p-4 flex items-center gap-3">
                            <div class="grid size-10 place-items-center rounded-md bg-primary-container text-primary">
                                <AppIcon :name="tenantApplication.application?.icon_path || 'widgets'" />
                            </div>
                            <div>
                                <p class="font-semibold text-on-surface">{{ tenantApplication.application?.name }}</p>
                                <p class="text-xs text-on-surface-variant">Slug: {{ tenantApplication.application?.slug }}</p>
                            </div>
                        </div>

                        <AppInput
                            v-model="form.label"
                            label="Label Instance (Opsional)"
                            placeholder="Contoh: Cabang Utama"
                            :error="form.errors.label"
                        />

                        <AppInput
                            v-model="form.instance_url"
                            label="URL Instance Aplikasi"
                            type="url"
                            :error="form.errors.instance_url"
                            required
                        />

                        <AppInput
                            v-model="form.expired_at"
                            label="Tanggal Kadaluarsa (Opsional)"
                            type="date"
                            :error="form.errors.expired_at"
                        />

                        <AppSwitch v-model="form.is_active" label="Status Lisensi Aktif" field />

                        <div class="md:col-span-2">
                            <AppTextarea
                                v-model="form.notes"
                                label="Catatan Lisensi (Opsional)"
                                :error="form.errors.notes"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <AppButton variant="secondary" type="button" :href="route('admin.tenants.applications.index', tenant.id)">
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
