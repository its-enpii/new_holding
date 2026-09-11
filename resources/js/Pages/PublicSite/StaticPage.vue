<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    settings: { type: Object, required: true },
    page: { type: Object, required: true },
});
</script>

<template>
    <Head :title="page.title">
        <meta head-key="description" name="description" :content="page.meta_description ?? page.title" />
        <meta head-key="og:title" property="og:title" :content="page.title" />
        <meta head-key="og:description" property="og:description" :content="page.meta_description ?? page.title" />
        <meta head-key="og:type" property="og:type" content="article" />
        <meta head-key="og:url" property="og:url" :content="$page.url" />
    </Head>

    <PublicLayout :settings="settings">
        <div class="py-10 sm:py-16 bg-surface">
            <article class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <nav class="mb-6">
                    <Link :href="route('home')" class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                        <AppIcon name="arrow_back" class="text-sm" />
                        Kembali ke beranda
                    </Link>
                </nav>

                <header>
                    <h1 class="text-2xl sm:text-4xl font-extrabold leading-tight tracking-tight text-on-surface">
                        {{ page.title }}
                    </h1>
                </header>

                <div
                    class="prose-content mt-8 text-on-surface leading-relaxed text-base space-y-4"
                    v-html="page.content"
                />
            </article>
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
