<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppButton from './AppButton.vue';
import AppModal from './AppModal.vue';

const props = defineProps({
    exportUrl: { type: String, required: true },
    importUrl: { type: String, required: true },
    columns: { type: Array, required: true },
    title: { type: String, default: 'Impor CSV' },
    hint: { type: String, default: 'Unggah file CSV (Excel-compatible). Baris pertama harus header.' },
});

const open = ref(false);
const fileInput = ref(null);
const form = useForm({ file: null });

function exportCsv() {
    window.location.assign(props.exportUrl);
}

function openImport() {
    form.reset();
    form.clearErrors();
    open.value = true;
}

function onFileChange(event) {
    const file = event.target.files?.[0] ?? null;
    form.file = file;
}

function submitImport() {
    form.post(props.importUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
            if (fileInput.value) fileInput.value.value = '';
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
        <label class="block space-y-2">
            <span class="ml-1 text-sm font-bold uppercase tracking-wider text-primary">File CSV</span>
            <input
                ref="fileInput"
                type="file"
                accept=".csv,text/csv,application/vnd.ms-excel"
                class="block w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm text-primary file:mr-3 file:rounded-lg file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-bold file:text-on-primary"
                @change="onFileChange"
            />
            <p v-if="form.errors.file" class="text-sm text-error">{{ form.errors.file }}</p>
        </label>
        <template #footer>
            <AppButton variant="secondary" :disabled="form.processing" @click="open = false">Batal</AppButton>
            <AppButton :loading="form.processing" :disabled="!form.file" icon="upload" @click="submitImport">Impor</AppButton>
        </template>
    </AppModal>
</template>
