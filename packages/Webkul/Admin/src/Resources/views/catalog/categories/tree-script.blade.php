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

                const deleteDialog = tree.querySelector('[data-category-delete-dialog]');
                const deleteForm = tree.querySelector('[data-category-delete-form]');
                const deleteConfirm = tree.querySelector('[data-category-delete-confirm]');
                const deleteProducts = tree.querySelector('[data-category-delete-products]');
                const deleteAck = tree.querySelector('[data-category-delete-ack]');
                const deletePassword = tree.querySelector('[data-category-delete-password]');
                const deleteError = tree.querySelector('[data-category-delete-error]');
                let deletionReview = null;
                let deletionNode = null;
                let deletionController = null;
                const format = (key, values) => Object.entries(values).reduce((text, [name, value]) => text.replace(':' + name, String(value)), message(key));
                const syncDeleteChoice = () => {
                    if (!deleteDialog) return;
                    const deletingProducts = deleteProducts.checked;
                    tree.querySelector('[data-category-delete-ack-label]').hidden = !deletingProducts;
                    deleteAck.required = deletingProducts;
                    deleteProducts.disabled = busy || !deletionReview?.can_delete_products || !deletionReview?.review.product_count;
                    deleteConfirm.disabled = busy || !deletionReview;
                    if (deletionReview) deleteConfirm.textContent = format(deletingProducts ? 'delete-confirm-products' : 'delete-confirm', { categories: deletionReview.review.category_count, products: deletionReview.review.product_count });
                };
                const showScopeList = (selector, entries, label) => {
                    const list = tree.querySelector(selector);
                    list.replaceChildren();
                    entries.forEach(entry => { const item = document.createElement('li'); item.textContent = label(entry); list.append(item); });
                    if (!entries.length) { const item = document.createElement('li'); item.textContent = message('delete-none'); list.append(item); }
                };
                const openDeletion = async node => {
                    if (!deleteDialog || !tree.dataset.deletePreviewUrl || busy || locked || isProtected(node)) return;
                    deletionController?.abort();
                    deletionController = new AbortController();
                    const controller = deletionController;
                    deletionNode = node;
                    deletionReview = null;
                    deletePassword.value = '';
                    deleteAck.checked = false;
                    deleteProducts.checked = false;
                    deleteError.textContent = '';
                    tree.querySelector('[data-category-delete-title]').textContent = message('delete-title') + ': ' + node.dataset.categoryName;
                    tree.querySelector('[data-category-delete-summary]').textContent = message('delete-loading');
                    tree.querySelector('[data-category-delete-details]').hidden = true;
                    tree.querySelector('[data-category-delete-choices]').hidden = true;
                    syncDeleteChoice();
                    deleteDialog.showModal();
                    try {
                        const response = await fetch(tree.dataset.deletePreviewUrl, {
                            method: 'POST', credentials: 'same-origin', signal: controller.signal,
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tree.dataset.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify({ category_id: idOf(node) }),
                        });
                        const result = await response.json();
                        if (controller.signal.aborted) return;
                        if (!response.ok) { deleteError.textContent = result.message || message('error-delete-generic'); return; }
                        deletionReview = result;
                        const review = result.review;
                        tree.querySelector('[data-category-delete-summary]').textContent = format('delete-summary', { categories: review.category_count, products: review.product_count });
                        tree.querySelector('[data-category-delete-sample]').textContent = message('delete-sample');
                        tree.querySelector('[data-category-delete-shared]').textContent = format('delete-shared', { count: review.shared_count });
                        tree.querySelector('[data-category-delete-shared]').hidden = review.shared_count === 0;
                        tree.querySelector('[data-category-delete-variants]').textContent = format('delete-variants', { count: review.variant_count });
                        tree.querySelector('[data-category-delete-variants]').hidden = review.variant_count === 0;
                        tree.querySelector('[data-category-delete-permission]').hidden = result.can_delete_products;
                        showScopeList('[data-category-delete-categories]', review.categories, category => category.name);
                        showScopeList('[data-category-delete-products-list]', review.products, product => product.id + ' · ' + product.sku);
                        tree.querySelector('[data-category-delete-details]').hidden = false;
                        tree.querySelector('[data-category-delete-choices]').hidden = false;
                        syncDeleteChoice();
                    } catch (error) {
                        if (!controller.signal.aborted) deleteError.textContent = message('error-delete-generic');
                    }
                };
                tree.addEventListener('click', event => {
                    const button = event.target.closest('[data-category-delete]');
                    if (button) openDeletion(button.closest('[data-category-node]'));
                    if (event.target.closest('[data-category-delete-cancel]')) { deletePassword.value = ''; deletionController?.abort(); deleteDialog.close(); }
                });
                deleteProducts?.addEventListener('change', () => { deleteAck.checked = false; syncDeleteChoice(); });
                deleteDialog?.addEventListener('cancel', event => { if (busy) event.preventDefault(); else { deletePassword.value = ''; deletionController?.abort(); } });
                deleteDialog?.addEventListener('close', () => { deletionController?.abort(); deletePassword.value = ''; deleteAck.checked = false; });
                deleteForm?.addEventListener('submit', async event => {
                    event.preventDefault();
                    if (busy || locked || !deletionReview || !deleteForm.reportValidity()) return;
                    const payload = {
                        category_id: idOf(deletionNode), preview_token: deletionReview.preview_token, revision: deletionReview.review.revision,
                        delete_products: deleteProducts.checked, acknowledge_products: deleteAck.checked, approver_email: tree.querySelector('[data-category-delete-approver]').value, password: deletePassword.value,
                    };
                    deletePassword.value = '';
                    busy = true;
                    tree.setAttribute('aria-busy', 'true');
                    tree.querySelectorAll('button, input, select').forEach(control => control.disabled = true);
                    deleteError.textContent = '';
                    tree.querySelector('[data-category-delete-summary]').textContent = message('delete-saving');
                    try {
                        const response = await fetch(tree.dataset.deleteUrl, {
                            method: 'POST', credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': tree.dataset.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                            body: JSON.stringify(payload),
                        });
                        payload.password = '';
                        const result = await response.json();
                        if (!response.ok) {
                            deleteError.textContent = response.status === 429 ? message('error-delete-throttle') : result.errors?.password ? message('error-delete-password') : result.message || message('error-delete-generic');
                            if ([409, 403, 419].includes(response.status)) deletionReview = null;
                            if (response.status >= 500) { locked = true; deletionReview = null; }
                            return;
                        }
                        const replacement = new DOMParser().parseFromString(result.html, 'text/html').querySelector('[data-category-tree]');
                        if (!replacement) throw new Error('Invalid category response');
                        const expansion = Object.fromEntries(buttons.map(button => [button.closest('[data-category-node]').dataset.categoryId, savedExpansion.get(button)]));
                        deleteDialog.close();
                        tree.replaceWith(document.adoptNode(replacement));
                        initialize({ expansion, search: search.value, message: result.message, focusId: result.focus_id });
                    } catch (error) {
                        payload.password = '';
                        locked = true;
                        deletionReview = null;
                        deleteError.textContent = message('error-delete-generic');
                    } finally {
                        busy = false;
                        tree.removeAttribute('aria-busy');
                        tree.querySelectorAll('button, input, select').forEach(control => control.disabled = false);
                        syncDeleteChoice();
                        if (deletionReview && deleteDialog.open) tree.querySelector('[data-category-delete-summary]').textContent = format('delete-summary', { categories: deletionReview.review.category_count, products: deletionReview.review.product_count });
                        deletePassword.focus();
                        if (locked) tree.querySelectorAll('[data-category-move], [data-category-drag], [data-category-delete]').forEach(control => control.disabled = true);
                    }
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
                if (state.focusId) (byId(state.focusId)?.querySelector(':scope > .mp-category-row [data-category-move]') || byId(state.focusId)?.querySelector(':scope > .mp-category-row button, :scope > .mp-category-row a'))?.focus({ preventScroll: true });
                const requestedDeletion = byId(tree.dataset.deleteCategory);
                if (requestedDeletion && requestedDeletion.querySelector(':scope > .mp-category-row [data-category-delete]')) openDeletion(requestedDeletion);
                else if (requestedDeletion && isProtected(requestedDeletion)) announce(message('error-root'), true);
            };

            if (document.readyState === 'complete') setTimeout(initialize, 0);
            else window.addEventListener('load', () => setTimeout(initialize, 0), { once: true });
        })();
    </script>
@endpushOnce
