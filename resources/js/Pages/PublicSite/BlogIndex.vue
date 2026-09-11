<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppButton from '@/Components/AppButton.vue';
import AppEmptyState from '@/Components/AppEmptyState.vue';
import AppIcon from '@/Components/AppIcon.vue';
import AppInput from '@/Components/AppInput.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    settings: { type: Object, required: true },
    posts: { type: Object, required: true },
    search: { type: String, default: '' },
});

const searchQuery = ref(props.search);

function doSearch() {
    router.get(route('public.posts'), { q: searchQuery.value }, { preserveState: true, replace: true });
}

function formatDateTime(value) {
    if (!value) return '';
    return new Date(value).toLocaleDateString('id-ID', { dateStyle: 'long' });
}
</script>

<template>
    <Head title="Berita &amp; Informasi">
        <meta head-key="description" name="description" content="Berita, artikel, dan informasi terkini dari portal holding." />
        <meta head-key="og:title" property="og:title" content="Berita &amp; Informasi" />
        <meta head-key="og:type" property="og:type" content="website" />
    </Head>

    <PublicLayout :settings="settings">
        <section class="border-b border-outline-variant/40 bg-gradient-to-b from-primary/10 to-surface py-12 sm:py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <span class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-primary">
                    <AppIcon name="newspaper" />
                    Warta &amp; Publikasi
                </span>
                <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-primary sm:text-4xl">
                    Berita &amp; Informasi Holding
                </h1>
                <p class="mt-2 max-w-2xl text-sm sm:text-base text-on-surface-variant">
                    Ikuti perkembangan terbaru, kegiatan strategis, dan pengumuman resmi seputar ekosistem unit bisnis.
                </p>

                <form class="mt-6 flex max-w-lg items-center gap-2" @submit.prevent="doSearch">
                    <AppInput
                        v-model="searchQuery"
                        placeholder="Cari judul berita atau topik..."
                        icon="search"
                        class="flex-1"
                    />
                    <AppButton type="submit" variant="primary" size="compact">Cari</AppButton>
                </form>
            </div>
        </section>

        <section class="py-12 sm:py-16 bg-surface">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div v-if="posts.data.length === 0" class="py-12">
                    <AppEmptyState
                        icon="article"
                        title="Tidak ada berita yang ditemukan"
                        description="Belum ada berita yang dipublikasikan atau sesuai dengan kata kunci pencarian Anda."
                    />
                </div>

                <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="post in posts.data"
                        :key="post.slug"
                        class="flex flex-col overflow-hidden rounded-2xl border border-outline-variant/60 bg-surface-container-lowest shadow-sm hover:shadow-md transition"
                    >
                        <div v-if="post.cover_image_url" class="aspect-video w-full overflow-hidden bg-surface-container">
                            <img :src="post.cover_image_url" :alt="post.title" class="size-full object-cover">
                        </div>
                        <div v-else class="flex aspect-video w-full items-center justify-center bg-primary-container/20 text-primary">
                            <AppIcon name="newspaper" class="text-4xl" />
                        </div>

                        <div class="flex flex-1 flex-col justify-between p-5">
                            <div>
                                <div class="flex items-center gap-2 text-xs font-medium text-on-surface-variant">
                                    <span>{{ formatDateTime(post.published_at) }}</span>
                                    <span v-if="post.author_name">· {{ post.author_name }}</span>
                                </div>
                                <h2 class="mt-2 text-base font-bold text-on-surface line-clamp-2">
                                    <Link :href="route('public.post', post.slug)" class="hover:text-primary transition">
                                        {{ post.title }}
                                    </Link>
                                </h2>
                                <p v-if="post.excerpt" class="mt-2 text-xs leading-relaxed text-on-surface-variant line-clamp-3">
                                    {{ post.excerpt }}
                                </p>
                            </div>
                            <div class="mt-5 pt-3 border-t border-outline-variant/40">
                                <Link :href="route('public.post', post.slug)" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                                    Baca selengkapnya
                                    <AppIcon name="arrow_forward" class="text-sm" />
                                </Link>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- Pagination -->
                <div v-if="posts.links?.length > 3" class="mt-12 flex flex-wrap items-center justify-center gap-1.5">
                    <Link
                        v-for="link in posts.links"
                        :key="link.label"
                        :href="link.url ?? '#'"
                        class="rounded-lg px-3.5 py-2 text-xs font-semibold transition"
                        :class="[
                            link.active ? 'bg-primary text-on-primary shadow-sm' : 'bg-surface-container-low text-on-surface hover:bg-surface-container',
                            !link.url && 'pointer-events-none opacity-40'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
