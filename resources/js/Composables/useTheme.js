import { ref, watch, onMounted, onUnmounted } from 'vue';

const theme = ref(localStorage.getItem('theme') || 'system');
const isDark = ref(false);

const updateIsDark = () => {
    if (theme.value === 'system') {
        isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    } else {
        isDark.value = theme.value === 'dark';
    }
    
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

// Initial update
updateIsDark();

export function useTheme() {
    const setTheme = (newTheme) => {
        theme.value = newTheme;
        localStorage.setItem('theme', newTheme);
        updateIsDark();
    };

    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const handleSystemChange = () => {
        if (theme.value === 'system') {
            updateIsDark();
        }
    };

    onMounted(() => {
        mediaQuery.addEventListener('change', handleSystemChange);
        updateIsDark();
    });

    onUnmounted(() => {
        mediaQuery.removeEventListener('change', handleSystemChange);
    });

    return {
        theme,
        isDark,
        setTheme
    };
}
