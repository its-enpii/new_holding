<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppButton from '@/Components/AppButton.vue';
import AppCard from '@/Components/AppCard.vue';
import AppIcon from '@/Components/AppIcon.vue';
import AppInput from '@/Components/AppInput.vue';
import AppTextarea from '@/Components/AppTextarea.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    settings: { type: Object, required: true },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
    website: '', // honeypot
});

const submitted = ref(false);

function submit() {
    form.post(route('public.contact.store'), {
        preserveScroll: true,
        onSuccess: () => {
            submitted.value = true;
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Hubungi Kami">
        <meta head-key="description" name="description" content="Kirimkan pertanyaan, permohonan lisensi, atau saran kepada holding portal." />
        <meta head-key="og:title" property="og:title" content="Hubungi Kami" />
        <meta head-key="og:type" property="og:type" content="website" />
    </Head>

    <PublicLayout :settings="settings">
        <section class="border-b border-outline-variant/40 bg-gradient-to-b from-primary/10 to-surface py-12 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <span class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary">
                    <AppIcon name="mail" />
                    Kanal Komunikasi
                </span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-primary sm:text-4xl">
                    Hubungi Tim Holding
                </h1>
                <p class="mt-2 max-w-2xl text-sm sm:text-base text-on-surface-variant">
                    Sampaikan pertanyaan seputar integrasi sistem, pendaftaran unit bisnis baru, atau bantuan teknis portal holding.
                </p>
            </div>
        </section>

        <section class="py-12 sm:py-16 bg-surface">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-8 lg:grid-cols-12">
                    <!-- Left: Contact Details -->
                    <div class="lg:col-span-5 space-y-6">
                        <div class="rounded-2xl border border-outline-variant/60 bg-surface-container-lowest p-6 shadow-sm">
                            <h2 class="text-base font-bold text-primary">Informasi Kontak</h2>
                            <p class="mt-2 text-xs leading-relaxed text-on-surface-variant">
                                Tim kami siap membantu kebutuhan operasional dan integrasi ekosistem holding Anda.
                            </p>

                            <ul class="mt-6 space-y-4 text-xs">
                                <li v-if="settings.contact_address" class="flex items-start gap-3 text-on-surface">
                                    <div class="grid size-9 place-items-center rounded-lg bg-primary/10 text-primary shrink-0">
                                        <AppIcon name="place" class="text-lg" />
                                    </div>
                                    <div>
                                        <p class="font-bold text-on-surface">Alamat Kantor</p>
                                        <p class="mt-0.5 text-on-surface-variant">{{ settings.contact_address }}</p>
                                    </div>
                                </li>

                                <li v-if="settings.contact_phone" class="flex items-start gap-3 text-on-surface">
                                    <div class="grid size-9 place-items-center rounded-lg bg-primary/10 text-primary shrink-0">
                                        <AppIcon name="call" class="text-lg" />
                                    </div>
                                    <div>
                                        <p class="font-bold text-on-surface">Telepon / WhatsApp</p>
                                        <p class="mt-0.5 text-on-surface-variant">{{ settings.contact_phone }}</p>
                                    </div>
                                </li>

                                <li v-if="settings.contact_email" class="flex items-start gap-3 text-on-surface">
                                    <div class="grid size-9 place-items-center rounded-lg bg-primary/10 text-primary shrink-0">
                                        <AppIcon name="mail" class="text-lg" />
                                    </div>
                                    <div>
                                        <p class="font-bold text-on-surface">Email</p>
                                        <p class="mt-0.5 text-on-surface-variant">{{ settings.contact_email }}</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Right: Form -->
                    <div class="lg:col-span-7">
                        <AppCard padded>
                            <h2 class="text-lg font-bold text-primary">Kirim Pesan</h2>
                            <p class="mt-1 text-xs text-on-surface-variant">
                                Lengkapi formulir di bawah ini. Kami akan membalas ke email atau nomor telepon Anda secepatnya.
                            </p>

                            <div v-if="flashSuccess" class="mt-4 rounded-xl bg-secondary/15 p-4 text-xs font-semibold text-secondary-container border border-secondary/30 flex items-center gap-2">
                                <AppIcon name="check_circle" class="text-lg text-secondary" />
                                <span>{{ flashSuccess }}</span>
                            </div>

                            <form class="mt-6 space-y-4" @submit.prevent="submit">
                                <!-- Honeypot field (hidden from regular users) -->
                                <div class="absolute -left-[9999px] h-px w-px overflow-hidden" aria-hidden="true">
                                    <label for="website">Website</label>
                                    <input id="website" v-model="form.website" type="text" tabindex="-1" autocomplete="off">
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <AppInput
                                        v-model="form.name"
                                        label="Nama Lengkap"
                                        icon="person"
                                        required
                                        placeholder="Nama Anda"
                                        :error="form.errors.name"
                                    />
                                    <AppInput
                                        v-model="form.email"
                                        label="Email"
                                        icon="mail"
                                        type="email"
                                        placeholder="nama@email.com"
                                        :error="form.errors.email"
                                    />
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <AppInput
                                        v-model="form.phone"
                                        label="Nomor Telepon"
                                        icon="call"
                                        placeholder="08xxxxxxxxxx"
                                        :error="form.errors.phone"
                                    />
                                    <AppInput
                                        v-model="form.subject"
                                        label="Subjek Pesan"
                                        icon="chat"
                                        placeholder="Topik atau perihal"
                                        :error="form.errors.subject"
                                    />
                                </div>

                                <AppTextarea
                                    v-model="form.message"
                                    label="Isi Pesan"
                                    required
                                    rows="5"
                                    placeholder="Tuliskan pesan, pertanyaan, atau permohonan Anda..."
                                    :error="form.errors.message"
                                />

                                <div class="flex justify-end pt-2">
                                    <AppButton type="submit" :loading="form.processing" icon="send">
                                        Kirim Pesan
                                    </AppButton>
                                </div>
                            </form>
                        </AppCard>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
