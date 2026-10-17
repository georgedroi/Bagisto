<script type="module">
    /**
     * Keep disclosure state accessible without depending on Vue's render timing.
     */
    function synchronizeNavigation() {
        const shell = document.querySelector('.mp-admin-shell');
        const isCollapsed = shell?.classList.contains('sidebar-collapsed') ?? false;

        document.querySelectorAll('[data-mp-navigation] [data-mp-group]').forEach(group => {
            const button = group.querySelector('[data-mp-disclosure]');

            if (! button) {
                return;
            }

            const panel = document.getElementById(button.getAttribute('aria-controls'));
            const isDesktop = group.closest('[data-mp-navigation]').dataset.mpNavigation === 'desktop';
            const isOpen = group.dataset.mpOpen === '1' && (! isDesktop || ! isCollapsed);

            button.setAttribute('aria-expanded', String(isOpen));

            if (panel) {
                panel.hidden = ! isOpen;
            }
        });

        document.querySelectorAll('[data-mp-sidebar-toggle]').forEach(button => {
            const label = isCollapsed ? button.dataset.expandLabel : button.dataset.collapseLabel;

            button.setAttribute('aria-expanded', String(! isCollapsed));
            button.setAttribute('aria-label', label);
            button.setAttribute('title', label);
            button.querySelector('[data-mp-collapse-label]').textContent = label;
        });
    }

    /**
     * Preserve Bagisto's sidebar cookie and layout offset convention.
     */
    function setSidebarCollapsed(isCollapsed) {
        const shell = document.querySelector('.mp-admin-shell');

        if (! shell) {
            return;
        }

        shell.classList.toggle('sidebar-collapsed', isCollapsed);
        shell.classList.toggle('sidebar-not-collapsed', ! isCollapsed);

        const expiry = new Date();

        expiry.setMonth(expiry.getMonth() + 1);

        document.cookie = `sidebar_collapsed=${isCollapsed ? 1 : 0}; path=/; expires=${expiry.toUTCString()}; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;

        synchronizeNavigation();
    }

    /**
     * Use delegation so Vue mounting cannot discard navigation handlers.
     */
    function handleNavigationClick(event) {
        if (! (event.target instanceof Element)) {
            return;
        }

        const toggle = event.target.closest('[data-mp-sidebar-toggle]');

        if (toggle) {
            setSidebarCollapsed(! document.querySelector('.mp-admin-shell').classList.contains('sidebar-collapsed'));

            return;
        }

        const disclosure = event.target.closest('[data-mp-disclosure]');

        if (! disclosure) {
            return;
        }

        const group = disclosure.closest('[data-mp-group]');
        const isDesktop = group.closest('[data-mp-navigation]').dataset.mpNavigation === 'desktop';
        const isCollapsed = document.querySelector('.mp-admin-shell')?.classList.contains('sidebar-collapsed');

        if (isDesktop && isCollapsed) {
            setSidebarCollapsed(false);
            group.dataset.mpOpen = '1';
        } else {
            group.dataset.mpOpen = group.dataset.mpOpen === '1' ? '0' : '1';
        }

        synchronizeNavigation();
    }

    /**
     * Close the current disclosure and return keyboard focus to its control.
     */
    function handleNavigationKeydown(event) {
        if (event.key !== 'Escape' || ! (event.target instanceof Element)) {
            return;
        }

        const group = event.target.closest('[data-mp-group]');
        const button = group?.querySelector('[data-mp-disclosure]');

        if (! button || button.getAttribute('aria-expanded') !== 'true') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        group.dataset.mpOpen = '0';
        synchronizeNavigation();
        button.focus();
    }

    /**
     * Keep the sidebar between the header and any visible local debug toolbar.
     */
    function updateNavigationHeaderHeight() {
        const header = document.querySelector('.mp-admin-header');

        if (header) {
            document.documentElement.style.setProperty('--mp-admin-header-height', `${header.getBoundingClientRect().height}px`);
        }

        const sidebar = document.querySelector('.mp-admin-sidebar');
        const sidebarBounds = sidebar?.getBoundingClientRect();
        let overlayHeight = 0;

        if (sidebarBounds && sidebarBounds.width > 0) {
            document.querySelectorAll('.phpdebugbar, .phpdebugbar-resize-handle, .phpdebugbar-openhandler').forEach(element => {
                if (navigationResizeObserver && ! observedNavigationElements.has(element)) {
                    observedNavigationElements.add(element);
                    navigationResizeObserver.observe(element);
                }

                const bounds = element.getBoundingClientRect();
                const style = getComputedStyle(element);

                if (element.getClientRects().length > 0 && style.visibility !== 'hidden'
                    && bounds.width > 0 && bounds.height > 0
                    && bounds.right > sidebarBounds.left && bounds.left < sidebarBounds.right
                    && bounds.bottom > 0 && bounds.top < window.innerHeight) {
                    overlayHeight = Math.max(overlayHeight, window.innerHeight - Math.max(0, bounds.top) + 8);
                }
            });
        }

        document.documentElement.style.setProperty('--mp-admin-bottom-overlay-height', `${Math.ceil(overlayHeight)}px`);
    }

    let navigationFrame = null;

    /**
     * Coalesce toolbar insertion, minimization and resize updates into one frame.
     */
    function scheduleNavigationLayout() {
        if (navigationFrame !== null) {
            return;
        }

        navigationFrame = requestAnimationFrame(() => {
            navigationFrame = null;
            updateNavigationHeaderHeight();
        });
    }

    const navigationResizeObserver = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(scheduleNavigationLayout) : null;
    const observedNavigationElements = new WeakSet();

    document.addEventListener('click', handleNavigationClick);
    document.addEventListener('keydown', handleNavigationKeydown, true);
    window.addEventListener('resize', scheduleNavigationLayout);
    window.addEventListener('load', () => {
        synchronizeNavigation();
        updateNavigationHeaderHeight();

        const header = document.querySelector('.mp-admin-header');

        if (header && navigationResizeObserver) {
            navigationResizeObserver.observe(header);
        }

        new MutationObserver(scheduleNavigationLayout).observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style', 'hidden'],
        });
    });
</script>
