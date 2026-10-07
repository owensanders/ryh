import { onMounted, ref } from 'vue';

type Appearance = 'light' | 'dark' | 'system';

export function updateTheme(value: Appearance) {
    const dark = value === 'system' ? window.matchMedia('(prefers-color-scheme: dark)').matches : value === 'dark';
    document.documentElement.classList.toggle('dark', dark);

    // Match the phone's status bar (installed app) to the chosen theme, not just the OS one.
    document.querySelectorAll('meta[name="theme-color"]').forEach((meta) => meta.setAttribute('content', dark ? '#0e1119' : '#fcfaf7'));
}

const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

const handleSystemThemeChange = () => {
    const currentAppearance = localStorage.getItem('appearance') as Appearance | null;
    updateTheme(currentAppearance || 'system');
};

export function initializeTheme() {
    // Initialize theme from saved preference or default to system...
    const savedAppearance = localStorage.getItem('appearance') as Appearance | null;
    updateTheme(savedAppearance || 'system');

    // Set up system theme change listener...
    mediaQuery.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    const appearance = ref<Appearance>('system');

    onMounted(() => {
        initializeTheme();

        const savedAppearance = localStorage.getItem('appearance') as Appearance | null;

        if (savedAppearance) {
            appearance.value = savedAppearance;
        }
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;
        localStorage.setItem('appearance', value);
        updateTheme(value);
    }

    return {
        appearance,
        updateAppearance,
    };
}
