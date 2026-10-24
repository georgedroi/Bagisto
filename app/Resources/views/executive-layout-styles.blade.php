<style>
    :root { --mp-admin-header-height: 56px; --mp-cobalt: #3b5bf5; color-scheme: light; }
    html, body { height: 100%; overflow: hidden; }
    body { margin: 0; font-family: 'Geist', system-ui, sans-serif; }
    #app { height: 100dvh; }
    .mp-admin-header { height: 56px; min-height: 56px; padding: 0 20px; background: #fff; border-color: #e2e8f0; gap: 16px; }
    .mp-brand { display: flex; align-items: center; gap: 9px; color: #0f172a; text-decoration: none; }
    .mp-brand img { max-width: 155px; max-height: 32px; object-fit: contain; }
    .mp-brand-emblem { width: 30px; height: 30px; color: #bd963e; }
    .mp-brand-name { font-size: 16px; font-weight: 650; white-space: nowrap; }
    .mp-environment { display: inline-flex; align-items: center; gap: 6px; margin-inline: 16px; padding: 4px 8px; border: 1px solid #e2e8f0; border-radius: 5px; font-size: 10px; color: #64748b; text-transform: capitalize; }
    .mp-environment span { color: #16a34a; font-size: 9px; }
    .mp-header-page { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; color: #64748b; }
    .mp-admin-header [data-action="nav-search"] { height: 34px; background: #f8f9ff; border-color: #e2e8f0; font-size: 12px; }
    .mp-admin-header div:has(> [data-action="nav-search"]) { width: clamp(180px, 23vw, 360px); }
    .mp-admin-shell { position: relative; height: calc(100dvh - 56px); overflow: hidden; gap: 0; }
    .mp-admin-shell .mp-admin-content { height: 100%; min-height: 0; overflow-y: auto; overflow-x: auto; overscroll-behavior: contain; padding: 24px 28px; padding-inline-start: 268px; padding-inline-end: 84px; background: #f8f9ff; color: #0f172a; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
    .mp-admin-shell.sidebar-collapsed .mp-admin-content { padding-inline-start: 92px; }
    .mp-admin-sidebar { top: 56px; height: calc(100dvh - 56px - var(--mp-admin-bottom-overlay-height, 0px)); width: 240px; border-color: #e2e8f0; }
    .mp-admin-shell.sidebar-collapsed .mp-admin-sidebar { width: 64px; }
    .mp-admin-sidebar__scroll { padding: 20px 10px; }
    .mp-nav-section + .mp-nav-section { margin-block-start: 24px; }
    .mp-nav-section__title { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
    .mp-nav-link { font-size: 13px; font-weight: 600; }
    .mp-nav-child { padding-block: 7px; font-size: 13px; font-weight: 500; }
    .mp-nav-child.is-active, .mp-nav-child[aria-current="page"] { background: #3b5bf5; color: #fff; }
    .mp-admin-sidebar, .mp-admin-navigation { --mp-nav-accent: #3b5bf5; --mp-nav-active: #eef1ff; --mp-nav-active-text: #3b5bf5; --mp-nav-line: #e2e8f0; --mp-nav-text: #1e293b; --mp-nav-muted: #334155; --mp-nav-heading: #475569; }
    .dark .mp-admin-sidebar, .dark .mp-admin-navigation { --mp-nav-text: #e5ebf3; --mp-nav-muted: #c3cedd; --mp-nav-heading: #aab8cc; --mp-nav-line: #263144; --mp-nav-active: #1c3053; --mp-nav-active-text: #d1e1ff; --mp-nav-accent: #7ca9ff; }
    .mp-admin-content .primary-button { background: #3b5bf5; border-color: #3b5bf5; }
    .mp-admin-content .primary-button:hover { background: #2947dc; }
    .mp-utility-dock { position: fixed; inset-inline-end: 0; top: 56px; bottom: 0; z-index: 1000; width: 56px; display: flex; align-items: center; flex-direction: column; gap: 12px; padding: 18px 8px; background: #fff; border-inline-start: 1px solid #e2e8f0; }
    .mp-dock-button { display: grid; place-items: center; width: 36px; height: 36px; border: 1px solid transparent; border-radius: 6px; color: #64748b; background: transparent; cursor: pointer; }
    .mp-dock-button:hover, .mp-dock-button[aria-expanded="true"], .mp-dock-button[aria-pressed="true"] { color: #3b5bf5; background: #eef1ff; }
    .mp-dock-button svg { width: 20px; height: 20px; }
    .mp-dock-home { margin-block-start: auto; }
    .mp-utility-panel { position: fixed; inset-inline-end: 68px; top: 70px; z-index: 1001; width: 280px; max-height: calc(100dvh - 90px); overflow: auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; box-shadow: 0 10px 30px #0f172a14; }
    .mp-utility-panel[hidden] { display: none; }
    .mp-utility-panel h2 { font-weight: 600; margin-block-end: 12px; }
    .mp-utility-panel a { display: flex; align-items: center; gap: 10px; border-radius: 5px; padding: 10px; color: #475569; }
    .mp-utility-panel a:hover { background: #eef1ff; color: #3b5bf5; }
    .mp-admin-header input:focus, .mp-admin-shell a:focus-visible, .mp-admin-shell button:focus-visible, .mp-dock-button:focus-visible, .mp-utility-panel a:focus-visible { outline: 2px solid #3b5bf5; outline-offset: 2px; }
    .mp-compact .mp-admin-content td, .mp-compact .mp-admin-content th { padding-block: 6px; }
    .mp-compact .mp-admin-content { font-size: 12px; }
    .dark { color-scheme: dark; }
    .dark .mp-admin-header, .dark .mp-utility-dock, .dark .mp-utility-panel { background: #101725; border-color: #263144; color: #cbd5e1; }
    .dark .mp-brand { color: #e2e8f0; }
    .dark .mp-admin-shell .mp-admin-content { background: #0b1120; color: #e2e8f0; }
    .dark .mp-admin-header [data-action="nav-search"] { background: #1a2435; border-color: #263144; }
    .mp-config-footer { position: fixed; bottom: 0; inset-inline-start: 240px; inset-inline-end: 56px; z-index: 40; display: flex; align-items: center; justify-content: flex-end; gap: 10px; padding: 12px 20px; border-top: 1px solid #e2e8f0; background: #fff; box-shadow: 0 -2px 8px #0f172a0d; }
    .mp-admin-shell.sidebar-collapsed .mp-config-footer { inset-inline-start: 64px; }
    .dark .mp-config-footer { background: #101725; border-color: #263144; }
    @media (max-width: 1279px) { .mp-header-page { display: none; } }
    @media (max-width: 1023px) {
        .mp-admin-shell .mp-admin-content, .mp-admin-shell.sidebar-collapsed .mp-admin-content { padding: 20px; }
        .mp-sidebar-container, .mp-utility-dock, .mp-utility-panel { display: none; }
        .mp-config-footer, .mp-admin-shell.sidebar-collapsed .mp-config-footer { inset-inline: 0; }
        .mp-environment { margin-inline: 8px; }
    }
    @media (max-width: 639px) {
        .mp-admin-header { padding-inline: 8px; gap: 6px; }
        .mp-environment { display: none; }
        .mp-brand-name { font-size: 13px; }
        .mp-admin-shell .mp-admin-content, .mp-admin-shell.sidebar-collapsed .mp-admin-content { padding: 12px; }
    }
    @media (prefers-reduced-motion: reduce) { .mp-admin-shell * { scroll-behavior: auto; transition: none; } }
</style>
<style>
    .msp-page-header { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-block-end: 24px; }
    .msp-page-header h1 { margin: 5px 0; font-size: 24px; line-height: 1.3; font-weight: 650; letter-spacing: -.035em; }
    .msp-page-header p:not(.msp-eyebrow) { max-width: 600px; color: #64748b; font-size: 12px; line-height: 1.6; }
    .msp-eyebrow { font-size: 10px; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; color: #475569; }
    .msp-header-actions { display: flex; flex-shrink: 0; align-items: center; gap: 10px; }
    .msp-panel, .msp-todo-panel { margin-block: 20px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; box-shadow: 0 1px 3px #0f172a04; }
    .msp-todo-panel { background: #f0f3ff; border-color: #e0e7ff; }
    .msp-section-title { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding-block-end: 16px; }
    .msp-section-title h2 { font-size: 17px; font-weight: 600; letter-spacing: -.025em; }
    .msp-section-title > span, .msp-section-title > a { font-size: 11px; color: #64748b; }
    .msp-kpi-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; }
    .msp-kpi-grid--four { grid-template-columns: repeat(4, minmax(0, 1fr)); margin-block-start: 18px; }
    .msp-kpi { min-width: 0; display: flex; flex-direction: column; align-items: flex-start; gap: 14px; padding: 16px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; color: #0f172a; transition: border-color 150ms; }
    a.msp-kpi:hover { border-color: #3b5bf5; }
    .msp-kpi-icon { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 6px; background: #eef1ff; color: #3b5bf5; font-family: ui-monospace, monospace; font-size: 12px; }
    .msp-kpi > strong { font-size: 30px; font-weight: 650; line-height: 1.1; letter-spacing: -.04em; }
    .msp-kpi h3 { min-height: 38px; font-size: 15px; font-weight: 600; line-height: 1.3; letter-spacing: -.025em; }
    .msp-kpi > span:not(.msp-kpi-icon) { font-size: 11px; color: #64748b; }
    .msp-kpi .msp-kpi-footer { display: flex; width: 100%; justify-content: space-between; margin-block-start: auto; padding-block-start: 12px; border-top: 1px solid #e2e8f0; color: #3b5bf5; }
    .msp-kpi--urgent > strong { color: #b91c1c; }
    .msp-kpi--urgent .msp-kpi-icon { color: #b91c1c; background: #fee2e2; }
    .msp-catalog-snapshot .msp-kpi { background: #f8f9ff; border: 0; border-radius: 4px; }
    .msp-catalog-snapshot .msp-kpi strong { font-size: 24px; }
    .msp-quick-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 16px; }
    .msp-quick-grid a { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8f9ff; font-size: 12px; }
    .msp-quick-grid a:hover { color: #3b5bf5; border-color: #a5b4fc; }
    .msp-status-tabs { display: flex; overflow: auto; align-items: center; gap: 5px; margin-block: 18px 20px; padding: 5px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
    .msp-status-tabs a { padding: 10px 15px; border-radius: 5px; background: #f4f6ff; color: #475569; font-size: 11px; font-weight: 500; white-space: nowrap; }
    .msp-status-tabs a.is-active { background: #3b5bf5; color: #fff; }
    .msp-data-grid .table-responsive { border: 1px solid #e2e8f0; border-radius: 9px; box-shadow: none; }
    .msp-grid-row { display: grid; gap: 18px; align-items: center; padding: 17px 18px; border-bottom: 1px solid #e2e8f0; background: #fff; color: #475569; font-size: 12px; }
    .msp-grid-row:last-child { border-bottom: 0; }
    .msp-order-grid .msp-grid-row { min-width: 780px; grid-template-columns: 1.1fr 1.5fr 1fr 1fr .9fr; }
    .msp-product-grid .msp-grid-row { min-width: 920px; grid-template-columns: 20px 2fr 1.1fr .8fr .9fr .7fr 1fr; }
    .msp-grid-row:not(.msp-grid-head):nth-child(odd) { background: #fcfdff; }
    .msp-grid-row:not(.msp-grid-head):hover { background: #f5f7ff; }
    .msp-grid-row strong { display: block; color: #0f172a; font-size: 12px; font-weight: 600; }
    .msp-grid-row small, .msp-recent-orders small { display: block; margin-block-start: 4px; color: #94a3b8; font-size: 10px; }
    .msp-grid-head { background: #f0f3ff; color: #64748b; font-size: 10px; font-weight: 500; letter-spacing: .04em; text-transform: uppercase; }
    .msp-grid-head button { text-align: start; font: inherit; color: inherit; text-transform: inherit; letter-spacing: inherit; }
    .msp-grid-head button:not(:disabled):hover { color: #3b5bf5; }
    .msp-grid-row input[type="checkbox"] { width: 15px; height: 15px; accent-color: #3b5bf5; }
    .msp-grid-loading { min-height: 200px; padding: 24px; }
    .msp-grid-loading .shimmer { display: block; height: 160px; border-radius: 6px; }
    .msp-mono { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; color: #3b5bf5 !important; font-size: 11px; }
    .msp-money { text-align: end; font-variant-numeric: tabular-nums; }
    .msp-row-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 6px; }
    .msp-review-button { display: inline-flex; align-items: center; justify-content: center; border: 1px solid #dce3ff; border-radius: 4px; padding: 5px 9px; background: #eef1ff; color: #284be7; font-size: 10px; font-weight: 600; white-space: nowrap; cursor: pointer; }
    .msp-review-button:hover { border-color: #3b5bf5; }
    .msp-product-identity { display: flex; align-items: center; gap: 12px; }
    .msp-product-identity img, .msp-image-placeholder { flex: 0 0 38px; width: 38px; height: 38px; padding: 3px; border: 1px solid #e2e8f0; border-radius: 4px; background: #f1f5f9; object-fit: cover; }
    .msp-image-placeholder { display: grid; place-items: center; font-size: 20px; color: #94a3b8; }
    .msp-stock-pill { display: inline-block; width: fit-content; padding: 3px 6px; border-radius: 4px; background: #e6f6f5; color: #0e7490; font-size: 10px; font-weight: 500; }
    .msp-stock-pill.is-empty { background: #fee2e2; color: #b91c1c; }
    .msp-stock-pill.is-low { background: #fef3c7; color: #92400e; }
    .msp-empty { padding: 32px; text-align: center; color: #64748b; }
    .msp-table-scroll { overflow: auto; }
    .msp-recent-orders table { width: 100%; min-width: 700px; text-align: start; border-collapse: collapse; }
    .msp-recent-orders th { text-align: start; background: #f0f3ff; font-size: 10px; text-transform: uppercase; font-weight: 500; letter-spacing: .04em; }
    .msp-recent-orders td, .msp-recent-orders th { padding: 14px 12px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }
    .msp-product-setup-layout { display: grid; grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr); gap: 20px; }
    .msp-product-setup-layout .msp-panel { margin-block: 0; height: fit-content; }
    .msp-product-setup-layout h2 { margin-block-end: 20px; font-weight: 600; font-size: 13px; }
    .msp-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
    .msp-form-field { display: flex; flex-direction: column; gap: 7px; }
    .msp-form-field--wide { grid-column: 1 / -1; }
    .msp-form-field label { font-size: 12px; font-weight: 500; }
    .msp-form-field input, .msp-form-field select { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; background: #f3f6ff; color: #334155; font-size: 12px; }
    .msp-form-field select[multiple] { min-height: 110px; }
    .msp-setup-steps p { margin-block-start: 16px; color: #64748b; font-size: 12px; line-height: 1.6; }
    .msp-setup-steps strong { color: #3b5bf5; font-size: 13px; }
    .msp-error { color: #b91c1c; font-size: 12px; margin-block-end: 12px; }
    .msp-product-editor { gap: 20px; }
    .msp-product-editor > div > .box-shadow { margin-block-end: 10px; border: 1px solid #e2e8f0; border-radius: 9px; box-shadow: 0 1px 3px #0f172a04; }
    .msp-product-editor input:not([type="checkbox"]):not([type="radio"]), .msp-product-editor textarea, .msp-product-editor select { background-color: #f5f7ff; border-color: #e2e8f0; }
    .msp-media-panel { background: #fff; }
    .msp-media-panel label:has(input[type="file"]) { width: 60px !important; min-width: 60px !important; height: 60px !important; min-height: 60px !important; border-color: #3b5bf5; }
    .msp-media-panel label:has(input[type="file"]) p { display: none; }
    .msp-media-panel label:has(input[type="file"]) .icon-image::before { content: '+'; font-family: system-ui, sans-serif; color: #3b5bf5; }
    .mp-compact .msp-grid-row { padding-block: 8px; }
    .dark .msp-panel, .dark .msp-kpi, .dark .msp-status-tabs, .dark .msp-grid-row, .dark .msp-media-panel { background: #101725; border-color: #263144; color: #cbd5e1; }
    .dark .msp-todo-panel, .dark .msp-grid-head, .dark .msp-catalog-snapshot .msp-kpi, .dark .msp-recent-orders th { background: #17213a; border-color: #263144; }
    .dark .msp-grid-row strong, .dark .msp-kpi { color: #e2e8f0; }
    .dark .msp-product-editor input, .dark .msp-product-editor textarea, .dark .msp-product-editor select, .dark .msp-form-field input, .dark .msp-form-field select { background: #17213a; border-color: #263144; color: #e2e8f0; }
    @media (max-width: 1199px) { .msp-kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } .msp-kpi-grid--four { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    @media (max-width: 767px) { .msp-kpi-grid, .msp-kpi-grid--four { grid-template-columns: repeat(2, minmax(0, 1fr)); } .msp-page-header { align-items: flex-start; flex-direction: column; } .msp-product-setup-layout { grid-template-columns: minmax(0, 1fr); } }
    @media (max-width: 479px) { .msp-quick-grid, .msp-form-grid { grid-template-columns: minmax(0, 1fr); } .msp-kpi { padding: 12px; } .msp-panel, .msp-todo-panel { padding: 14px; } .msp-header-actions { flex-wrap: wrap; } }
</style>
<style>
    .msp-order-details .box-shadow { border: 1px solid #e2e8f0; border-radius: 9px; box-shadow: 0 1px 3px #0f172a04; }
</style>
<style>
    .msp-media-panel .group:has(> img) { width: 60px; min-width: 60px; max-height: 60px; }
    .msp-media-panel .group:has(> img) > div { padding: 3px; }
    .msp-media-panel .group:has(> img) > div > p { display: none; }
    .msp-media-panel .group:has(> img) .icon-edit, .msp-media-panel .group:has(> img) .icon-delete { padding: 3px; font-size: 16px; }
</style>
