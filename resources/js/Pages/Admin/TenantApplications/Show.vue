<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import AppBadge from '../../../Components/AppBadge.vue';
import AppButton from '../../../Components/AppButton.vue';
import AppCard from '../../../Components/AppCard.vue';
import AppIcon from '../../../Components/AppIcon.vue';
import AppSwitch from '../../../Components/AppSwitch.vue';
import { useConfirm } from '../../../Composables/useConfirm';
import { useToast } from '../../../Composables/useToast';

const props = defineProps({
    tenant: { type: Object, required: true },
    tenantApplication: { type: Object, required: true },
    newApiSecret: { type: String, default: null },
});

const page = usePage();
const { confirm } = useConfirm();
const { success: showSuccessToast, warning: showWarningToast, error: showErrorToast } = useToast();
const showSecret = ref(false);
const regenerating = ref(false);
const testingConnection = ref(false);
const connectionResult = ref(null);

const flashSecret = computed(() => props.newApiSecret || page.props.flash?.new_api_secret);

const isExpired = computed(() => {
    if (!props.tenantApplication.expired_at) return false;
    return new Date(props.tenantApplication.expired_at) < new Date();
});

const statusTone = computed(() => {
    if (isExpired.value) return 'danger';
    return props.tenantApplication.is_active ? 'success' : 'neutral';
});

const statusLabel = computed(() => {
    if (isExpired.value) return 'Kadaluarsa';
    return props.tenantApplication.is_active ? 'Aktif' : 'Nonaktif';
});

const connection = computed(() => connectionResult.value || {
    status: props.tenantApplication.connection_status,
    latency_ms: props.tenantApplication.connection_latency_ms,
    checked_at: props.tenantApplication.connection_checked_at,
    message: null,
});

const connectionTone = computed(() => ({
    connected: 'success',
    auth_error: 'warning',
    offline: 'error',
}[connection.value.status] || 'neutral'));

const connectionLabel = computed(() => ({
    connected: 'Terhubung',
    auth_error: 'Secret/URL salah',
    offline: 'Tidak dapat dihubungi',
}[connection.value.status] || 'Belum dicek'));

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text);
        showSuccessToast('Teks disalin ke clipboard!');
    }
}

function regenerate() {
    confirm({
        title: 'Regenerate API Secret',
        message: 'Secret lama tidak akan berlaku lagi setelah digenerate ulang. Pastikan untuk memperbarui konfigurasi di instance aplikasi.',
        confirmLabel: 'Regenerate Secret',
    }).then((confirmed) => {
        if (!confirmed) return;
        regenerating.value = true;
        router.post(
            route('admin.tenants.applications.regenerate-secret', [props.tenant.id, props.tenantApplication.id]),
            {},
            {
                onFinish: () => { regenerating.value = false; },
            }
        );
    });
}

