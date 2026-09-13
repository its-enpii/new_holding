<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppButton from './AppButton.vue';
import AppFileUpload from './AppFileUpload.vue';
import AppModal from './AppModal.vue';

const props = defineProps({
    exportUrl: { type: String, required: true },
    importUrl: { type: String, required: true },
    columns: { type: Array, required: true },
    title: { type: String, default: 'Impor CSV' },
    hint: { type: String, default: 'Unggah file CSV (Excel-compatible). Baris pertama harus header.' },
});

const open = ref(false);
const form = useForm({ file: null });

function exportCsv() {
    window.location.assign(props.exportUrl);
}

function openImport() {
    form.reset();
    form.clearErrors();
    open.value = true;
}

function onFileChange(file) {
    form.file = file instanceof File ? file : null;
}

function submitImport() {
    form.post(props.importUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <AppButton type="button" variant="secondary" icon="download" size="compact" @click="exportCsv">Export CSV</AppButton>
        <AppButton type="button" variant="secondary" icon="upload" size="compact" @click="openImport">Import CSV</AppButton>
    </div>

    <AppModal v-model="open" :title="title" size="md">
        <p class="mb-4 text-sm text-on-surface-variant">{{ hint }}</p>
        <div class="mb-4 rounded-xl border border-outline-variant bg-surface-container-low p-4">
            <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Kolom header</p>
            <p class="mt-2 font-mono text-sm text-primary">{{ columns.join(';') }}</p>
        </div>
        <AppFileUpload
            :model-value="form.file"
            label="File CSV"
            accept=".csv,text/csv,application/vnd.ms-excel"
            :error="form.errors.file"
            @update:model-value="onFileChange"
        />
        <template #footer>
            <AppButton variant="secondary" :disabled="form.processing" @click="open = false">Batal</AppButton>
            <AppButton :loading="form.processing" :disabled="!form.file" icon="upload" @click="submitImport">Impor</AppButton>
        </template>
    </AppModal>
</template>
