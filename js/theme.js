(function () {
    const STORAGE_KEY = 'dms-theme';
    const DARK_CLASS = 'dark-mode';

    function getSavedTheme() {
        return localStorage.getItem(STORAGE_KEY) || 'light';
    }

    function setTheme(theme) {
        const isDark = theme === 'dark';
        document.body.classList.toggle(DARK_CLASS, isDark);
        localStorage.setItem(STORAGE_KEY, isDark ? 'dark' : 'light');
        updateToggle(isDark);
    }

    function updateToggle(isDark) {
        const toggle = document.querySelector('.theme-toggle');
        if (!toggle) return;

        toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        toggle.setAttribute('title', isDark ? 'Light mode' : 'Dark mode');
        toggle.innerHTML = isDark
            ? '<span class="theme-icon sun" aria-hidden="true">☀</span>'
            : '<span class="theme-icon moon" aria-hidden="true">☾</span>';
    }

    function injectThemeStyles() {
        if (document.getElementById('theme-mode-styles')) return;

        const style = document.createElement('style');
        style.id = 'theme-mode-styles';
        style.textContent = `
            .theme-toggle {
                position: fixed;
                top: 18px;
                right: 18px;
                width: 44px;
                height: 44px;
                border: 1px solid rgba(255,255,255,.35);
                border-radius: 50%;
                background: #ffffff;
                color: #4e1e72;
                box-shadow: 0 8px 24px rgba(0,0,0,.18);
                cursor: pointer;
                z-index: 2000;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: transform .2s ease, background .2s ease, color .2s ease, border-color .2s ease;
            }

            .theme-toggle:hover {
                transform: translateY(-2px);
            }

            .theme-icon {
                font-size: 1.35rem;
                line-height: 1;
            }

            body.dark-mode {
                background: #12151f !important;
                color: #e8ecf5 !important;
            }

            body.dark-mode .theme-toggle {
                background: #222638;
                color: #ffd166;
                border-color: rgba(255,255,255,.18);
            }

            body.dark-mode .navbar,
            body.dark-mode .landing-navbar,
            body.dark-mode .sidebar {
                background: #171a29 !important;
                color: #f5f7fb !important;
            }

            body.dark-mode .main-content,
            body.dark-mode .widget,
            body.dark-mode .auth-box,
            body.dark-mode .landing-container,
            body.dark-mode .service-card,
            body.dark-mode .services-section,
            body.dark-mode .services,
            body.dark-mode .howitworks,
            body.dark-mode .security,
            body.dark-mode .step,
            body.dark-mode .security-card,
            body.dark-mode .topbar,
            body.dark-mode .menu,
            body.dark-mode .content,
            body.dark-mode .modal-box {
                background: #1b2030 !important;
                color: #e8ecf5 !important;
                box-shadow: 0 8px 24px rgba(0,0,0,.35) !important;
            }

            body.dark-mode .auth-container,
            body.dark-mode .hero,
            body.dark-mode .cta-new,
            body.dark-mode .footer {
                background: linear-gradient(135deg, #151928, #31203f) !important;
                color: #f5f7fb !important;
            }

            body.dark-mode h1,
            body.dark-mode h2,
            body.dark-mode h3,
            body.dark-mode label,
            body.dark-mode .logo,
            body.dark-mode .service-icon,
            body.dark-mode .security-card i,
            body.dark-mode .widget h3,
            body.dark-mode .main-content h1,
            body.dark-mode .auth-box h1,
            body.dark-mode .modal-box h3,
            body.dark-mode .services h2,
            body.dark-mode .services-section h2 {
                color: #f5f7fb !important;
            }

            body.dark-mode p,
            body.dark-mode span,
            body.dark-mode small,
            body.dark-mode .auth-link,
            body.dark-mode .empty-state,
            body.dark-mode .tab-btn,
            body.dark-mode .service-card p {
                color: #cfd6e6 !important;
            }

            body.dark-mode a {
                color: #9ec5ff;
            }

            body.dark-mode input,
            body.dark-mode select,
            body.dark-mode textarea {
                background: #111827 !important;
                color: #f5f7fb !important;
                border-color: #39445c !important;
            }

            body.dark-mode input::placeholder,
            body.dark-mode textarea::placeholder {
                color: #8f9bb2;
            }

            body.dark-mode .documents-table thead,
            body.dark-mode .documents-table tr:hover,
            body.dark-mode .tab-btn:hover,
            body.dark-mode .folder-item:hover,
            body.dark-mode .alertbar {
                background: #252b3c !important;
            }

            body.dark-mode .documents-table th,
            body.dark-mode .documents-table td {
                border-color: #343d52 !important;
                color: #e8ecf5 !important;
            }

            body.dark-mode .tag {
                background: #332341 !important;
                color: #f0d8ff !important;
                border-color: #74518c !important;
            }

            body.dark-mode .footer,
            body.dark-mode .landing-footer {
                background: #171a29 !important;
                color: #f5f7fb !important;
            }

            @media (max-width: 768px) {
                .theme-toggle {
                    top: 12px;
                    right: 12px;
                    width: 40px;
                    height: 40px;
                }
            }
        `;
        document.head.appendChild(style);
    }

    function createToggle() {
        if (document.querySelector('.theme-toggle')) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'theme-toggle';
        button.addEventListener('click', function () {
            const nextTheme = document.body.classList.contains(DARK_CLASS) ? 'light' : 'dark';
            setTheme(nextTheme);
        });

        document.body.appendChild(button);
    }

    document.addEventListener('DOMContentLoaded', function () {
        injectThemeStyles();
        createToggle();
        setTheme(getSavedTheme());
    });
}());
