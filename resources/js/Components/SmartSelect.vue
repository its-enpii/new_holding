<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue';
import AppIcon from './AppIcon.vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    label: { type: String, required: true },
    placeholder: { type: String, default: null },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    clearable: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    searchable: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    valueKey: { type: String, default: 'value' },
    labelKey: { type: String, default: 'label' },
    groupKey: { type: String, default: 'group' },
    id: { type: String, default: null },
    hideLabel: { type: Boolean, default: false },
    emptyActionLabel: { type: String, default: null },
    excludedValues: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue', 'search', 'search-change', 'empty-action']);
const generatedId = useId();
const selectId = computed(() => props.id || generatedId);
const trigger = ref(null);
const listbox = ref(null);
const open = ref(false);
const placeAbove = ref(false);
/** Selalu fixed sejak render pertama; kalau tidak, panel ikut alur dokumen di ujung <body> dan fokus memaksa halaman scroll ke bawah. */
const menuStyle = ref({ position: 'fixed', top: '0px', left: '0px', width: '0px', zIndex: 80, visibility: 'hidden' });
const search = ref('');
const highlighted = ref(0);
const searchInput = ref(null);
const selectedCache = ref(null);
let searchTimer;

const selected = computed(() => {
    const current = props.options.find((option) => String(option[props.valueKey]) === String(props.modelValue));

    return current || (selectedCache.value && String(selectedCache.value[props.valueKey]) === String(props.modelValue) ? selectedCache.value : null);
});
const selectedLabel = computed(() => selected.value?.[props.labelKey] || '');
const visibleOptions = computed(() => {
    const excluded = new Set(props.excludedValues.map((value) => String(value)));
    const filtered = excluded.size === 0 ? props.options : props.options.filter((option) => !excluded.has(String(option[props.valueKey])));
    const query = search.value.trim().toLocaleLowerCase();

    if (!query) return filtered;

    return filtered.filter((option) => {
        const label = String(option[props.labelKey] ?? '').toLocaleLowerCase();
        const group = String(option[props.groupKey] ?? '').toLocaleLowerCase();
        const subtitle = String(option.subtitle ?? option.description ?? '').toLocaleLowerCase();
        const value = String(option[props.valueKey] ?? '').toLocaleLowerCase();
        return label.includes(query) || group.includes(query) || subtitle.includes(query) || value.includes(query);
    });
});

/** Flat list for keyboard nav; grouped rows for render (header + options). */
const visibleRows = computed(() => {
    const opts = visibleOptions.value;
    const hasGroup = opts.some((o) => o[props.groupKey]);
    if (!hasGroup) {
        return opts.map((option, index) => ({ kind: 'option', option, index }));
    }

    const rows = [];
    let index = 0;
    let lastGroup = Symbol('start');
    for (const option of opts) {
        const group = option[props.groupKey] || '';
        if (group !== lastGroup) {
            rows.push({ kind: 'header', label: group || 'Lainnya' });
            lastGroup = group;
        }
        rows.push({ kind: 'option', option, index: index++ });
    }
    return rows;
});

function estimatedPopupHeight() {
    const itemHeight = 36;
    const optionHeight = Math.min(visibleRows.value.length, 8) * itemHeight;
    const searchHeight = props.searchable ? 52 : 0;
    return optionHeight + searchHeight + 16;
}

function positionMenu() {
    const triggerEl = trigger.value;
    if (!triggerEl) return;

    const margin = 8;
    const rect = triggerEl.getBoundingClientRect();
    const popupHeight = estimatedPopupHeight();
    const spaceBelow = window.innerHeight - rect.bottom - margin;
    const spaceAbove = rect.top - margin;
    const above = spaceBelow < popupHeight && spaceAbove > spaceBelow;
    placeAbove.value = above;

    const left = Math.min(Math.max(margin, rect.left), window.innerWidth - rect.width - margin);
    const maxList = Math.min(224 + (props.searchable ? 52 : 0), above ? spaceAbove : spaceBelow);

    if (above) {
        menuStyle.value = {
            position: 'fixed',
            left: `${left}px`,
            width: `${rect.width}px`,
            bottom: `${window.innerHeight - rect.top + margin}px`,
            top: 'auto',
            zIndex: 80,
            maxHeight: `${maxList}px`,
            visibility: 'visible',
        };
    } else {
        menuStyle.value = {
            position: 'fixed',
            left: `${left}px`,
            width: `${rect.width}px`,
            top: `${rect.bottom + margin}px`,
            bottom: 'auto',
            zIndex: 80,
            maxHeight: `${maxList}px`,
            visibility: 'visible',
        };
    }
}

