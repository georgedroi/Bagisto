<script>
    document.addEventListener('input', function (event) {
        if (! event.target.matches('[data-msw-menu-search]')) {
            return;
        }

        const term = event.target.value.trim().toLowerCase();

        document.querySelectorAll('[data-msw-menu-group]').forEach((group) => {
            let matches = 0;

            group.querySelectorAll('a').forEach((link) => {
                const found = link.textContent.toLowerCase().includes(term);

                link.hidden = ! found;
                link.style.display = found ? '' : 'none';
                matches += found ? 1 : 0;
            });

            group.style.display = matches ? '' : 'none';

            if (term) {
                group.dataset.mswWasOpen = group.dataset.mswWasOpen ?? String(group.open);
                group.open = matches > 0;
            } else if (group.dataset.mswWasOpen !== undefined) {
                group.open = group.dataset.mswWasOpen === 'true';
                delete group.dataset.mswWasOpen;
            }
        });
    });

    const mswRoot = document.documentElement;
    let mswFlyTimer = null;
    let mswMenuState = {};

    try {
        const saved = JSON.parse(localStorage.getItem('msw-menu-open') || '{}');

        if (saved && typeof saved === 'object' && ! Array.isArray(saved)) {
            mswMenuState = saved;
        }
    } catch (error) {}

    const mswIsCollapsed = () => mswRoot.classList.contains('msw-collapsed') && window.innerWidth >= 1024;

    function mswHideFly() {
        document.querySelectorAll('[data-msw-menu-group].is-fly').forEach((group) => {
            group.classList.remove('is-fly');
            group.open = group.dataset.mswFlyOpen === 'true';
            delete group.dataset.mswFlyOpen;
            group.querySelector('summary').setAttribute('aria-expanded', 'false');
        });
    }

    function mswShowFly(group) {
        clearTimeout(mswFlyTimer);

        if (group.classList.contains('is-fly')) {
            return;
        }

        mswHideFly();
        group.dataset.mswFlyOpen = String(group.open);
        group.open = true;
        group.classList.add('is-fly');
        group.querySelector('summary').setAttribute('aria-expanded', 'true');

        const links = group.querySelector('.msw-menu-links');

        links.style.top = Math.max(8, Math.min(group.querySelector('summary').getBoundingClientRect().top, window.innerHeight - links.offsetHeight - 8)) + 'px';
    }

    function mswLabelToggle() {
        const toggle = document.querySelector('[data-msw-menu-toggle]');

        toggle.setAttribute('aria-label', mswRoot.classList.contains('msw-collapsed') ? toggle.dataset.mswExpand : toggle.dataset.mswCollapse);
        toggle.setAttribute('aria-expanded', String(! mswRoot.classList.contains('msw-collapsed')));

        document.querySelectorAll('[data-msw-menu-group]').forEach(group => {
            group.querySelector('summary').setAttribute('aria-expanded', String(group.open && (! mswIsCollapsed() || group.classList.contains('is-fly'))));
        });
    }

    function mswSetCollapsed(collapsed) {
        mswHideFly();
        mswRoot.classList.toggle('msw-collapsed', collapsed);
        mswLabelToggle();

        document.cookie = `sidebar_collapsed=${collapsed ? 1 : 0}; path=/; max-age=2592000; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;

        try {
            localStorage.setItem('msw-menu-collapsed', collapsed ? '1' : '0');
        } catch (error) {}
    }

    function mswFocusSearch() {
        if (mswIsCollapsed()) {
            mswSetCollapsed(false);
        }

        document.querySelector('[data-msw-menu-search]').focus();
    }

    mswSetCollapsed(mswRoot.classList.contains('msw-collapsed'));

    document.querySelector('[data-msw-menu-toggle]').addEventListener('click', function () {
        mswSetCollapsed(! mswRoot.classList.contains('msw-collapsed'));
    });

    document.querySelector('[data-msw-search-shortcut]').addEventListener('click', mswFocusSearch);

    document.querySelectorAll('[data-msw-menu-group]').forEach((group) => {
        if (typeof mswMenuState[group.dataset.mswMenuGroup] === 'boolean') {
            group.open = mswMenuState[group.dataset.mswMenuGroup];
        }

        group.addEventListener('mouseenter', () => {
            if (mswIsCollapsed()) {
                mswShowFly(group);
            }
        });
        group.addEventListener('mouseleave', () => {
            if (mswIsCollapsed()) {
                clearTimeout(mswFlyTimer);
                mswFlyTimer = setTimeout(mswHideFly, 150);
            }
        });
        group.addEventListener('focusin', () => {
            if (mswIsCollapsed()) {
                mswShowFly(group);
            }
        });
        group.addEventListener('focusout', event => {
            if (mswIsCollapsed() && ! group.contains(event.relatedTarget)) {
                mswHideFly();
            }
        });
        group.querySelector('summary').addEventListener('click', (event) => {
            if (mswIsCollapsed()) {
                event.preventDefault();
                mswShowFly(group);
            } else if (! document.querySelector('[data-msw-menu-search]').value.trim()) {
                mswMenuState[group.dataset.mswMenuGroup] = ! group.open;

                try {
                    localStorage.setItem('msw-menu-open', JSON.stringify(mswMenuState));
                } catch (error) {}
            }
        });
        group.addEventListener('toggle', () => {
            if (! mswIsCollapsed() && ! document.querySelector('[data-msw-menu-search]').value.trim()) {
                group.querySelector('summary').setAttribute('aria-expanded', String(group.open));
                mswMenuState[group.dataset.mswMenuGroup] = group.open;

                try {
                    localStorage.setItem('msw-menu-open', JSON.stringify(mswMenuState));
                } catch (error) {}
            }
        });
    });

    mswLabelToggle();

    const mswMenuList = document.querySelector('.msw-menu-list');

    try {
        mswMenuList.scrollTop = Number(sessionStorage.getItem('msw-menu-scroll')) || 0;
    } catch (error) {}

    mswMenuList.addEventListener('scroll', () => {
        mswHideFly();

        try {
            sessionStorage.setItem('msw-menu-scroll', String(mswMenuList.scrollTop));
        } catch (error) {}
    }, { passive: true });

    document.addEventListener('click', event => {
        if (! event.target.closest('.msw-menu')) {
            mswHideFly();
        }
    });

    window.addEventListener('resize', () => {
        mswHideFly();
        mswLabelToggle();
    });

    document.addEventListener('keydown', function (event) {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            mswFocusSearch();
        }

        if (event.key === 'Escape') {
            const group = event.target.closest('[data-msw-menu-group]') ?? document.querySelector('[data-msw-menu-group].is-fly');

            if (group) {
                event.preventDefault();
                group.querySelector('summary').focus();

                if (mswIsCollapsed()) {
                    mswHideFly();
                } else {
                    group.open = false;
                }
            }
        }
    });
</script>
