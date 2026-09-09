import { computed, ref } from 'vue';

const STORAGE_KEY = 'holding-theme';
const THEME_MODES = ['light', 'dark', 'system'];
const themeMode = ref(readStored());

function readStored() {
    try {
        const value = localStorage.getItem(STORAGE_KEY);
        return THEME_MODES.includes(value) ? value : 'system';
    } catch {
        return 'system';
    }
}

function systemMode() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function applyMode(mode) {
    const next = THEME_MODES.includes(mode) ? mode : 'system';
    document.documentElement.setAttribute('data-theme', next === 'system' ? systemMode() : next);
    try {
        localStorage.setItem(STORAGE_KEY, next);
    } catch {
        /* Private browsing mode. */
    }
    themeMode.value = next;
}

export function useTheme() {
    const current = computed(() => {
        if (themeMode.value !== 'system') return themeMode.value;

        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    });

    function setTheme(mode) {
        applyMode(mode);
    }

    function toggleTheme() {
        setTheme(current.value === 'dark' ? 'light' : 'dark');
    }

    return { themeMode, current, setTheme, toggleTheme };
}