function rememberSelected(options = props.options) {
    const option = options.find((item) => String(item[props.valueKey]) === String(props.modelValue));
    if (option) selectedCache.value = option;
}

function openMenu() {
    if (props.disabled) return;
    open.value = true;
    highlighted.value = Math.max(0, visibleOptions.value.findIndex((option) => String(option[props.valueKey]) === String(props.modelValue)));
    nextTick(() => {
        positionMenu();
        requestAnimationFrame(() => positionMenu());
        props.searchable && searchInput.value?.focus({ preventScroll: true });
    });
}

function onViewportChange() {
    if (open.value) positionMenu();
}
onMounted(() => {
    window.addEventListener('resize', onViewportChange);
    window.addEventListener('scroll', onViewportChange, true);
});
onBeforeUnmount(() => {
    window.removeEventListener('resize', onViewportChange);
    window.removeEventListener('scroll', onViewportChange, true);
});

function closeMenu() {
    open.value = false;
    menuStyle.value = { ...menuStyle.value, visibility: 'hidden' };
    if (search.value) emit('search-change', '');
    search.value = '';
}

function choose(option) {
    selectedCache.value = option;
    emit('update:modelValue', option[props.valueKey]);
    closeMenu();
}

function clear() {
    selectedCache.value = null;
    emit('update:modelValue', '');
    closeMenu();
}

function runEmptyAction() {
    const query = search.value.trim();
    if (!props.emptyActionLabel || !query || props.loading || visibleOptions.value.length) return;

    closeMenu();
    emit('empty-action', query);
}

function move(delta) {
    if (!visibleOptions.value.length) return;
    highlighted.value = (highlighted.value + delta + visibleOptions.value.length) % visibleOptions.value.length;
}

function onKeydown(event) {
    if (!open.value && ['ArrowDown', 'Enter', ' '].includes(event.key)) {
        event.preventDefault();
        openMenu();
        return;
    }
    if (!open.value) return;
    if (event.key === 'ArrowDown') { event.preventDefault(); move(1); }
    if (event.key === 'ArrowUp') { event.preventDefault(); move(-1); }
    if (event.key === 'Enter') {
        if (visibleOptions.value[highlighted.value]) { event.preventDefault(); choose(visibleOptions.value[highlighted.value]); }
        else if (props.emptyActionLabel && search.value.trim() && !props.loading) { event.preventDefault(); runEmptyAction(); }
    }
    if (event.key === 'Escape') { event.preventDefault(); closeMenu(); }
}

function onSearch() {
    emit('search-change', search.value);
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => emit('search', search.value), 300);
}

function onDocumentClick(event) {
    if (!event.target.closest(`[data-smart-select="${selectId.value}"]`)) closeMenu();
}

onMounted(() => document.addEventListener('click', onDocumentClick));
onBeforeUnmount(() => {
    clearTimeout(searchTimer);
    document.removeEventListener('click', onDocumentClick);
});

watch(() => props.options, (options) => {
    rememberSelected(options);
    highlighted.value = Math.min(highlighted.value, Math.max(options.length - 1, 0));
}, { immediate: true });
watch(search, () => {
    highlighted.value = 0;
});
watch(() => props.modelValue, (value) => {
    const match = props.options.find((item) => String(item[props.valueKey]) === String(value));
    selectedCache.value = match ?? null;
});
</script>

