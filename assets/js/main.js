/**
 * main.js – Global JS helpers for OEMS
 */

document.addEventListener('DOMContentLoaded', function () {

    // ── Auto-dismiss flash / alert messages after 5 seconds ──
    document.querySelectorAll('.flash-message, .alert:not(.alert-permanent)').forEach(function (el) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // ── Confirm all delete buttons/forms ─────────────────────
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // ── Client-side password match validation ─────────────────
    const password        = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    if (password && confirmPassword) {
        function validatePasswords() {
            if (confirmPassword.value && confirmPassword.value !== password.value) {
                confirmPassword.setCustomValidity('Passwords do not match.');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        password.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
    }

    // ── Tooltip initialisation ────────────────────────────────
    document.querySelectorAll('[title]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

    // ── Active nav link highlight ─────────────────────────────
    const currentPath = window.location.pathname;
    document.querySelectorAll('.navbar-nav .nav-link').forEach(function (link) {
        const href = link.getAttribute('href');
        if (href && currentPath.endsWith(href.split('/').pop())) {
            link.classList.add('active');
        }
    });

    // ── Form submit prevention on double-click ────────────────
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            const btn = form.querySelector('[type="submit"]');
            if (btn) {
                // Delay to allow native form validation first
                setTimeout(function () {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Please wait…';
                }, 10);
            }
        });
    });
});
