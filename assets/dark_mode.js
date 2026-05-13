// Dark Mode Toggle Manager
(function() {
    const THEME_KEY = 'hotel-theme-preference';
    const DARK_THEME = 'dark';
    const LIGHT_THEME = 'light';

    // Get current theme from localStorage or system preference
    function getCurrentTheme() {
        const saved = localStorage.getItem(THEME_KEY);
        if (saved) {
            return saved;
        }
        
        // Check system preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return DARK_THEME;
        }
        
        return LIGHT_THEME;
    }

    // Apply theme to document
    function applyTheme(theme) {
        const html = document.documentElement;
        
        if (theme === DARK_THEME) {
            html.setAttribute('data-theme', DARK_THEME);
            html.classList.add('dark-mode');
        } else {
            html.setAttribute('data-theme', LIGHT_THEME);
            html.classList.remove('dark-mode');
        }
        
        // Update toggle button if it exists
        const toggle = document.getElementById('theme-toggle');
        if (toggle) {
            toggle.setAttribute('aria-pressed', theme === DARK_THEME);
            toggle.title = theme === DARK_THEME ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        }
    }

    // Toggle between themes
    function toggleTheme() {
        const current = localStorage.getItem(THEME_KEY) || getCurrentTheme();
        const newTheme = current === DARK_THEME ? LIGHT_THEME : DARK_THEME;
        
        localStorage.setItem(THEME_KEY, newTheme);
        applyTheme(newTheme);
    }

    // Initialize theme on page load
    function initTheme() {
        const theme = getCurrentTheme();
        localStorage.setItem(THEME_KEY, theme);
        applyTheme(theme);
    }

    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initTheme();
            
            // Attach toggle handler
            const toggle = document.getElementById('theme-toggle');
            if (toggle) {
                toggle.addEventListener('click', toggleTheme);
            }
        });
    } else {
        initTheme();
        const toggle = document.getElementById('theme-toggle');
        if (toggle) {
            toggle.addEventListener('click', toggleTheme);
        }
    }

    // Listen for system preference changes
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem(THEME_KEY)) {
                applyTheme(e.matches ? DARK_THEME : LIGHT_THEME);
            }
        });
    }

    // Expose toggle function globally
    window.toggleDarkMode = toggleTheme;
})();
