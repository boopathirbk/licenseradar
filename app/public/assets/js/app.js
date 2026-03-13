/**
 * LicenseRadar — Client-Side JavaScript
 * Theme toggle, 2FA tab switching, and utility functions.
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    init2FATabs();
});

// ═══════════════════════════════════════════════════════════════════════
// Theme Toggle
// ═══════════════════════════════════════════════════════════════════════

function initThemeToggle() {
    const btn = document.getElementById('theme-toggle');
    if (!btn) return;

    btn.addEventListener('click', () => {
        const html = document.documentElement;
        const current = html.classList.contains('dark') ? 'dark' : 'light';
        const next = current === 'dark' ? 'light' : 'dark';

        html.classList.remove(current);
        html.classList.add(next);

        // Persist via form submission to update DB
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?route=settings';
        form.style.display = 'none';

        const csrf = document.querySelector('input[name="_csrf_token"]');
        if (csrf) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_csrf_token';
            csrfInput.value = csrf.value;
            form.appendChild(csrfInput);
        }

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_theme';
        form.appendChild(actionInput);

        const themeInput = document.createElement('input');
        themeInput.type = 'hidden';
        themeInput.name = 'theme';
        themeInput.value = next;
        form.appendChild(themeInput);

        document.body.appendChild(form);
        form.submit();
    });
}

// ═══════════════════════════════════════════════════════════════════════
// 2FA Method Tabs
// ═══════════════════════════════════════════════════════════════════════

function init2FATabs() {
    const tabs = document.querySelectorAll('.method-tab');
    const methodInput = document.getElementById('2fa-method');
    if (!tabs.length || !methodInput) return;

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const method = tab.dataset.method;
            methodInput.value = method;

            // Update active state
            tabs.forEach(t => {
                t.setAttribute('aria-selected', 'false');
                t.style.background = 'transparent';
                t.style.color = '';
            });
            tab.setAttribute('aria-selected', 'true');
            tab.style.background = 'var(--tab-active-bg, rgba(63, 63, 70, 0.4))';
            tab.style.color = 'var(--tab-active-color, #f4f4f5)';
        });

        // Set initial active state
        if (tab.getAttribute('aria-selected') === 'true') {
            tab.style.background = 'var(--tab-active-bg, rgba(63, 63, 70, 0.4))';
            tab.style.color = 'var(--tab-active-color, #f4f4f5)';
        }
    });
}
