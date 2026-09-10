<script setup>
import { computed, ref, useId, watch } from 'vue';
import AppIcon from './AppIcon.vue';

const props = defineProps({
    /**
     * Optional items array for multi-item accordion:
     * [{ key, title, subtitle?, icon?, badge?, content?, defaultOpen? }]
     */
    items: { type: Array, default: null },
    /**
     * For single collapsible mode:
     */
    title: { type: String, default: '' },
    subtitle: { type: String, default: null },
    icon: { type: String, default: null },
    badge: { type: [String, Number], default: null },
    defaultOpen: { type: Boolean, default: false },
    /**
     * Allow multiple expanded items simultaneously when using items prop
     */
    multiple: { type: Boolean, default: false },
    bordered: { type: Boolean, default: true },
    variant: {
        type: String,
        default: 'surface',
        validator: (val) => ['surface', 'filled', 'ghost'].includes(val),
    },
});

const model = defineModel({ type: [String, Number, Array, Boolean], default: undefined });
const emit = defineEmits(['toggle']);

const generatedId = useId();

// State for multi-item mode
const internalOpenKeys = ref(new Set());

// State for single collapsible mode
const singleOpen = ref(props.defaultOpen);

// Initialize open keys if items prop is provided
if (props.items && props.items.length) {
    props.items.forEach((item) => {
        if (item.defaultOpen) {
            internalOpenKeys.value.add(item.key ?? item.id ?? item.title);
        }
    });
}

const isSingleMode = computed(() => !props.items || props.items.length === 0);

function isItemOpen(key) {
    if (isSingleMode.value) {
        if (model.value !== undefined && typeof model.value === 'boolean') {
            return model.value;
        }
        return singleOpen.value;
    }

    if (model.value !== undefined) {
        if (Array.isArray(model.value)) {
            return model.value.includes(key);
        }
        return model.value === key;
    }

    return internalOpenKeys.value.has(key);
}

function toggleSingle() {
    const nextState = !isItemOpen('single');
    if (model.value !== undefined && typeof model.value === 'boolean') {
        model.value = nextState;
    } else {
        singleOpen.value = nextState;
    }
    emit('toggle', { open: nextState });
}

function toggleItem(key) {
    if (isSingleMode.value) {
        toggleSingle();
        return;
    }

    const isOpen = isItemOpen(key);
    let nextOpenKeys;

    if (props.multiple) {
        nextOpenKeys = new Set(internalOpenKeys.value);
        if (isOpen) {
            nextOpenKeys.delete(key);
        } else {
            nextOpenKeys.add(key);
        }
    } else {
        nextOpenKeys = new Set();
        if (!isOpen) {
            nextOpenKeys.add(key);
        }
    }

    internalOpenKeys.value = nextOpenKeys;

    if (model.value !== undefined) {
        if (Array.isArray(model.value)) {
            model.value = Array.from(nextOpenKeys);
        } else {
            model.value = isOpen ? null : key;
        }
    }

    emit('toggle', { key, open: !isOpen });
}

const variantClasses = {
    surface: 'bg-surface-container-lowest',
    filled: 'bg-surface-container-low',
    ghost: 'bg-transparent',
};
</script>

