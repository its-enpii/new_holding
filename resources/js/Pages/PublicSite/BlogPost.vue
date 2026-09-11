<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    settings: { type: Object, required: true },
    post: { type: Object, required: true },
    relatedPosts: { type: Array, default: () => [] },
});

function formatDateTime(value) {
    if (!value) return '';
    return new Date(value).toLocaleDateString('id-ID', { dateStyle: 'long' });
}
</script>

<template>
    <Head :title="`${post.title} — Berita`">
        <meta head-key="description" name="description" :content="post.meta_description ?? post.excerpt ?? post.title" />
        <meta head-key="og:title" property="og:title" :content="post.title" />
        <meta head-key="og:description" property="og:description" :content="post.meta_description ?? post.excerpt ?? post.title" />
        <meta head-key="og:type" property="og:type" content="article" />
        <meta head-key="og:url" property="og:url" :content="$page.url" />
        <meta v-if="post.cover_image_url" head-key="og:image" property="og:image" :content="post.cover_image_url" />
    </Head>

    <PublicLayout :settings="settings">
        <div class="py-10 sm:py-16 bg-surface">
            <article class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <nav class="mb-6">
                    <Link :href="route('public.posts')" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                        <AppIcon name="arrow_back" class="text-sm" />
                        Kembali ke semua berita
                    </Link>
                </nav>

                <header>
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-on-surface-variant">
                        <span>{{ formatDateTime(post.published_at) }}</span>
                        <span v-if="post.author_name">· {{ post.author_name }}</span>
                    </div>

                    <h1 class="mt-3 text-2xl sm:text-4xl font-extrabold leading-tight tracking-tight text-on-surface">
                        {{ post.title }}
                    </h1>

                    <p v-if="post.excerpt" class="mt-4 text-base sm:text-lg leading-relaxed text-on-surface-variant font-medium">
                        {{ post.excerpt }}
                    </p>
                </header>

                <figure v-if="post.cover_image_url" class="my-8 overflow-hidden rounded-2xl border border-outline-variant/60 shadow-md">
                    <img :src="post.cover_image_url" :alt="post.title" class="w-full object-cover max-h-[420px]">
                </figure>

                <!-- Rich content rendered securely -->
                <div
                    class="prose-content mt-8 text-on-surface leading-relaxed text-base space-y-4"
                    v-html="post.content"
                />

                <footer class="mt-12 border-t border-outline-variant/60 pt-6 flex justify-between items-center">
                    <Link :href="route('public.posts')" class="inline-flex min-h-10 items-center gap-2 rounded-full bg-primary/10 px-5 text-sm font-semibold text-primary hover:bg-primary/20 transition">
                        <AppIcon name="newspaper" />
                        Lihat berita lainnya
                    </Link>
                </footer>
            </article>

            <!-- Related Posts -->
            <section v-if="relatedPosts.length > 0" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 mt-16 pt-12 border-t border-outline-variant/40">
                <h2 class="text-xl font-bold text-on-surface mb-6">Berita Terkait</h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="rel in relatedPosts"
                        :key="rel.slug"
                        class="flex flex-col overflow-hidden rounded-2xl border border-outline-variant/60 bg-surface-container-lowest p-5 shadow-sm hover:shadow-md transition"
                    >
                        <p class="text-xs text-on-surface-variant">{{ formatDateTime(rel.published_at) }}</p>
                        <h3 class="mt-2 text-sm font-bold text-on-surface line-clamp-2">
                            <Link :href="route('public.post', rel.slug)" class="hover:text-primary transition">
                                {{ rel.title }}
                            </Link>
                        </h3>
                        <p v-if="rel.excerpt" class="mt-2 text-xs text-on-surface-variant line-clamp-2">{{ rel.excerpt }}</p>
                    </article>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>

<style scoped>
:deep(.prose-content h2) {
    font-size: 1.5rem;
    font-weight: 700;
    margin-top: 1.75rem;
    margin-bottom: 0.75rem;
    color: var(--color-on-surface);
}
:deep(.prose-content h3) {
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--color-on-surface);
}
:deep(.prose-content p) {
    margin-bottom: 1rem;
    line-height: 1.75;
}
:deep(.prose-content ul) {
    list-style-type: disc;
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}
:deep(.prose-content ol) {
    list-style-type: decimal;
    padding-left: 1.5rem;
    margin-bottom: 1rem;
}
:deep(.prose-content table) {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
}
:deep(.prose-content th),
:deep(.prose-content td) {
    border: 1px solid var(--color-outline-variant);
    padding: 0.5rem 0.75rem;
}
:deep(.prose-content th) {
    background: var(--color-surface-container-low);
    font-weight: 700;
}
</style>
