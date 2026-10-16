@pushOnce('scripts')
    <script>
        (() => {
            const initialize = () => {
                const tree = document.querySelector('[data-category-tree]');
                if (!tree || tree.dataset.initialized) return;
                tree.dataset.initialized = 'true';

                const search = tree.querySelector('[data-category-search]');
                const nodes = [...tree.querySelectorAll('[data-category-node]')];
                const buttons = [...tree.querySelectorAll('[data-category-toggle]')];
                const empty = tree.querySelector('[data-category-empty]');
                const count = tree.querySelector('[data-category-count]');
                const normalize = value => value.normalize('NFKC').toLocaleLowerCase().trim();
                const savedExpansion = new Map(buttons.map(button => [button, true]));

                const setExpanded = (button, expanded) => {
                    button.setAttribute('aria-expanded', String(expanded));
                    document.getElementById(button.getAttribute('aria-controls')).hidden = !expanded;
                };

                const updateCount = () => {
                    count.textContent = nodes.filter(node => !node.hidden && node.getClientRects().length).length;
                };

                const filter = () => {
                    const query = normalize(search.value);
                    const matches = new Map();

                    [...nodes].reverse().forEach(node => {
                        const ownMatch = !query || normalize(node.dataset.categoryName).includes(query);
                        const children = node.querySelector(':scope > [data-category-children]');
                        const childMatch = children && [...children.children].some(child => matches.get(child));
                        const matchedAncestor = nodes.some(parent => parent !== node && parent.contains(node) && normalize(parent.dataset.categoryName).includes(query));
                        const visible = ownMatch || childMatch || matchedAncestor;
                        matches.set(node, visible);
                        node.hidden = !visible;
                    });

                    buttons.forEach(button => setExpanded(button, query ? true : savedExpansion.get(button)));
                    empty.hidden = nodes.some(node => !node.hidden);
                    updateCount();
                };

                tree.addEventListener('click', event => {
                    const button = event.target.closest('[data-category-toggle]');
                    if (button) {
                        const expanded = button.getAttribute('aria-expanded') !== 'true';
                        savedExpansion.set(button, expanded);
                        setExpanded(button, expanded);
                        updateCount();
                    }

                    const expand = event.target.closest('[data-category-expand-all]');
                    const collapse = event.target.closest('[data-category-collapse-all]');
                    if (expand || collapse) {
                        search.value = '';
                        buttons.forEach(toggle => {
                            const node = toggle.closest('[data-category-node]');
                            const isRoot = !node.parentElement.closest('[data-category-node]');
                            savedExpansion.set(toggle, Boolean(expand) || isRoot);
                        });
                        filter();
                    }
                });

                tree.addEventListener('keydown', event => {
                    const button = event.target.closest('[data-category-toggle]');
                    if (!button) return;
                    const forward = document.documentElement.dir === 'rtl' ? 'ArrowLeft' : 'ArrowRight';
                    const backward = forward === 'ArrowRight' ? 'ArrowLeft' : 'ArrowRight';
                    if (event.key === forward || event.key === backward) {
                        event.preventDefault();
                        const expanded = event.key === forward;
                        savedExpansion.set(button, expanded);
                        setExpanded(button, expanded);
                        updateCount();
                    }
                });

                search.addEventListener('input', filter);
                filter();
            };

            if (document.readyState === 'complete') setTimeout(initialize, 0);
            else window.addEventListener('load', () => setTimeout(initialize, 0), { once: true });
        })();
    </script>
@endpushOnce