<template>
    <!-- Single Collapsible Mode -->
    <div
        v-if="isSingleMode"
        class="overflow-hidden rounded-xl transition-all duration-200"
        :class="[
            variantClasses[variant] || variantClasses.surface,
            bordered && 'border border-outline-variant',
        ]"
    >
        <button
            :id="`accordion-header-${generatedId}`"
            type="button"
            class="flex w-full items-center justify-between gap-3 p-4 text-left font-bold text-primary transition-colors hover:bg-surface-container-low/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-inset"
            :aria-expanded="isItemOpen('single')"
            :aria-controls="`accordion-panel-${generatedId}`"
            @click="toggleSingle"
        >
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <AppIcon v-if="icon" :name="icon" class="shrink-0 text-xl text-primary" />
                <div class="min-w-0 flex-1">
                    <slot name="title">
                        <span class="block truncate text-sm font-bold text-primary sm:text-base">{{ title }}</span>
                    </slot>
                    <p v-if="subtitle" class="mt-0.5 truncate text-xs font-normal text-on-surface-variant">
                        {{ subtitle }}
                    </p>
                </div>
                <span
                    v-if="badge !== null && badge !== undefined"
                    class="shrink-0 rounded-full bg-primary-container px-2.5 py-0.5 text-xs font-bold text-on-primary-container"
                >
                    {{ badge }}
                </span>
            </div>
            <AppIcon
                name="expand_more"
                class="shrink-0 text-xl text-on-surface-variant transition-transform duration-200 ease-out"
                :class="{ 'rotate-180 text-primary': isItemOpen('single') }"
            />
        </button>

        <div
            :id="`accordion-panel-${generatedId}`"
            role="region"
            :aria-labelledby="`accordion-header-${generatedId}`"
            class="grid transition-[grid-template-rows] duration-200 ease-out"
            :class="isItemOpen('single') ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
        >
            <div class="overflow-hidden">
                <div class="border-t border-outline-variant/60 p-4 pt-3 text-sm text-on-surface-variant leading-relaxed">
                    <slot />
                </div>
            </div>
        </div>
    </div>

    <!-- Multi-Item List Accordion Mode -->
    <div
        v-else
        class="space-y-2"
        role="presentation"
    >
        <div
            v-for="(item, index) in items"
            :key="item.key ?? item.id ?? index"
            class="overflow-hidden rounded-xl transition-all duration-200"
            :class="[
                variantClasses[variant] || variantClasses.surface,
                bordered && 'border border-outline-variant',
            ]"
        >
            <button
                :id="`accordion-header-${generatedId}-${item.key ?? index}`"
                type="button"
                class="flex w-full items-center justify-between gap-3 p-4 text-left font-bold text-primary transition-colors hover:bg-surface-container-low/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-inset"
                :aria-expanded="isItemOpen(item.key ?? item.id ?? item.title)"
                :aria-controls="`accordion-panel-${generatedId}-${item.key ?? index}`"
                @click="toggleItem(item.key ?? item.id ?? item.title)"
            >
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <AppIcon v-if="item.icon" :name="item.icon" class="shrink-0 text-xl text-primary" />
                    <div class="min-w-0 flex-1">
                        <span class="block text-sm font-bold text-primary sm:text-base">{{ item.title }}</span>
                        <p v-if="item.subtitle" class="mt-0.5 text-xs font-normal text-on-surface-variant">
                            {{ item.subtitle }}
                        </p>
                    </div>
                    <span
                        v-if="item.badge !== null && item.badge !== undefined"
                        class="shrink-0 rounded-full bg-primary-container px-2.5 py-0.5 text-xs font-bold text-on-primary-container"
                    >
                        {{ item.badge }}
                    </span>
                </div>
                <AppIcon
                    name="expand_more"
                    class="shrink-0 text-xl text-on-surface-variant transition-transform duration-200 ease-out"
                    :class="{ 'rotate-180 text-primary': isItemOpen(item.key ?? item.id ?? item.title) }"
                />
            </button>

            <div
                :id="`accordion-panel-${generatedId}-${item.key ?? index}`"
                role="region"
                :aria-labelledby="`accordion-header-${generatedId}-${item.key ?? index}`"
                class="grid transition-[grid-template-rows] duration-200 ease-out"
                :class="isItemOpen(item.key ?? item.id ?? item.title) ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
            >
                <div class="overflow-hidden">
                    <div class="border-t border-outline-variant/60 p-4 pt-3 text-sm text-on-surface-variant leading-relaxed">
                        <slot :name="`content-${item.key ?? index}`" :item="item">
                            <div v-if="item.content">{{ item.content }}</div>
                        </slot>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>