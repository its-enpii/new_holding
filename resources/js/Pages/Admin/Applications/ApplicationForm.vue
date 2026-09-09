<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppInput from '../../../Components/AppInput.vue';
import AppSwitch from '../../../Components/AppSwitch.vue';
import AppTextarea from '../../../Components/AppTextarea.vue';

const props = defineProps({ application: { type: Object, default: null }, submitLabel: { type: String, default: 'Simpan' } });
const form = useForm({
    name: props.application?.name ?? '',
    slug: props.application?.slug ?? '',
    description: props.application?.description ?? '',
    icon_path: props.application?.icon_path ?? '',
    base_url: props.application?.base_url ?? '',
    has_financial_report: props.application?.has_financial_report ?? true,
    is_active: props.application?.is_active ?? true,
});

function submit() {
    if (props.application) form.put(route('admin.applications.update', props.application.id));
    else form.post(route('admin.applications.store'));
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <AppCard>
            <div class="grid gap-5 md:grid-cols-2">
                <AppInput v-model="form.name" label="Nama" :error="form.errors.name" required />
                <AppInput v-model="form.slug" label="Slug" :error="form.errors.slug" required />
                <AppInput v-model="form.base_url" label="Base URL" type="url" :error="form.errors.base_url" required />
                <AppInput v-model="form.icon_path" label="Path icon" :error="form.errors.icon_path" />
                <div class="md:col-span-2">
                    <AppTextarea v-model="form.description" label="Deskripsi" :error="form.errors.description" />
                </div>
                <AppSwitch v-model="form.has_financial_report" label="Laporan keuangan" field />
                <AppSwitch v-model="form.is_active" label="Status" field />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <AppButton variant="secondary" type="button" @click="router.visit(props.application ? route('admin.applications.index') : route('admin.applications.create'))">Batal</AppButton>
                <AppButton type="submit" :loading="form.processing">{{ props.submitLabel }}</AppButton>
            </div>
        </AppCard>
    </form>
</template>
