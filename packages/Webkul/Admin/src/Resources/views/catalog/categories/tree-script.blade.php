@pushOnce('scripts')
    <script>
        (() => {
            const initialize = (state = {}) => {
                const tree = document.querySelector('[data-category-tree]');
                if (!tree || tree.dataset.initialized) return;
                tree.dataset.initialized = 'true';

                const search = tree.querySelector('[data-category-search]');
                const nodes = [...tree.querySelectorAll('[data-category-node]')];
                const buttons = [...tree.querySelectorAll('[data-category-toggle]')];
                const empty = tree.querySelector('[data-category-empty]');
                const count = tree.querySelector('[data-category-count]');
                const normalize = value => value.normalize('NFKC').toLocaleLowerCase().trim();
                const savedExpansion = new Map(buttons.map(button => [button, state.expansion?.[button.closest('[data-category-node]').dataset.categoryId] ?? true]));

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

                const feedback = tree.querySelector('[data-category-feedback]');
                const messages = tree.querySelector('[data-category-messages]');
                const message = key => messages.getAttribute('data-' + key);
                const byId = id => nodes.find(node => node.dataset.categoryId === String(id));
                const idOf = node => Number(node.dataset.categoryId);
                const parentOf = node => byId(node.dataset.parentId);
                const isProtected = node => node.dataset.protected === 'true';
                const scope = node => {
                    for (let current = node; current; current = parentOf(current)) {
                        if (isProtected(current)) return idOf(current);
                    }
                };
                const canMove = (node, parent) => node && parent && !isProtected(node)
                    && !node.contains(parent) && scope(node) === scope(parent)
                    && !node.querySelector('[data-protected="true"]');
                const announce = (text, error = false) => {
                    feedback.textContent = text;
                    feedback.classList.toggle('is-error', error);
                };
                let busy = false;
                let locked = false;
                let dragNode = null;
                let movingNode = null;
                const dialog = tree.querySelector('[data-category-dialog]');
                const parentSelect = tree.querySelector('[data-category-parent]');
                const beforeSelect = tree.querySelector('[data-category-before]');

                const saveMove = async (node, parentId, beforeId) => {
                    if (busy || locked || !canMove(node, byId(parentId))) return;
                    busy = true;
                    tree.setAttribute('aria-busy', 'true');
                    tree.querySelectorAll('button, input, select').forEach(control => control.disabled = true);
                    if (dialog?.open) dialog.close();
                    announce(message('saving'));
                    const expansion = Object.fromEntries(buttons.map(button => [button.closest('[data-category-node]').dataset.categoryId, savedExpansion.get(button)]));
                    expansion[String(parentId)] = true;
                    try {
                        const response = await fetch(tree.dataset.moveUrl, {
                            method: 'POST', credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tree.dataset.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ category_id: idOf(node), parent_id: parentId, before_id: beforeId, revision: tree.dataset.revision }),
                        });
                        const result = await response.json();
                        if (!response.ok) {
                            locked = response.status !== 422;
                            announce(typeof result.message === 'string' ? result.message : message('error-generic'), true);
                            return;
                        }
                        const documentResult = new DOMParser().parseFromString(result.html, 'text/html');
                        const replacement = documentResult.querySelector('[data-category-tree]');
                        if (!replacement) throw new Error('Invalid category response');
                        const searchValue = search.value;
                        tree.replaceWith(document.adoptNode(replacement));
                        initialize({ expansion, search: searchValue, message: result.message, focusId: idOf(node) });
                    } catch (error) {
                        locked = true;
                        announce(message('error-generic'), true);
                    } finally {
                        busy = false;
                        tree.removeAttribute('aria-busy');
                        tree.querySelectorAll('button, input, select').forEach(control => control.disabled = false);
                        if (locked) tree.querySelectorAll('[data-category-move], [data-category-drag]').forEach(control => control.disabled = true);
                    }
                };

                const pathName = node => {
                    const parts = [];
                    for (let current = node; current; current = parentOf(current)) parts.unshift(current.dataset.categoryName);
                    return parts.join(' / ');
                };
                const fillBefore = () => {
                    beforeSelect.replaceChildren(new Option(message('last'), ''));
                    nodes.filter(node => node.dataset.parentId === parentSelect.value && node !== movingNode).forEach(node => beforeSelect.add(new Option(node.dataset.categoryName, node.dataset.categoryId)));
                };
                const openMove = node => {
                    if (!dialog || busy || locked || isProtected(node)) return;
                    movingNode = node;
                    parentSelect.replaceChildren();
                    nodes.filter(parent => canMove(node, parent)).forEach(parent => parentSelect.add(new Option(pathName(parent), parent.dataset.categoryId)));
                    parentSelect.value = node.dataset.parentId;
                    fillBefore();
                    const next = node.nextElementSibling;
                    beforeSelect.value = next?.dataset.categoryId || '';
                    tree.querySelector('[data-category-move-title]').textContent = message('move-title') + ': ' + node.dataset.categoryName;
                    dialog.showModal();
                    parentSelect.focus();
                };
                tree.addEventListener('click', event => {
                    const move = event.target.closest('[data-category-move], [data-category-drag]');
                    if (move) openMove(move.closest('[data-category-node]'));
                    if (event.target.closest('[data-category-cancel]')) dialog.close();
                });
                parentSelect?.addEventListener('change', fillBefore);
                tree.querySelector('[data-category-move-form]')?.addEventListener('submit', event => {
                    event.preventDefault();
                    saveMove(movingNode, Number(parentSelect.value), beforeSelect.value ? Number(beforeSelect.value) : null);
                });

                const clearDrop = () => tree.querySelectorAll('[data-drop]').forEach(row => row.removeAttribute('data-drop'));
                const destination = event => {
                    const row = event.target.closest('.mp-category-row');
                    if (!row || !dragNode || busy || locked || search.value.trim()) return null;
                    const target = row.closest('[data-category-node]');
                    if (target === dragNode || dragNode.contains(target)) return null;
                    const box = row.getBoundingClientRect();
                    const ratio = (event.clientY - box.top) / box.height;
                    const placement = ratio < .25 ? 'before' : ratio > .75 ? 'after' : 'inside';
                    const parent = placement === 'inside' ? target : parentOf(target);
                    if (!canMove(dragNode, parent)) return null;
                    let before = placement === 'before' ? target : placement === 'after' ? target.nextElementSibling : null;
                    if (before === dragNode) before = before.nextElementSibling;
                    return { row, placement, parentId: idOf(parent), beforeId: before ? idOf(before) : null };
                };
                tree.addEventListener('dragstart', event => {
                    const handle = event.target.closest('[data-category-drag]');
                    if (!handle || busy || locked || search.value.trim()) { event.preventDefault(); return; }
                    dragNode = handle.closest('[data-category-node]');
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', dragNode.dataset.categoryId);
                });
                tree.addEventListener('dragover', event => {
                    clearDrop();
                    const drop = destination(event);
                    if (!drop) return;
                    event.preventDefault();
                    event.dataTransfer.dropEffect = 'move';
                    drop.row.dataset.drop = drop.placement;
                    announce(message('drop-' + drop.placement) + ': ' + drop.row.querySelector('.mp-category-name').textContent);
                });
                tree.addEventListener('drop', event => {
                    const drop = destination(event);
                    clearDrop();
                    if (!drop) return;
                    event.preventDefault();
                    saveMove(dragNode, drop.parentId, drop.beforeId);
                    dragNode = null;
                });
                tree.addEventListener('dragend', () => { dragNode = null; clearDrop(); if (!busy) announce(''); });
                tree.addEventListener('dragleave', event => { if (!tree.contains(event.relatedTarget)) clearDrop(); });

                search.value = state.search || '';
                search.addEventListener('input', filter);
                filter();
                if (state.message) announce(state.message);
                if (state.focusId) byId(state.focusId)?.querySelector(':scope > .mp-category-row [data-category-move]')?.focus({ preventScroll: true });
            };

            if (document.readyState === 'complete') setTimeout(initialize, 0);
            else window.addEventListener('load', () => setTimeout(initialize, 0), { once: true });
        })();
    </script>
@endpushOnce