<template>
    <div class="space-y-2" :data-smart-select="selectId">
        <label :for="selectId" :class="hideLabel ? 'sr-only' : 'ml-1 block text-sm font-bold uppercase tracking-wider text-primary'">{{ label }}</label>
        <div class="relative">
            <button
                :id="selectId"
                ref="trigger"
                type="button"
                role="combobox"
                :aria-expanded="open"
                :aria-controls="`${selectId}-listbox`"
                :aria-invalid="Boolean(error)"
                :aria-required="required"
                :disabled="disabled"
                class="flex h-14 w-full items-center justify-between rounded-xl border bg-surface-container-lowest px-4 pr-16 text-left text-primary transition-all duration-150 active:scale-[0.99] focus:border-primary-container focus:ring-2 focus:ring-primary-container/10 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60 disabled:active:scale-100"
                :class="error ? 'border-error' : 'border-outline-variant'"
                v-bind="$attrs"
                @click="open ? closeMenu() : openMenu()"
                @keydown="onKeydown"
            >
                <span class="block min-w-0 flex-1 truncate whitespace-nowrap" :class="selectedLabel ? 'text-primary' : 'text-outline'">{{ selectedLabel || (placeholder ?? `Pilih ${label.toLowerCase()}`) }}</span>
                <AppIcon name="expand_more" class="absolute right-3 text-xl text-outline transition-transform duration-200" :class="{ 'rotate-180': open }" />
            </button>
            <button
                v-if="clearable && selectedLabel"
                type="button"
                class="absolute right-9 top-1/2 z-10 -translate-y-1/2 rounded-full p-1 text-outline transition-all duration-150 hover:bg-surface-container-low hover:text-primary focus:outline-none focus:ring-2 focus:ring-primary-container/20 active:scale-90"
                aria-label="Hapus pilihan"
                @click="clear"
            >
                <AppIcon name="close" class="text-lg" />
            </button>
            <Teleport to="body">
                <Transition
                    enter-active-class="transition duration-150 ease-out"
                    enter-from-class="opacity-0 scale-95 -translate-y-1"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-100 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 -translate-y-1"
                >
                    <div
                        v-if="open"
                        :id="`${selectId}-listbox`"
                        ref="listbox"
                        role="listbox"
                        class="flex flex-col overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest p-2 shadow-xl"
                        :class="placeAbove ? 'origin-bottom' : 'origin-top'"
                        :style="menuStyle"
                        :data-smart-select="selectId"
                    >
                        <div v-if="searchable" class="relative mb-2 shrink-0 bg-surface-container-lowest pb-1">
                            <AppIcon name="search" class="pointer-events-none absolute left-3 top-2.5 text-base text-on-surface-variant" />
                            <input ref="searchInput" v-model="search" type="search" class="h-9 w-full rounded-lg border border-outline-variant pl-9 pr-8 text-sm text-primary placeholder:text-on-surface-variant focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Cari..." @input="onSearch" @keydown="onKeydown">
                            <button v-if="search" type="button" class="absolute right-2 top-1.5 rounded-full p-1 text-on-surface-variant transition-all duration-150 hover:text-primary active:scale-90" @click="search = ''; onSearch()"><AppIcon name="close" class="text-sm" /></button>
                        </div>
                        <div class="min-h-0 flex-1 overflow-y-auto">
                            <div v-if="loading" class="px-3 py-4 text-center text-sm text-on-surface-variant">Memuat...</div>
                            <template v-else>
                                <template v-for="(row, rowIndex) in visibleRows" :key="row.kind === 'header' ? `h-${row.label}-${rowIndex}` : String(row.option[valueKey])">
                                    <div
                                        v-if="row.kind === 'header'"
                                        class="px-3 pb-1 pt-2 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant first:pt-1"
                                        role="presentation"
                                    >
                                        {{ row.label }}
                                    </div>
                                    <button
                                        v-else
                                        :id="`${selectId}-option-${row.index}`"
                                        type="button"
                                        role="option"
                                        :aria-selected="String(row.option[valueKey]) === String(modelValue)"
                                        class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition-colors duration-150 hover:bg-surface-container-low"
                                        :class="row.index === highlighted ? 'bg-surface-container-low text-primary font-medium' : 'text-on-surface'"
                                        @mouseenter="highlighted = row.index"
                                        @click="choose(row.option)"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold" :class="String(row.option[valueKey]) === String(modelValue) ? 'text-primary' : 'text-on-surface'">
                                                    {{ row.option[labelKey] }}
                                                </span>
                                                <span v-if="row.option.badge" class="shrink-0 rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary">
                                                    {{ row.option.badge }}
                                                </span>
                                            </div>
                                            <p v-if="row.option.subtitle || row.option.description" class="mt-0.5 truncate text-xs text-on-surface-variant">
                                                {{ row.option.subtitle || row.option.description }}
                                            </p>
                                        </div>
                                        <AppIcon v-if="String(row.option[valueKey]) === String(modelValue)" name="check" class="shrink-0 text-base text-primary" />
                                    </button>
                                </template>
                                <button v-if="!visibleOptions.length && emptyActionLabel && search.trim()" type="button" class="flex w-full items-center gap-2 rounded-lg px-3 py-3 text-left text-sm font-semibold text-primary transition-colors hover:bg-surface-container-low focus:bg-surface-container-low focus:outline-none" @click="runEmptyAction"><AppIcon name="person_add" class="text-xl" />{{ emptyActionLabel }}</button>
                                <div v-else-if="!visibleOptions.length" class="px-3 py-4 text-center text-sm text-on-surface-variant">Tidak ada opsi.</div>
                            </template>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </div>
        <p v-if="error" :id="`${selectId}-error`" class="ml-1 text-sm text-error">{{ error }}</p>
        <p v-else-if="hint" :id="`${selectId}-hint`" class="ml-1 text-sm text-on-surface-variant">{{ hint }}</p>
    </div>
</template>
