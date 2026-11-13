<style>
    :root {
        --lux-navy-950: #060f22;
        --lux-navy-900: #0a1a35;
        --lux-navy-800: #102549;
        --lux-navy-700: #183460;
        --lux-wine-950: #1c060a;
        --lux-wine-900: #2a0a0f;
        --lux-wine-800: #3d0f16;
        --lux-wine-700: #561820;
        --lux-gold: #c9a45c;
        --lux-gold-light: #ecd18f;
        --lux-gold-deep: #8a6a2e;
        --lux-gold-line: rgba(201, 164, 92, .55);
        --lux-gold-faint: rgba(201, 164, 92, .22);
        --lux-gold-wash: rgba(201, 164, 92, .12);
        --lux-cream: #f4e8cf;
        --lux-cream-soft: #dccbb0;
        --lux-muted: #a8957a;
        --lux-up: #8fe0a8;
        --lux-down: #ff9b8f;
        --lux-serif: 'DM Serif Display', Georgia, 'Times New Roman', serif;
        --lux-well: rgba(12, 3, 6, .55);
        --lux-page: radial-gradient(1200px 700px at 60% -10%, #16305a 0%, #0b1b36 45%, #060f22 100%);
        --lux-grain: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 .32 0'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        --lux-wine: radial-gradient(130% 100% at 25% 0%, #6e1f27 0%, #4a1219 42%, #2c0a0f 100%);
        --lux-gold-fill: linear-gradient(180deg, #f2d898 0%, #d4ae63 45%, #a9823d 100%);
        --lux-navy-fill: linear-gradient(180deg, #1d3f73 0%, #12294f 55%, #0b1c3a 100%);
        --lux-frame: inset 0 0 0 1px rgba(255, 228, 160, .14), inset 0 0 0 4px rgba(0, 0, 0, .22), 0 0 0 1px rgba(0, 0, 0, .45), 0 12px 30px -12px rgba(0, 0, 0, .7), 0 0 22px -8px rgba(201, 164, 92, .4);
        --lux-frame-sm: inset 0 0 0 1px rgba(255, 228, 160, .08), 0 6px 16px -10px rgba(0, 0, 0, .7);
        color-scheme: dark;
    }

    html.dark body, html.dark .mp-admin-shell .mp-admin-content { background: var(--lux-page) fixed; background-color: var(--lux-navy-950); color: var(--lux-cream-soft); }
    html.dark .mp-admin-shell .mp-admin-content { scrollbar-color: var(--lux-gold-deep) transparent; }
    html.dark body .mp-admin-header, html.dark body .mp-config-footer { background: var(--lux-navy-900); border-color: var(--lux-gold-faint); color: var(--lux-cream-soft); box-shadow: none; }
    html.dark .mp-environment { border-color: var(--lux-gold-faint); color: var(--lux-cream-soft); }
    html.dark .mp-header-page, html.dark .msp-eyebrow { color: var(--lux-muted); }
    html.dark .mp-admin-header [data-action="nav-search"] { background: var(--lux-well); border-color: var(--lux-gold-line); color: var(--lux-cream); }
    html.dark .mp-admin-header [data-mp-theme-toggle], html.dark .mp-admin-header span.icon-dark, html.dark .mp-admin-header span.icon-light { display: none; }

    html.dark .mp-admin-content .box-shadow, html.dark .msp-panel, html.dark .msp-todo-panel, html.dark .msp-order-card, html.dark .msp-media-panel, html.dark .msp-order-details .box-shadow, html.dark .msp-product-editor > div > .box-shadow { border: 1.5px solid var(--lux-gold-line); background-color: var(--lux-wine-800); background-image: var(--lux-grain), var(--lux-wine); box-shadow: var(--lux-frame); color: var(--lux-cream-soft); }
    html.dark .dark\:bg-gray-900 { background-color: var(--lux-wine-800); }
    html.dark .dark\:bg-gray-950 { background-color: var(--lux-navy-950); }
    html.dark .dark\:bg-gray-800 { background-color: var(--lux-wine-700); }
    html.dark .dark\:hover\:bg-gray-950:hover, html.dark .dark\:hover\:bg-gray-800:hover, html.dark .dark\:hover\:bg-gray-900:hover { background-color: var(--lux-gold-wash); }
    html.dark .dark\:border-gray-800, html.dark .dark\:border-gray-700, html.dark .dark\:border-gray-600, html.dark .dark\:border-gray-950 { border-color: var(--lux-gold-faint); }
    html.dark .peer.rounded-full.dark\:bg-gray-800 { background-color: var(--lux-navy-950); box-shadow: inset 0 0 0 1px var(--lux-gold-line); }
    html.dark .peer:checked ~ .dark\:peer-checked\:bg-blue-600 { background-color: var(--lux-gold); }
    html.dark .peer:checked ~ .peer-checked\:text-blue-600 { color: var(--lux-gold-light); }
    html.dark .mp-admin-content .bg-blue-600, html.dark .mp-admin-content .dark\:bg-blue-600 { background-color: var(--lux-gold-deep); }
    html.dark .mp-admin-header .bg-blue-400 { background: var(--lux-gold-fill); color: #2a1a06; box-shadow: 0 0 0 1px #f0d590; }
    html.dark .mp-admin-content .bg-gray-100 { border-color: var(--lux-gold-faint); background-color: var(--lux-gold-wash); color: var(--lux-cream-soft); }
    html.dark .shimmer { background: linear-gradient(to right, rgba(201, 164, 92, .08) 8%, rgba(201, 164, 92, .2) 18%, rgba(201, 164, 92, .08) 33%); background-size: 1250px 100%; }
    html.dark .dark\:text-white { color: var(--lux-cream); }
    html.dark .dark\:text-gray-300, html.dark .dark\:text-gray-200 { color: var(--lux-cream-soft); }
    html.dark .msp-workflow-stage { background-color: var(--lux-wine-900); }
    html.dark .text-blue-600, html.dark .text-blue-500, html.dark .dark\:text-blue-600, html.dark .text-sky-600 { color: var(--lux-gold-light); }
    html.dark .mp-admin-content .text-xl.font-bold, html.dark .msp-page-header h1 { font-family: var(--lux-serif); font-weight: 400; letter-spacing: .01em; color: var(--lux-cream); }

    html.dark .primary-button, html.dark .mp-admin-content .primary-button { border: 1px solid #f0d590; background: var(--lux-gold-fill); color: #2a1a06; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .45), 0 4px 12px -6px rgba(201, 164, 92, .8); }
    html.dark .primary-button:hover, html.dark .mp-admin-content .primary-button:hover { background: linear-gradient(180deg, #f7e2ad 0%, #dcb86c 45%, #b38b44 100%); }
    html.dark .secondary-button { border: 1px solid var(--lux-gold-line); background: rgba(0, 0, 0, .2); color: var(--lux-gold-light); }
    html.dark .secondary-button:hover { border-color: var(--lux-gold); background: var(--lux-gold-wash); }
    html.dark .transparent-button { color: var(--lux-cream-soft); }

    html.dark .mp-admin-content input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="hidden"]), html.dark .mp-admin-content select, html.dark .mp-admin-content textarea, html.dark .msp-form-field input, html.dark .msp-form-field select, html.dark .msp-workflow input:not([type="hidden"]), html.dark .msp-workflow select, html.dark .msp-product-editor input:not([type="checkbox"]):not([type="radio"]), html.dark .msp-product-editor textarea, html.dark .msp-product-editor select { border-color: var(--lux-gold-faint); background-color: var(--lux-well); color: var(--lux-cream); }
    html.dark .mp-admin-content input:focus, html.dark .mp-admin-content select:focus, html.dark .mp-admin-content textarea:focus { border-color: var(--lux-gold); }
    html.dark .mp-admin-header input:focus, html.dark .mp-admin-shell a:focus-visible, html.dark .mp-admin-shell button:focus-visible { outline-color: var(--lux-gold-light); }

    html.dark .msp-kpi, html.dark .msp-task-group, html.dark .msp-status-tabs, html.dark .msp-order-list-head, html.dark .msp-catalog-snapshot .msp-kpi { border-color: var(--lux-gold-line); background: rgba(20, 5, 9, .5); color: var(--lux-cream); }
    html.dark .msp-kpi-icon, html.dark .msp-order-card__avatar { background: var(--lux-gold-wash); color: var(--lux-gold-light); }
    html.dark .msp-kpi .msp-kpi-footer, html.dark .msp-task-group strong.has-tasks, html.dark .msp-mono, html.dark .msp-order-card__number a, html.dark .msp-setup-steps strong { color: var(--lux-gold-light) !important; }
    html.dark .msp-kpi .msp-kpi-footer, html.dark .msp-task-group a, html.dark .msp-order-card__header, html.dark .msp-recent-orders td, html.dark .msp-recent-orders th, html.dark .msp-grid-row { border-color: var(--lux-gold-faint); }
    html.dark .msp-grid-head, html.dark .msp-recent-orders th { background: rgba(0, 0, 0, .3); color: var(--lux-cream-soft); }
    html.dark .msp-grid-row, html.dark .msp-grid-row:not(.msp-grid-head):nth-child(odd) { background: transparent; color: var(--lux-cream-soft); }
    html.dark .msp-grid-row:not(.msp-grid-head):hover { background: var(--lux-gold-wash); }
    html.dark .msp-status-tabs a, html.dark .msp-quick-grid a { border-color: var(--lux-gold-faint); background: rgba(0, 0, 0, .25); color: var(--lux-cream-soft); }
    html.dark .msp-status-tabs a.is-active { background: var(--lux-gold-fill); color: #2a1a06; }
    html.dark .msp-quick-grid a:hover, html.dark .msp-task-group a:hover { border-color: var(--lux-gold); color: var(--lux-gold-light); }
    html.dark .msp-review-button { border-color: var(--lux-gold-line); background: var(--lux-gold-wash); color: var(--lux-gold-light); }
    html.dark .msp-order-item__image span { background: var(--lux-gold); color: #2a1a06; }

    body:has(> .msw) {
        --msw-edge: rgba(201, 164, 92, .3);
        --msw-shine: inset 0 1px 0 rgba(255, 228, 160, .1);
        --msw-raise: var(--lux-frame);
        --msw-raise-sm: var(--lux-frame-sm);
        --msw-raise-hover: inset 0 0 0 1px rgba(255, 228, 160, .2), 0 0 18px -6px rgba(201, 164, 92, .6), 0 10px 22px -12px rgba(0, 0, 0, .8);
        background: var(--lux-page) fixed;
        background-color: var(--lux-navy-950);
        color: var(--lux-cream-soft);
        scrollbar-color: var(--lux-gold-deep) transparent;
    }
    .msw-menu, .msw-rail { background: linear-gradient(180deg, var(--lux-navy-900) 0%, var(--lux-navy-950) 100%); box-shadow: 1px 0 0 var(--lux-gold-faint), 8px 0 30px -12px rgba(0, 0, 0, .6); color: var(--lux-cream-soft); }
    .msw-rail { box-shadow: -1px 0 0 var(--lux-gold-faint), -8px 0 30px -12px rgba(0, 0, 0, .6); }
    .msw-logo, .msw-logo strong { color: var(--lux-gold-light); font-family: var(--lux-serif); font-weight: 400; }
    .msw-menu-search { color: var(--lux-muted); }
    .msw-menu-search input { border-color: var(--lux-gold-line); background: var(--lux-well); color: var(--lux-cream); }
    .msw-menu-search input::placeholder { color: var(--lux-muted); }
    .msw-menu-list, .msw-menu-foot { border-color: var(--lux-gold-faint); scrollbar-color: var(--lux-gold-deep) transparent; }
    .msw-menu summary { color: var(--lux-cream); }
    .msw-menu summary:hover, .msw-menu-list a:hover { background: var(--lux-gold-wash); color: var(--lux-gold-light); }
    .msw-menu-list a { color: var(--lux-cream-soft); }
    .msw-menu-list a.is-active { margin-inline: 6px; padding-inline-start: 34px; border-radius: 6px; background: linear-gradient(90deg, #a9823d 0%, #6f5424 100%); color: #fff8e6; box-shadow: inset 0 0 0 1px rgba(255, 228, 160, .3); }
    .msw-menu-list details:first-child a.is-active { padding-inline-start: 38px; }
    .msw-menu summary:focus-visible, .msw-menu-list a:focus-visible, .msw-menu-search-shortcut:focus-visible { outline-color: var(--lux-gold-light); }
    .msw-menu-toggle { background: var(--lux-navy-800); color: var(--lux-gold-light); box-shadow: 0 0 0 1px var(--lux-gold-line), 0 2px 12px rgba(0, 0, 0, .5); }
    .msw-menu-foot { color: var(--lux-cream); }
    @media (min-width: 1024px) {
        html.msw-collapsed .msw-menu-search-shortcut { color: var(--lux-gold-light); }
        html.msw-collapsed .msw-menu details.is-fly > summary, html.msw-collapsed .msw-menu details:has(a.is-active) > summary { background: var(--lux-gold-wash); color: var(--lux-gold-light); }
        html.msw-collapsed .msw-menu details.is-fly .msw-menu-links { background: var(--lux-navy-900); box-shadow: 0 0 0 1px var(--lux-gold-faint), 4px 2px 16px rgba(0, 0, 0, .5); }
        html.msw-collapsed .msw-menu details.is-fly .msw-menu-links a.is-active { padding-inline-start: 10px; }
    }
    .msw-rail a, .msw-rail button { border: 1.5px solid var(--lux-gold-line); background: radial-gradient(circle at 50% 30%, var(--lux-navy-700), var(--lux-navy-950)); color: var(--lux-gold-light); box-shadow: 0 0 14px -4px rgba(201, 164, 92, .55); }
    .msw-rail a:hover, .msw-rail button:hover { border-color: var(--lux-gold); box-shadow: 0 0 18px -2px rgba(201, 164, 92, .75); }

    .msw-main-hero { background: none; }
    .msw h1, .msw-h1-strong { font-family: var(--lux-serif); font-weight: 400 !important; letter-spacing: .01em; color: var(--lux-cream) !important; }
    .msw-hero-title { font-family: var(--lux-serif); font-weight: 400 !important; letter-spacing: .01em; color: var(--lux-cream) !important; text-shadow: 0 2px 12px rgba(0, 0, 0, .5); }
    .msw-crumb { color: var(--lux-muted); }
    .msw-crumb strong { color: var(--lux-cream); }

    .msw-card, .msw-order-head, .msw-order-row, .msw-table, [data-msw-page="fees"] .msw-card, .msw-modal { border: 1.5px solid var(--lux-gold-line); background-color: var(--lux-wine-800); background-image: var(--lux-grain), var(--lux-wine); box-shadow: var(--lux-frame); color: var(--lux-cream-soft); }
    .msw-top .msw-profile { background-color: var(--lux-navy-800); background-image: var(--lux-grain), var(--lux-navy-fill); border-color: var(--lux-gold-faint); }
    .msw-card h2, .msw-add-card h2, .msw-modal-title, .msw-date > b, .msw-calendar-head strong { color: var(--lux-cream); }
    .msw-title, .msw-set-title { color: var(--lux-cream) !important; }
    .msw-blue, .msw-more > button, .msw-batch-link, .msw-add-more, .msw-clock, .msw-copy.is-copied, .msw-table td a { color: var(--lux-gold-light); }

    .msw-tile, .msw-tile-big, .msw-task, .msw-set, .msw-subtabs a, .msw-outline { border: 1px solid var(--lux-gold-line); background: rgba(20, 5, 9, .5); box-shadow: var(--lux-frame-sm); color: var(--lux-cream); }
    .msw-tile span, .msw-tile-big span, .msw-outline span, .msw-set b { color: var(--lux-cream); }
    .msw-tile strong, .msw-outline strong, .msw-task b { color: var(--lux-cream); }
    .msw-tile-big strong { background: var(--lux-gold-fill); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .msw-tile .msw-change, .msw-task small, .msw-set small { color: var(--lux-muted); }
    .msw-task b .msw-icon { color: var(--lux-cream); }
    .msw-task > .msw-icon { color: var(--lux-gold); }
    .msw-tile[data-field$=".range-sales"] { border-color: #f0d590; background: var(--lux-gold-fill); }
    .msw-tile[data-field$=".range-sales"] span, .msw-tile[data-field$=".range-sales"] strong { color: #2a1a06; }
    .msw-tile[data-field$=".range-sales"] .msw-change { color: #5a4317; }
    .msw-tile[data-field$=".range-orders"] { background-color: var(--lux-navy-800); background-image: var(--lux-navy-fill); }
    a.msw-tile.is-active, .msw-subtabs a.is-active, .msw-outline.is-active { outline-color: var(--lux-gold); border-color: var(--lux-gold); color: var(--lux-gold-light); }
    .msw-change.is-up { color: var(--lux-up); }
    .msw-change.is-down, .msw-rank-row strong.is-down { color: var(--lux-down); }

    .msw-task-groups h3 { color: var(--lux-cream); }
    .msw-task-line { color: var(--lux-cream-soft); }
    .msw-task-line b { min-width: 28px; justify-content: center; padding: 0 6px; border: 1px solid var(--lux-gold-line); border-radius: 8px; background: var(--lux-navy-950); font-size: 12px; color: var(--lux-cream); }
    .msw-task-line:hover { color: var(--lux-gold-light); }
    @media (min-width: 1024px) {
        .msw-task-groups > div + div { padding-inline-start: 24px; border-inline-start: 1px solid var(--lux-gold-faint); }
    }

    .msw-tabs a { color: var(--lux-cream-soft); }
    .msw-tabs a.is-active { color: var(--lux-gold-light); }
    .msw-tabs a.is-active::after { background: var(--lux-gold-fill); box-shadow: 0 2px 8px rgba(201, 164, 92, .5); }
    .msw-tabs sup { background: var(--lux-gold); color: #2a1a06; }
    .msw-tabs-sm { border-color: var(--lux-gold-faint); }
    .msw-chip { border: 1px solid var(--lux-gold-faint); background: rgba(0, 0, 0, .3); box-shadow: none; color: var(--lux-cream-soft); }
    .msw-chip.is-active { border-color: var(--lux-gold); background: var(--lux-gold-wash); color: var(--lux-gold-light); }

    .msw-field, [data-msw-page="fees"] .msw-field, .msw-money, .msw-alert-input, .msw-stock-table input, .msw-batch-date, .msw-date-select > button, .msw-date-range > button, .msw-add-input, .msw-add-textarea, .msw-var-fields { border-color: var(--lux-gold-line); background: var(--lux-well); box-shadow: inset 0 1px 3px rgba(0, 0, 0, .5); color: var(--lux-cream); }
    .msw-field > span { background: var(--lux-gold-wash); color: var(--lux-cream-soft); }
    .msw-field input, .msw-field select, .msw-money input, .msw-add-input input, .msw-add-input select, .msw-var-fields input { color: var(--lux-cream); color-scheme: dark; }
    .msw-field select option, .msw-add-input select option { background: var(--lux-wine-900); color: var(--lux-cream); }
    .msw-field button, .msw-pencil, .msw-modal-close, .msw-date-close, .msw-var-close, .msw-var-tool, .msw-calendar-head button { color: var(--lux-gold-light); }
    .msw-money span, .msw-add-input small, .msw-add-input > span, .msw-date-select > button .is-empty, .msw-date-range > button span.is-empty, .msw-date-range > button i { color: var(--lux-muted); }
    .msw-money:focus-within, .msw-add-input:focus-within, .msw-stock-table input:focus, .msw-date-range > button:focus-visible, .msw-date-range > button.is-active { border-color: var(--lux-gold); box-shadow: 0 0 0 2px rgba(201, 164, 92, .25); }
    .msw-var-fields input + input { border-color: var(--lux-gold-faint); }

    .msw-button { border-color: var(--lux-gold-line); background: rgba(0, 0, 0, .2); box-shadow: inset 0 1px 0 rgba(255, 228, 160, .08); color: var(--lux-cream); }
    .msw-button:hover:not(:disabled) { border-color: var(--lux-gold); box-shadow: 0 0 14px -4px rgba(201, 164, 92, .6); color: var(--lux-gold-light); }
    .msw-primary, [data-msw-page="fees"] .msw-primary { border-color: #f0d590; background: var(--lux-gold-fill); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .45), 0 4px 12px -6px rgba(201, 164, 92, .8); color: #2a1a06; }
    .msw-primary:hover:not(:disabled) { background: linear-gradient(180deg, #f7e2ad 0%, #dcb86c 45%, #b38b44 100%); color: #2a1a06; }
    .msw-modal-cancel { border-color: var(--lux-gold-faint); color: var(--lux-cream-soft); }

    .msw-table th, .msw-table-soft th, [data-msw-page="fees"] .msw-table th, .msw-modal-table > thead > tr > th, .msw-stock-table th, .msw-add-table th, .msw-var-table th { border-color: var(--lux-gold-faint); background: rgba(0, 0, 0, .3); box-shadow: inset 0 -1px 0 var(--lux-gold-faint); color: var(--lux-cream); }
    .msw-table td, .msw-var-table td, .msw-var-first, .msw-var-second, .msw-modal-table, .msw-add-tablewrap, .msw-var-tablewrap, .msw-add-editor, .msw-add-tools, .msw-calendar-head, .msw-calendar-foot { border-color: var(--lux-gold-faint); }
    .msw-table small, .msw-order-row small, .msw-rank-row small, .msw-bar small, .msw-alert-hint, .msw-add-note, .msw-product-info, .msw-was, .msw-copy, .msw-return small { color: var(--lux-muted); }
    .msw-order-head { color: var(--lux-cream-soft); }
    .msw-order-row strong, .msw-product b, .msw-price, .msw-rank-row b, .msw-var-special-info span:first-child { color: var(--lux-cream); }
    .msw-pill, .msw-tag { border: 1px solid var(--lux-gold-faint); background: var(--lux-gold-wash); color: var(--lux-cream); }
    .msw-note { border-color: var(--lux-gold-faint); background: rgba(16, 37, 73, .6); box-shadow: none; color: var(--lux-cream-soft); }
    .msw-pager a { border-color: var(--lux-gold-faint); color: var(--lux-gold-light); }
    .msw-pager a[aria-disabled] { color: var(--lux-muted); opacity: .6; }
    .msw-pager strong { background: var(--lux-gold-fill); color: #2a1a06; }
    .msw-empty { color: rgba(244, 232, 207, .3); }
    .msw-table .msw-empty, .msw-card .msw-empty { color: var(--lux-muted); }
    .msw-deal-dates, .msw-table small.msw-deal-dates, .msw-var-special-dates, .msw-alert-badge, .msw-add-label b, .msw-add-table th b, .msw-var-table th b, .msw-var-hint, .msw-add-foot small, [data-msw-message].is-error { color: var(--lux-down); }

    .msw-bars { border-color: var(--lux-gold-faint); }
    .msw-bar i { background: var(--lux-gold-fill); box-shadow: 0 0 8px -2px rgba(201, 164, 92, .6); }
    .msw-rank-row { border-color: var(--lux-gold-faint); }
    .msw-rank-row > strong { color: var(--lux-gold-light); }

    .msw-product img, .msw-product i, .msw-product-info img, .msw-product-info i, .msw-add-thumb, .msw-var-image.has-image { border-color: var(--lux-gold-faint); background-color: var(--lux-navy-900); }
    .msw-switch { background: #4a3a2a; }
    .msw-switch.is-on, .msw-switch-field input:checked + .msw-switch { background: var(--lux-gold); }
    .msw-switch::after { background: var(--lux-cream); }
    .msw-online.is-on::before { background: var(--lux-up); }
    .msw-preorder { background: var(--lux-gold-wash); color: var(--lux-gold-light); }

    .msw-set > span, .msw-avatar { border: 1.5px solid var(--lux-gold-line); background: radial-gradient(circle at 50% 30%, var(--lux-navy-700), var(--lux-navy-950)); color: var(--lux-gold-light); }
    .msw-profile strong, .msw-profile-head h2 { color: var(--lux-cream); }
    .msw-profile-tools a { background: rgba(0, 0, 0, .25); color: var(--lux-gold-light); }
    .msw-profile-tools a:first-child { color: var(--lux-cream-soft); }
    .msw-profile-head, .msw-return-foot { border-color: var(--lux-gold-faint); }

    .msw-more-menu, .msw-date, .msw-date-options, .msw-calendar, .msw-add-list { border: 1px solid var(--lux-gold-line); background: var(--lux-wine-900); box-shadow: 0 16px 40px -12px rgba(0, 0, 0, .8); color: var(--lux-cream-soft); }
    .msw-more-menu button, .msw .msw-more-menu a, .msw-date-options button, .msw-add-list button, .msw-calendar-grid span, .msw-calendar-grid button, .msw-date-select > button, .msw-date-range > button, .msw-batch-date { color: var(--lux-cream-soft); }
    .msw-more-menu button:hover, .msw-more-menu a:hover, .msw-date-options button:hover, .msw-add-list button:hover, .msw-add-tools button:hover { background: var(--lux-gold-wash); color: var(--lux-gold-light); }
    .msw-calendar-grid button:disabled, .msw-batch-link:disabled, .msw-var-tool:disabled { color: rgba(168, 149, 122, .45); }
    .msw-calendar-grid button.is-range { background: var(--lux-gold-wash); }
    .msw-calendar-grid button.is-picked { background: var(--lux-gold-fill); color: #2a1a06; }
    .msw-calendar-foot .msw-button:disabled { background: rgba(0, 0, 0, .3); color: var(--lux-muted); }
    .msw-modal::backdrop { background: rgba(3, 8, 20, .7); }
    .msw-modal-intro, .msw-add-foot p, .msw-add-side p, .msw-add-label, .msw-add-recent, .msw-add-drop small { color: var(--lux-cream-soft); }

    .msw-add-drop, .msw-var-box { background: rgba(0, 0, 0, .25); }
    .msw-add-tile, .msw-var-add, .msw-var-image { border-color: var(--lux-gold-line); background-color: transparent; color: var(--lux-gold-light); }
    .msw-add-recent button { background: var(--lux-gold-wash); color: var(--lux-cream); }
    .msw-add-bar { background: rgba(255, 255, 255, .1); }
    .msw-add-bar i { background: var(--lux-gold-fill); }
    .msw-add-tools button { color: var(--lux-cream-soft); }
    #msw-description { color: var(--lux-cream); }
    #msw-description.is-empty::before { color: var(--lux-muted); }
    .msw-add-side h2 { color: var(--lux-gold-light); }
    .msw-add-foot .msw-icon { color: var(--lux-gold); }
    .msw-var-table .is-linked { box-shadow: inset 0 0 0 1px var(--lux-down); }

    @media (max-width: 1023px) {
        .msw-menu, .msw-rail { box-shadow: none; }
        .msw-rail { border-color: var(--lux-gold-faint); }
        .msw-menu-list a.is-active { margin-inline: 0; padding-inline: 12px; }
        .msw-stock-table thead tr { background: rgba(0, 0, 0, .3); }
    }
</style>
