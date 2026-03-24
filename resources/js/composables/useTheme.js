import { ref, onMounted } from 'vue';

const THEME_KEY = 'theme';

function getInitialTheme() {
  if (typeof document === 'undefined') return 'light';
  const current = document.documentElement.getAttribute('data-theme');
  if (current === 'dark' || current === 'light') return current;
  if (typeof localStorage !== 'undefined' && localStorage.getItem('theme') === 'dark') return 'dark';
  if (typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) return 'dark';
  return 'light';
}

function applyTheme(theme) {
  if (typeof document === 'undefined') return;
  document.documentElement.setAttribute('data-theme', theme);
}

export function useTheme() {
  const theme = ref(getInitialTheme());

  onMounted(() => {
    applyTheme(theme.value);
  });

  function setTheme(value) {
    const next = value === 'dark' ? 'dark' : 'light';
    theme.value = next;
    localStorage.setItem(THEME_KEY, next);
    applyTheme(next);
  }

  function toggleTheme() {
    setTheme(theme.value === 'dark' ? 'light' : 'dark');
  }

  return { theme, setTheme, toggleTheme };
}