async function testConnection() {
    testingConnection.value = true;
    const xsrfToken = document.cookie.match('(^|;)\\s*XSRF-TOKEN\\s*=\\s*([^;]+)')?.pop() || '';

    try {
        const response = await fetch(
            route('admin.tenants.applications.test-connection', [props.tenant.id, props.tenantApplication.id]),
            {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': decodeURIComponent(xsrfToken),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message || 'Tidak dapat memeriksa koneksi.');
        }

        connectionResult.value = payload;
        if (payload.status === 'connected') {
            showSuccessToast({ title: 'Koneksi berhasil', message: payload.message });
        } else if (payload.status === 'auth_error') {
            showWarningToast({ title: 'Koneksi gagal', message: payload.message });
        } else {
            showErrorToast({ title: 'Koneksi gagal', message: payload.message });
        }
    } catch (error) {
        showErrorToast({ title: 'Koneksi gagal', message: error.message || 'Tidak dapat memeriksa koneksi.' });
    } finally {
        testingConnection.value = false;
    }
}
</script>

<template>
    <Head :title="`Lisensi ${tenantApplication.application?.name} - ${tenant.name}`" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-xs text-on-surface-variant">
                        <Link :href="route('admin.tenants.index')" class="hover:text-primary">Tenants</Link>
                        <span>/</span>
                        <Link :href="route('admin.tenants.show', tenant.id)" class="hover:text-primary">{{ tenant.name }}</Link>
                        <span>/</span>
                        <Link :href="route('admin.tenants.applications.index', tenant.id)" class="hover:text-primary">Lisensi</Link>
                        <span>/</span>
                        <span>Detail</span>
                    </div>
                    <h1 class="mt-1 text-3xl font-semibold text-primary">Detail Lisensi Aplikasi</h1>
                    <p class="mt-1 text-on-surface-variant">
                        Informasi kredensial dan integrasi <span class="font-semibold text-on-surface">{{ tenantApplication.application?.name }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <AppButton variant="secondary" :href="route('admin.tenants.applications.index', tenant.id)">
                        Daftar Lisensi
                    </AppButton>
                    <AppButton variant="primary" :href="route('admin.tenants.applications.edit', [tenant.id, tenantApplication.id])">
                        <AppIcon name="edit" class="mr-1" />
                        Edit Lisensi
                    </AppButton>
                </div>
            </div>

            <div v-if="flashSecret" class="flex flex-wrap items-center justify-between gap-4 rounded-md border border-tertiary/40 bg-tertiary-container/30 p-4 text-on-tertiary-container">
                <div class="space-y-1">
                    <p class="text-sm font-semibold flex items-center gap-2">
                        <AppIcon name="key" />
                        API Secret Baru Telah Dibuat
                    </p>
                    <p class="font-mono text-xs break-all select-all">{{ flashSecret }}</p>
                </div>
                <AppButton variant="secondary" size="sm" @click="copyToClipboard(flashSecret)">
                    <AppIcon name="content_copy" class="mr-1" />
                    Salin Secret
                </AppButton>
            </div>

            <!-- Connection & Credential Card -->
            <AppCard>
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-outline-variant/40 pb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-primary">Koneksi</h2>
                            <p class="text-xs text-on-surface-variant">Periksa koneksi dan kelola kredensial subsidiary.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <AppBadge :tone="connectionTone">{{ connectionLabel }}</AppBadge>
                            <AppButton variant="primary" size="sm" :loading="testingConnection" @click="testConnection">
                                <AppIcon name="wifi" class="mr-1" />
                                Test Koneksi
                            </AppButton>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-md border border-outline-variant/40 bg-surface-container-low p-4">
                            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Latensi</p>
                            <p class="mt-2 text-sm font-semibold text-on-surface">{{ connection.latency_ms !== null ? `${connection.latency_ms} ms` : '—' }}</p>
                        </div>
                        <div class="rounded-md border border-outline-variant/40 bg-surface-container-low p-4">
                            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Terakhir Dicek</p>
                            <p class="mt-2 text-sm font-semibold text-on-surface">{{ formatDate(connection.checked_at) }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-md border border-outline-variant/40 bg-surface-container-low p-4">
                            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">X-Holding-Token (API Secret)</p>
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p v-if="showSecret" class="font-mono text-xs break-all text-on-surface select-all">
                                        {{ tenantApplication.api_secret }}
                                    </p>
                                    <p v-else class="font-mono text-xs text-on-surface-variant tracking-widest">
                                        ••••••••••••••••••••••••••••••••••••••••
                                    </p>
                                </div>
                                <AppButton variant="ghost" size="sm" @click="copyToClipboard(tenantApplication.api_secret)">
                                    <AppIcon name="content_copy" />
                                </AppButton>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <AppSwitch v-model="showSecret" label="Tampilkan secret" />
                            </div>
                        </div>

                        <div class="rounded-md border border-outline-variant/40 bg-surface-container-low p-4">
                            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">X-Holding-Tenant (Tenant Slug)</p>
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <p class="font-mono text-sm font-semibold text-primary">
                                    {{ tenant.slug }}
                                </p>
                                <AppButton variant="ghost" size="sm" @click="copyToClipboard(tenant.slug)">
                                    <AppIcon name="content_copy" />
                                </AppButton>
                            </div>
                            <p class="mt-3 text-xs text-on-surface-variant">Domain: {{ tenant.domain || '—' }}</p>
                        </div>
                    </div>
                </div>
            </AppCard>

            <!-- Detail Lisensi Card -->
            <AppCard>
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-primary border-b border-outline-variant/40 pb-3">Informasi Instance & Lisensi</h2>
                    <dl class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-on-surface-variant">Aplikasi Master</dt>
                            <dd class="mt-1 font-semibold text-primary flex items-center gap-2">
                                <AppIcon :name="tenantApplication.application?.icon_path || 'widgets'" class="text-primary" />
                                {{ tenantApplication.application?.name }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">Label Instance</dt>
                            <dd class="mt-1 font-semibold text-on-surface">{{ tenantApplication.label || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">Status Lisensi</dt>
                            <dd class="mt-1"><AppBadge :tone="statusTone">{{ statusLabel }}</AppBadge></dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">URL Instance</dt>
                            <dd class="mt-1">
                                <a :href="tenantApplication.instance_url" target="_blank" rel="noopener noreferrer" class="font-mono text-xs text-primary underline break-all">
                                    {{ tenantApplication.instance_url }}
                                </a>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">Diaktivasi Pada</dt>
                            <dd class="mt-1 text-sm text-on-surface">{{ formatDate(tenantApplication.activated_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-on-surface-variant">Kadaluarsa Pada</dt>
                            <dd class="mt-1 text-sm text-on-surface">{{ formatDate(tenantApplication.expired_at) }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-on-surface-variant">Catatan Lisensi</dt>
                            <dd class="mt-1 text-sm text-on-surface">{{ tenantApplication.notes || '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </AppCard>
        </div>
    </AdminLayout>
</template>
