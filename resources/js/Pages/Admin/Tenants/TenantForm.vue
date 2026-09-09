<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppSwitch from '../../../Components/AppSwitch.vue';
import AppTextarea from '../../../Components/AppTextarea.vue';

const props = defineProps({ tenant: { type: Object, default: null }, submitLabel: { type: String, default: 'Simpan' } });
const form = useForm({
    name: props.tenant?.name ?? '',
    slug: props.tenant?.slug ?? '',
    domain: props.tenant?.domain ?? '',
    email: props.tenant?.email ?? '',
    phone: props.tenant?.phone ?? '',
    address: props.tenant?.address ?? '',
    logo_path: props.tenant?.logo_path ?? '',
    is_active: props.tenant?.is_active ?? true,
});

function submit() {
    if (props.tenant) form.put(route('admin.tenants.update', props.tenant.id));
    else form.post(route('admin.tenants.store'));
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <AppCard>
            <div class="grid gap-5 md:grid-cols-2">
                <AppInput v-model="form.name" label="Nama" :error="form.errors.name" required />
                <AppInput v-model="form.slug" label="Slug" :error="form.errors.slug" required />
                <AppInput v-model="form.domain" label="Domain / Subdomain (Opsional)" placeholder="bumdesma.domain.test" :error="form.errors.domain" />
                <AppInput v-model="form.email" label="Email" type="email" :error="form.errors.email" required />
                <AppInput v-model="form.phone" label="Telepon" :error="form.errors.phone" />
                <div class="md:col-span-2">
                    <AppTextarea v-model="form.address" label="Alamat" :error="form.errors.address" />
                </div>
                <AppInput v-model="form.logo_path" label="Path logo" :error="form.errors.logo_path" />
                <AppSwitch v-model="form.is_active" label="Status" field />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <AppButton variant="secondary" type="button" @click="router.visit(props.tenant ? route('admin.tenants.index') : route('admin.tenants.create'))">Batal</AppButton>
                <AppButton type="submit" :loading="form.processing">{{ props.submitLabel }}</AppButton>
            </div>
        </AppCard>
    </form>
</template>
