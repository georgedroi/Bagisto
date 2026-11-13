<script>
    document.addEventListener('submit', async function (event) {
        const form = event.target.closest('form');

        if (! form || form.dataset.mswJson === undefined) {
            return;
        }

        event.preventDefault();

        if (form.dataset.mswConfirm && ! window.confirm(form.dataset.mswConfirm)) {
            return;
        }

        if (form.elements.special_price && ! form.elements.special_price.value) {
            form.elements.special_price_from.value = '';
            form.elements.special_price_to.value = '';
        }

        const root = form.closest('.msw');
        const button = form.querySelector('button[type="submit"]');
        const message = form.querySelector('[data-msw-message]');

        button.disabled = true;
        message.classList.remove('is-error');
        message.textContent = root.dataset.mswLoading;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (! response.ok || (form.dataset.mswStrict !== undefined && response.redirected)) {
                const body = await response.json().catch(() => ({}));

                throw new Error(body.message || root.dataset.mswFailed);
            }

            message.textContent = root.dataset.mswSaved;
            window.location.reload();
        } catch (error) {
            message.classList.add('is-error');
            message.textContent = error.message || root.dataset.mswFailed;
            button.disabled = false;
        }
    });

    const mswIso = (date) => date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');

    function mswShowText(element, value) {
        element.textContent = value || element.dataset.mswPlaceholder;
        element.classList.toggle('is-empty', ! value);
    }

    let mswBatchKey = 0;

    const mswCalendarIcon = '<svg class="msw-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg>';

    function mswStockInput(name, value, label) {
        const input = document.createElement('input');

        input.type = 'number';
        input.name = name;
        input.step = '1';
        input.required = true;
        input.value = value;
        input.setAttribute('aria-label', label);

        return input;
    }

    function mswHiddenInput(name, value) {
        const input = document.createElement('input');

        input.type = 'hidden';
        input.name = name;
        input.value = value;

        return input;
    }

    function mswBatchRow(holder, sourceId, batch) {
        const row = document.createElement('tr');
        const key = mswBatchKey++;
        const prefix = 'batches[' + sourceId + '][' + key + ']';
        const quantity = mswStockInput(prefix + '[quantity]', batch.quantity, holder.dataset.mswBatch);
        const dates = document.createElement('button');
        const remove = document.createElement('button');

        row.dataset.mswBatchRow = sourceId;
        quantity.min = '1';

        dates.type = 'button';
        dates.className = 'msw-batch-date';
        dates.dataset.mswBatchDate = '';
        dates.setAttribute('aria-label', holder.dataset.mswDates);
        dates.innerHTML = '<span></span>' + mswCalendarIcon;
        dates.append(mswHiddenInput(prefix + '[ship_from]', batch.ship_from), mswHiddenInput(prefix + '[ship_to]', batch.ship_to));

        remove.type = 'button';
        remove.className = 'msw-batch-link';
        remove.dataset.mswBatchRemove = '';
        remove.textContent = holder.dataset.mswRemove;

        row.insertCell();
        row.insertCell().dataset.mswBatchName = '';
        row.insertCell().appendChild(quantity);
        row.insertCell().appendChild(dates);
        row.insertCell().appendChild(remove);

        mswShowBatchDates(dates);

        return row;
    }

    function mswShowBatchDates(button) {
        const [from, to] = button.querySelectorAll('input');
        const short = (iso) => iso.slice(5, 7) + '/' + iso.slice(8, 10);

        button.querySelector('span').textContent = from.value && to.value ? short(from.value) + ' ~ ' + short(to.value) : '';
    }

    function mswFillStock(form, sources) {
        const holder = form.querySelector('[data-msw-sources]');

        holder.replaceChildren();

        sources.forEach((source) => {
            const row = holder.insertRow();
            const add = holder.insertRow();
            const link = document.createElement('button');

            row.insertCell().textContent = source.name;
            row.insertCell().textContent = holder.dataset.mswReady;

            const input = mswStockInput('inventories[' + source.id + ']', source.qty, source.name);

            input.min = '0';
            input.dataset.mswReadyInput = '';
            row.insertCell().appendChild(input);
            row.insertCell().textContent = holder.dataset.mswSla;
            row.insertCell();

            (source.batches || []).forEach((batch) => add.before(mswBatchRow(holder, source.id, batch)));

            link.type = 'button';
            link.className = 'msw-batch-link';
            link.textContent = holder.dataset.mswAdd;
            link.dataset.mswBatchAdd = source.id;
            add.insertCell();

            const cell = add.insertCell();

            cell.colSpan = 4;
            cell.appendChild(link);
        });

        mswStockTotal(form);
    }

    function mswStockTotal(form) {
        const holder = form.querySelector('[data-msw-sources]');

        form.querySelector('[data-msw-total]').textContent = [...form.querySelectorAll('[data-msw-ready-input]')]
            .reduce((total, input) => total + (parseInt(input.value, 10) || 0), 0);

        holder.querySelectorAll('[data-msw-batch-add]').forEach((link) => {
            const rows = [...holder.querySelectorAll('[data-msw-batch-row="' + link.dataset.mswBatchAdd + '"]')];

            rows.forEach((row, index) => {
                row.querySelector('[data-msw-batch-name]').textContent = holder.dataset.mswBatch.replace(':n', index + 1);
            });

            link.disabled = rows.length >= Number(holder.dataset.mswMax)
                || rows.some((row) => [...row.querySelectorAll('input')].some((input) => ! input.value));
        });

        form.querySelector('[data-msw-preorder]').hidden = ! holder.querySelector('[data-msw-batch-row]');
    }

    function mswFillPrice(form, data) {
        ['price', 'special_price'].forEach((name) => {
            form.elements[name].value = data[name] === null || data[name] === undefined ? '' : Number(data[name]).toFixed(2);
        });

        ['special_price_from', 'special_price_to'].forEach((name) => {
            form.elements[name].value = (data[name] || '').slice(0, 10);
        });

        form.querySelectorAll('[data-msw-currency]').forEach((element) => {
            element.textContent = data.currency;
        });

        const pop = form.querySelector('[data-msw-date]');

        pop.hidden = true;
        pop.dataset.mswConfirmed = form.elements.special_price_from.value || form.elements.special_price_to.value
            ? 'set'
            : (data.special_price ? 'long' : '');
    }

    async function mswOpenEditor(dialog) {
        const root = dialog.closest('.msw');
        const form = dialog.querySelector('form');
        const button = form.querySelector('button[type="submit"]');
        const message = form.querySelector('[data-msw-message]');

        dialog.showModal();
        form.reset();
        message.classList.remove('is-error');
        message.textContent = '';

        if (! dialog.dataset.mswLoad) {
            return;
        }

        button.disabled = true;
        message.textContent = root.dataset.mswLoading;

        try {
            const response = await fetch(dialog.dataset.mswLoad, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const body = await response.json();

            if (! response.ok) {
                throw new Error(body.message || root.dataset.mswFailed);
            }

            if (dialog.dataset.mswKind === 'price') {
                mswFillPrice(form, body.data);
            } else {
                mswFillStock(form, body.data.sources);
            }

            button.disabled = false;
            message.textContent = '';
        } catch (error) {
            message.classList.add('is-error');
            message.textContent = error.message || root.dataset.mswFailed;
        }
    }

    function mswOpenDates(trigger) {
        const form = trigger.closest('form');
        const pop = form.querySelector('[data-msw-date]');
        const from = form.elements.special_price_from.value;
        const shown = new Date((from || mswIso(new Date())) + 'T00:00:00');

        pop.mswState = { mode: pop.dataset.mswConfirmed || '', from, to: form.elements.special_price_to.value, year: shown.getFullYear(), month: shown.getMonth() };
        pop.querySelector('[data-msw-date-options]').hidden = true;
        pop.querySelector('[data-msw-calendar]').hidden = true;
        pop.hidden = false;

        const rect = trigger.getBoundingClientRect();
        const width = pop.offsetWidth;
        const room = pop.closest('dialog').clientWidth > 700 ? 470 : 620;

        pop.style.left = Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8)) + 'px';
        pop.style.top = Math.max(8, Math.min(rect.top - 20, window.innerHeight - room)) + 'px';

        mswDrawDates(pop);
    }

    function mswDrawDates(pop) {
        const state = pop.mswState;
        const labels = { long: pop.querySelector('[data-msw-date-pick="long"]').textContent, set: pop.querySelector('[data-msw-date-pick="set"]').textContent };
        const mode = pop.querySelector('[data-msw-date-mode]');
        const complete = state.mode === 'long' || (state.mode === 'set' && state.from && state.to);

        mode.querySelector('span').dataset.mswPlaceholder = mode.dataset.mswPlaceholder;
        mswShowText(mode.querySelector('span'), labels[state.mode]);

        pop.querySelector('[data-msw-date-range]').hidden = state.mode !== 'set';
        mswShowText(pop.querySelector('[data-msw-date-from]'), state.from);
        mswShowText(pop.querySelector('[data-msw-date-to]'), state.to);
        pop.querySelector('[data-msw-date-confirm]').disabled = ! complete;
        pop.querySelector('[data-msw-calendar-ok]').disabled = ! (state.from && state.to);
        pop.querySelector('[data-msw-date-field]').classList.toggle('is-active', ! pop.querySelector('[data-msw-calendar]').hidden);

        mswDrawCalendar(pop);
    }

    function mswDrawCalendar(pop) {
        const state = pop.mswState;
        const lang = document.documentElement.lang || 'en';
        const head = pop.querySelector('[data-msw-calendar-head]');
        const grid = pop.querySelector('[data-msw-calendar-grid]');
        const arrow = (step, icon, label) => {
            const button = document.createElement('button');

            button.type = 'button';
            button.dataset.mswStep = step;
            button.setAttribute('aria-label', label);
            button.innerHTML = '<svg class="msw-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' + icon + '"/></svg>';

            return button;
        };
        const title = document.createElement('strong');

        title.textContent = state.year + ' ' + new Intl.DateTimeFormat(lang, { month: 'short' }).format(new Date(state.year, state.month, 1));

        head.replaceChildren(
            arrow(-12, 'm12 6-6 6 6 6M19 6l-6 6 6 6', pop.dataset.mswPrevYear),
            arrow(-1, 'm15 6-6 6 6 6', pop.dataset.mswPrevMonth),
            title,
            arrow(1, 'm9 6 6 6-6 6', pop.dataset.mswNextMonth),
            arrow(12, 'm5 6 6 6-6 6M12 6l6 6-6 6', pop.dataset.mswNextYear)
        );

        grid.replaceChildren();

        for (let day = 0; day < 7; day++) {
            const name = document.createElement('span');
            const text = new Intl.DateTimeFormat(lang, { weekday: 'short' }).format(new Date(2026, 10, 1 + day));

            name.textContent = lang.startsWith('en') ? text.slice(0, 2) : text;
            grid.appendChild(name);
        }

        const first = new Date(state.year, state.month, 1);

        for (let cell = 0; cell < 42; cell++) {
            const date = new Date(state.year, state.month, 1 - first.getDay() + cell);
            const iso = mswIso(date);
            const button = document.createElement('button');

            button.type = 'button';
            button.textContent = date.getDate();
            button.dataset.mswDay = iso;
            button.disabled = date.getMonth() !== state.month;
            button.classList.toggle('is-picked', ! button.disabled && (iso === state.from || iso === state.to));
            button.classList.toggle('is-range', ! button.disabled && state.from && state.to && iso > state.from && iso < state.to);
            grid.appendChild(button);
        }
    }

    function mswChooseDay(state, iso) {
        if (! state.from || state.to || iso < state.from) {
            state.from = iso;
            state.to = '';
        } else {
            state.to = iso;
        }
    }

    function mswOpenBatchCalendar(button) {
        const calendar = button.closest('form').querySelector('[data-msw-batch-cal]');
        const [from, to] = button.querySelectorAll('input');
        const shown = new Date((from.value || mswIso(new Date())) + 'T00:00:00');

        calendar.mswButton = button;
        calendar.mswState = { from: from.value, to: to.value, year: shown.getFullYear(), month: shown.getMonth() };
        calendar.hidden = false;

        mswDrawBatchCalendar(calendar);

        const rect = button.getBoundingClientRect();

        calendar.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - calendar.offsetWidth - 8)) + 'px';
        calendar.style.top = Math.max(8, Math.min(rect.bottom + 4, window.innerHeight - calendar.offsetHeight - 8)) + 'px';
    }

    function mswDrawBatchCalendar(calendar) {
        mswDrawCalendar(calendar);
        calendar.querySelector('[data-msw-calendar-ok]').disabled = ! (calendar.mswState.from && calendar.mswState.to);
    }

    function mswPickDay(pop, iso) {
        mswChooseDay(pop.mswState, iso);
        mswDrawDates(pop);
    }

    function mswConfirmDates(pop) {
        const form = pop.closest('form');
        const state = pop.mswState;
        const dated = state.mode === 'set';

        form.elements.special_price_from.value = dated ? state.from : '';
        form.elements.special_price_to.value = dated ? state.to : '';
        pop.dataset.mswConfirmed = state.mode;
        pop.hidden = true;
    }

    document.addEventListener('click', async function (event) {
        const target = event.target.closest('button');

        if (! target) {
            return;
        }

        if (target.dataset.mswOpen) {
            mswOpenEditor(document.getElementById(target.dataset.mswOpen));
        } else if (target.dataset.mswClose !== undefined) {
            target.closest('dialog').close();
        } else if (target.dataset.mswCopy) {
            try {
                await navigator.clipboard.writeText(target.dataset.mswCopy);
            } catch (error) {
                return;
            }

            const title = target.title;

            target.title = target.dataset.mswCopied;
            target.classList.add('is-copied');
            setTimeout(() => {
                target.title = title;
                target.classList.remove('is-copied');
            }, 1500);
        } else if (target.dataset.mswDateOpen !== undefined) {
            mswOpenDates(target);
        }

        const pop = target.closest('[data-msw-date]');

        if (! pop) {
            return;
        }

        const options = pop.querySelector('[data-msw-date-options]');
        const calendar = pop.querySelector('[data-msw-calendar]');
        const mode = pop.querySelector('[data-msw-date-mode]');

        if (target.dataset.mswDateClose !== undefined) {
            pop.hidden = true;
        } else if (target.dataset.mswDateMode !== undefined) {
            options.hidden = ! options.hidden;
            mode.setAttribute('aria-expanded', String(! options.hidden));
        } else if (target.dataset.mswDatePick) {
            pop.mswState.mode = target.dataset.mswDatePick;
            options.hidden = true;
            mode.setAttribute('aria-expanded', 'false');
            calendar.hidden = true;
            mswDrawDates(pop);
        } else if (target.dataset.mswDateField !== undefined) {
            calendar.hidden = ! calendar.hidden;
            mswDrawDates(pop);
        } else if (target.dataset.mswStep) {
            const shown = new Date(pop.mswState.year, pop.mswState.month + Number(target.dataset.mswStep), 1);

            pop.mswState.year = shown.getFullYear();
            pop.mswState.month = shown.getMonth();
            mswDrawCalendar(pop);
        } else if (target.dataset.mswDay) {
            mswPickDay(pop, target.dataset.mswDay);
        } else if (target.dataset.mswCalendarOk !== undefined) {
            calendar.hidden = true;
            mswDrawDates(pop);
        } else if (target.dataset.mswDateConfirm !== undefined) {
            mswConfirmDates(pop);
        }
    });

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-msw-more]');

        document.querySelectorAll('.msw-more-menu:not([hidden])').forEach((menu) => {
            if ((! menu.contains(event.target) && menu.previousElementSibling !== trigger) || event.target.closest('[data-msw-open]')) {
                menu.hidden = true;
                menu.previousElementSibling.setAttribute('aria-expanded', 'false');
            }
        });

        if (! trigger) {
            return;
        }

        const menu = trigger.nextElementSibling;
        const rect = trigger.getBoundingClientRect();

        menu.hidden = ! menu.hidden;
        menu.dataset.mswTop = rect.top;
        trigger.setAttribute('aria-expanded', String(! menu.hidden));
        menu.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - menu.offsetWidth - 8)) + 'px';
        menu.style.top = Math.max(8, Math.min(rect.bottom + 4, window.innerHeight - menu.offsetHeight - 8)) + 'px';
    });

    document.addEventListener('click', function (event) {
        const target = event.target.closest('button');
        const open = document.querySelector('[data-msw-batch-cal]:not([hidden])');

        if (open && ! open.contains(event.target) && ! event.target.closest('[data-msw-batch-date]')) {
            open.hidden = true;
        }

        if (! target) {
            return;
        }

        const calendar = target.closest('[data-msw-batch-cal]');

        if (target.dataset.mswBatchAdd) {
            const holder = target.closest('[data-msw-sources]');

            target.closest('tr').before(mswBatchRow(holder, target.dataset.mswBatchAdd, { quantity: '', ship_from: '', ship_to: '' }));
            mswStockTotal(target.closest('form'));
        } else if (target.dataset.mswBatchRemove !== undefined) {
            const form = target.closest('form');

            target.closest('tr').remove();
            mswStockTotal(form);
        } else if (target.dataset.mswBatchDate !== undefined) {
            mswOpenBatchCalendar(target);
        } else if (calendar && target.dataset.mswStep) {
            const shown = new Date(calendar.mswState.year, calendar.mswState.month + Number(target.dataset.mswStep), 1);

            calendar.mswState.year = shown.getFullYear();
            calendar.mswState.month = shown.getMonth();
            mswDrawBatchCalendar(calendar);
        } else if (calendar && target.dataset.mswDay) {
            mswChooseDay(calendar.mswState, target.dataset.mswDay);
            mswDrawBatchCalendar(calendar);
        } else if (calendar && target.dataset.mswCalendarOk !== undefined) {
            const [from, to] = calendar.mswButton.querySelectorAll('input');

            from.value = calendar.mswState.from;
            to.value = calendar.mswState.to;
            mswShowBatchDates(calendar.mswButton);
            mswStockTotal(calendar.closest('form'));
            calendar.hidden = true;
        }
    });

    window.addEventListener('scroll', function () {
        document.querySelectorAll('.msw-more-menu:not([hidden])').forEach((menu) => {
            if (Math.abs(menu.previousElementSibling.getBoundingClientRect().top - Number(menu.dataset.mswTop)) > 4) {
                menu.hidden = true;
                menu.previousElementSibling.setAttribute('aria-expanded', 'false');
            }
        });
    }, true);

    document.addEventListener('input', function (event) {
        if (event.target.matches('[data-msw-money]')) {
            const digits = event.target.value.replace(/\D/g, '').replace(/^0+/, '').slice(0, 12);

            event.target.value = digits ? (Number(digits) / 100).toFixed(2) : '';
        }

        if (event.target.matches('[data-msw-sources] input')) {
            mswStockTotal(event.target.closest('form'));
        }
    });

    document.addEventListener('cancel', function (event) {
        const pop = event.target.querySelector && event.target.querySelector('[data-msw-date]:not([hidden]), [data-msw-batch-cal]:not([hidden])');

        if (pop) {
            event.preventDefault();
            pop.hidden = true;
        }
    }, true);
</script>
