<script type="module">
    /**
     * Restore the operator's preferred table density without requiring storage access.
     */
    function restoreDensity() {
        let compact = false;

        try {
            compact = localStorage.getItem('msp.compact') === '1';
        } catch {}

        document.documentElement.classList.toggle('mp-compact', compact);
        document.querySelector('[data-action="toggle-compact-view"]')?.setAttribute('aria-pressed', String(compact));
    }

    /**
     * Focus Bagisto's existing live search component.
     */
    function focusSearch() {
        document.querySelector('.mp-admin-header input[data-action="nav-search"]')?.focus();
    }

    /**
     * Keep the dock disclosure accessible and return focus on keyboard dismissal.
     */
    function closeDock(returnFocus = false) {
        const panel = document.getElementById('mp-applications-panel');
        const trigger = document.querySelector('[data-action="open-dock-drawer"]');

        if (panel) {
            panel.hidden = true;
        }

        trigger?.setAttribute('aria-expanded', 'false');

        if (returnFocus) {
            trigger?.focus();
        }
    }

    document.addEventListener('click', event => {
        const target = event.target instanceof Element ? event.target : null;
        const control = target?.closest('[data-action]');
        const action = control?.dataset.action;

        if (action === 'open-dock-drawer') {
            const panel = document.getElementById('mp-applications-panel');

            panel.hidden = ! panel.hidden;
            control.setAttribute('aria-expanded', String(! panel.hidden));

            if (! panel.hidden) {
                panel.querySelector('a')?.focus();
            }
        } else if (! target?.closest('#mp-applications-panel')) {
            closeDock();
        }

        if (action === 'toggle-compact-view') {
            const compact = document.documentElement.classList.toggle('mp-compact');

            control.setAttribute('aria-pressed', String(compact));

            try {
                localStorage.setItem('msp.compact', compact ? '1' : '0');
            } catch {}
        }

        if (action === 'nav-search' && control.tagName === 'BUTTON') {
            focusSearch();
        }
    });

    document.addEventListener('keydown', event => {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            focusSearch();
        }

        if (event.key === 'Escape') {
            const panel = document.getElementById('mp-applications-panel');

            if (panel && ! panel.hidden) {
                closeDock(true);
            }
        }
    });

    window.addEventListener('load', restoreDensity);
</script>
