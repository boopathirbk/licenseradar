/**
 * LicenseRadar — Client-Side JavaScript
 * Inline theme toggle (no page reload), password eye toggle, 2FA tabs.
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggles();
    initSettingsThemeButtons();
    initPasswordToggles();
    init2FATabs();
});

// ════════════════════════════════════════════════════════════════════════
// Theme Toggle (inline — no page reload)
// ════════════════════════════════════════════════════════════════════════

const SUN_SVG  = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>';
const MOON_SVG = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>';

function initThemeToggles() {
    // Find all theme toggle buttons (header, login page, setup page)
    document.querySelectorAll('#header-theme-toggle, #login-theme-toggle, #setup-theme-toggle').forEach(btn => {
        btn.addEventListener('click', () => toggleTheme(btn));
    });
}

function toggleTheme(btn) {
    const html = document.documentElement;
    const isDark = html.classList.contains('dark');
    const newTheme = isDark ? 'light' : 'dark';

    // 1. Instantly update the DOM
    html.classList.remove('dark', 'light');
    html.classList.add(newTheme);

    // 2. Update all theme toggle buttons on the page
    document.querySelectorAll('#header-theme-toggle, #login-theme-toggle, #setup-theme-toggle').forEach(b => {
        b.innerHTML = newTheme === 'dark' ? SUN_SVG : MOON_SVG;
        b.setAttribute('aria-label', 'Switch to ' + (newTheme === 'dark' ? 'light' : 'dark') + ' mode');
        b.setAttribute('title', 'Switch to ' + (newTheme === 'dark' ? 'light' : 'dark') + ' mode');
    });

    // 3. Update frosted header class
    const header = document.querySelector('header[role="banner"]');
    if (header) {
        header.classList.remove('frosted-header-dark', 'frosted-header-light');
        header.classList.add(newTheme === 'dark' ? 'frosted-header-dark' : 'frosted-header-light');
    }

    // 4. Save to localStorage for instant load on next page
    localStorage.setItem('lr-theme', newTheme);

    // 5. Persist to server via fetch (no page reload)
    const csrf = btn.getAttribute('data-csrf');
    if (csrf) {
        const fd = new FormData();
        fd.append('_csrf_token', csrf);
        fd.append('theme', newTheme);

        fetch('?route=toggle_theme', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).catch(() => { /* silent — local toggle is enough */ });
    }
}

// ════════════════════════════════════════════════════════════════════════
// Settings Appearance Buttons (same inline logic as header toggle)
// ════════════════════════════════════════════════════════════════════════

function initSettingsThemeButtons() {
    const group = document.getElementById('settings-theme-group');
    if (!group) return;

    group.querySelectorAll('[data-set-theme]').forEach(btn => {
        btn.addEventListener('click', () => {
            const newTheme = btn.getAttribute('data-set-theme');
            const html = document.documentElement;

            // 1. Apply theme to <html>
            html.classList.remove('dark', 'light');
            html.classList.add(newTheme);

            // 2. Update active state on the Dark/Light buttons
            group.querySelectorAll('[data-set-theme]').forEach(b => {
                const isActive = b.getAttribute('data-set-theme') === newTheme;
                b.classList.toggle('active', isActive);
                b.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            // 3. Update header theme icon
            document.querySelectorAll('#header-theme-toggle').forEach(b => {
                b.innerHTML = newTheme === 'dark' ? SUN_SVG : MOON_SVG;
                b.setAttribute('aria-label', 'Switch to ' + (newTheme === 'dark' ? 'light' : 'dark') + ' mode');
            });

            // 4. Update frosted header
            const header = document.querySelector('header[role="banner"]');
            if (header) {
                header.classList.remove('frosted-header-dark', 'frosted-header-light');
                header.classList.add(newTheme === 'dark' ? 'frosted-header-dark' : 'frosted-header-light');
            }

            // 5. Save to localStorage
            localStorage.setItem('lr-theme', newTheme);

            // 6. Persist to server
            const csrf = group.getAttribute('data-csrf');
            if (csrf) {
                const fd = new FormData();
                fd.append('_csrf_token', csrf);
                fd.append('theme', newTheme);
                fetch('?route=toggle_theme', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).catch(() => {});
            }
        });
    });
}

// Restore theme from localStorage before first paint (prevents flash)
(function() {
    const saved = localStorage.getItem('lr-theme');
    if (saved && (saved === 'dark' || saved === 'light')) {
        document.documentElement.classList.remove('dark', 'light');
        document.documentElement.classList.add(saved);
    }
})();

// ════════════════════════════════════════════════════════════════════════
// Password Eye Toggle
// ════════════════════════════════════════════════════════════════════════

const EYE_OPEN = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
const EYE_OFF  = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

function initPasswordToggles() {
    document.querySelectorAll('.eye-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.innerHTML = isPassword ? EYE_OFF : EYE_OPEN;
            btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    });
}

// ════════════════════════════════════════════════════════════════════════
// 2FA Tab Switching
// ════════════════════════════════════════════════════════════════════════

function init2FATabs() {
    const tabs = document.querySelectorAll('[data-2fa-tab]');
    if (!tabs.length) return;

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const method = tab.getAttribute('data-2fa-tab');
            tabs.forEach(t => {
                t.classList.toggle('nav-active', t === tab);
                t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
            });
            const methodInput = document.getElementById('2fa_method');
            if (methodInput) methodInput.value = method;
        });
    });
}
