<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppDatePicker from '../../../Components/AppDatePicker.vue';
import AppIcon from '../../../Components/AppIcon.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppSelect from '../../../Components/AppSelect.vue';
import AppSwitch from '../../../Components/AppSwitch.vue';
import AppTextarea from '../../../Components/AppTextarea.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    applications: { type: Array, required: true },
});

const form = useForm({
    application_id: props.applications.length > 0 ? props.applications[0].id : '',
    label: '',
    instance_url: '',
    expired_at: '',
    notes: '',
    is_active: true,
});

const applicationOptions = computed(() =>
    props.applications.map((app) => ({
        value: app.id,
        label: `${app.name} (${app.slug})`,
    }))
);

function onAppChange(appId) {
    const selected = props.applications.find((a) => a.id === Number(appId));
    if (selected && !form.instance_url && selected.base_url) {
        form.instance_url = selected.base_url;
    }
}

function submit() {
    form.post(route('admin.tenants.applications.store', props.tenant.id));
}
</script>

<template>
    <Head :title="`Assign Aplikasi - ${tenant.name}`" />
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
                    <span>Assign</span>
                </div>
                <h1 class="mt-1 text-3xl font-semibold text-primary">Assign Aplikasi</h1>
                <p class="mt-1 text-on-surface-variant">Hubungkan aplikasi subsidiary ke tenant {{ tenant.name }}</p>
            </div>

            <div v-if="applications.length === 0" class="rounded-md border border-outline-variant bg-surface-container-low p-6 text-center">
                <p class="text-on-surface-variant">Semua aplikasi aktif telah di-assign ke tenant ini, atau belum ada aplikasi master aktif.</p>
                <div class="mt-4">
                    <AppButton variant="secondary" :href="route('admin.tenants.applications.index', tenant.id)">
                        Kembali
                    </AppButton>
                </div>
            </div>

            <form v-else class="space-y-6" @submit.prevent="submit">
                <AppCard>
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <AppSelect
                                v-model="form.application_id"
                                label="Pilih Aplikasi Master"
                                :options="applicationOptions"
                                :error="form.errors.application_id"
                                required
                                @update:model-value="onAppChange"
                            />
                        </div>

                        <AppInput
                            v-model="form.label"
                            label="Label Instance (Opsional)"
                            placeholder="Contoh: Cabang Utama / Unit Simpan Pinjam"
                            :error="form.errors.label"
                        />

                        <AppInput
                            v-model="form.instance_url"
                            label="URL Instance Aplikasi"
                            placeholder="https://app.tenant.test"
                            type="url"
                            :error="form.errors.instance_url"
                            required
                        />

                        <AppDatePicker
                            v-model="form.expired_at"
                            label="Tanggal Kadaluarsa (Opsional)"
                            :error="form.errors.expired_at"
                        />

                        <AppSwitch v-model="form.is_active" label="Status Lisensi Aktif" field />

                        <div class="md:col-span-2">
                            <AppTextarea
                                v-model="form.notes"
                                label="Catatan Lisensi (Opsional)"
                                placeholder="Keterangan kontrak, PIC, atau catatan operasional"
                                :error="form.errors.notes"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <AppButton variant="secondary" type="button" :href="route('admin.tenants.applications.index', tenant.id)">
                            Batal
                        </AppButton>
                        <AppButton type="submit" :loading="form.processing">
                            Assign Aplikasi
                        </AppButton>
                    </div>
                </AppCard>
            </form>
        </div>
    </AdminLayout>
</template>
