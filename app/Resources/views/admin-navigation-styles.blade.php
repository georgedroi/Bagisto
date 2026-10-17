<style>
    .mp-admin-sidebar,
    .mp-admin-navigation {
        --mp-nav-surface: #ffffff;
        --mp-nav-text: #475569;
        --mp-nav-muted: #64748b;
        --mp-nav-heading: #64748b;
        --mp-nav-line: #e5eaf1;
        --mp-nav-hover: #f1f5f9;
        --mp-nav-active: #eaf1ff;
        --mp-nav-accent: #2563eb;
        --mp-nav-active-text: #1d4ed8;
    }

    .dark .mp-admin-sidebar,
    .dark .mp-admin-navigation {
        --mp-nav-surface: #101725;
        --mp-nav-text: #b4c0d0;
        --mp-nav-muted: #91a0b5;
        --mp-nav-heading: #8494ac;
        --mp-nav-line: #263144;
        --mp-nav-hover: #1a2435;
        --mp-nav-active: #1c3053;
        --mp-nav-accent: #7ca9ff;
        --mp-nav-active-text: #d1e1ff;
    }

    .mp-admin-sidebar {
        position: fixed;
        inset-block-start: var(--mp-admin-header-height, 62px);
        inset-inline-start: 0;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        width: 270px;
        height: max(0px, calc(100vh - var(--mp-admin-header-height, 62px) - var(--mp-admin-bottom-overlay-height, 0px)));
        height: max(0px, calc(100dvh - var(--mp-admin-header-height, 62px) - var(--mp-admin-bottom-overlay-height, 0px)));
        border-inline-end: 1px solid var(--mp-nav-line);
        background: var(--mp-nav-surface);
        color: var(--mp-nav-text);
        transition: width 180ms ease;
    }

    .mp-admin-sidebar__scroll {
        min-height: 0;
        flex: 1;
        overflow: auto;
        overscroll-behavior: contain;
        padding: 22px 14px 18px;
        scrollbar-width: thin;
        scrollbar-color: var(--mp-nav-line) transparent;
    }

    .mp-nav-section + .mp-nav-section {
        margin-block-start: 25px;
    }

    .mp-nav-section__title {
        margin: 0 0 9px;
        padding-inline: 12px;
        color: var(--mp-nav-heading);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .13em;
        line-height: 1.6;
        text-transform: uppercase;
    }

    .mp-nav-section__items {
        display: grid;
        gap: 5px;
    }

    .mp-nav-row {
        position: relative;
        display: flex;
        min-height: 43px;
        align-items: center;
        border: 1px solid transparent;
        border-radius: 10px;
        transition: background-color 140ms ease, border-color 140ms ease;
    }

    .mp-nav-row:hover,
    .mp-nav-row:focus-within {
        background: var(--mp-nav-hover);
    }

    .mp-nav-group.is-active > .mp-nav-row {
        background: var(--mp-nav-active);
        color: var(--mp-nav-active-text);
    }

    .mp-nav-group.is-active > .mp-nav-row::before {
        position: absolute;
        inset-block: 11px;
        inset-inline-start: -1px;
        width: 3px;
        border-radius: 3px;
        background: var(--mp-nav-accent);
        content: '';
    }

    .mp-nav-link,
    .mp-nav-help,
    .mp-sidebar-toggle {
        display: flex;
        min-width: 0;
        min-height: 42px;
        align-items: center;
        gap: 12px;
        border-radius: 9px;
        padding: 10px 12px;
        color: inherit;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.6;
        text-align: start;
        text-decoration: none;
    }

    .mp-nav-link {
        flex: 1;
    }

    .mp-nav-label {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .mp-nav-link .mp-nav-icon {
        width: 21px;
        flex: 0 0 21px;
        color: inherit;
        font-size: 21px;
        line-height: 1;
        text-align: center;
    }

    .mp-nav-group.is-active > .mp-nav-row .mp-nav-icon {
        color: var(--mp-nav-accent);
    }

    .mp-nav-disclosure {
        display: grid;
        width: 30px;
        min-height: 32px;
        flex: 0 0 30px;
        place-items: center;
        margin-inline-end: 5px;
        border: 0;
        border-radius: 7px;
        background: transparent;
        color: var(--mp-nav-muted);
        cursor: pointer;
    }

    .mp-nav-disclosure:hover {
        background: var(--mp-nav-hover);
        color: var(--mp-nav-accent);
    }

    .mp-nav-disclosure svg {
        width: 16px;
        height: 16px;
        transition: transform 160ms ease;
    }

    .mp-nav-disclosure[aria-expanded="true"] svg {
        transform: rotate(180deg);
    }

    .mp-nav-submenu {
        display: grid;
        gap: 3px;
        margin-block: 7px 2px;
        margin-inline: 22px 5px;
        border-inline-start: 1px solid var(--mp-nav-line);
        padding-inline-start: 12px;
    }

    .mp-nav-submenu[hidden] {
        display: none !important;
    }

    .mp-nav-child {
        display: block;
        overflow-wrap: anywhere;
        border-radius: 7px;
        padding: 8px 10px;
        color: var(--mp-nav-muted);
        font-size: 12px;
        font-weight: 400;
        line-height: 1.55;
        text-decoration: none;
    }

    .mp-nav-child:hover,
    .mp-nav-child.is-active {
        background: var(--mp-nav-hover);
        color: var(--mp-nav-active-text);
    }

    .mp-nav-child[aria-current="page"] {
        font-weight: 600;
    }

    .mp-nav-grandchildren {
        display: grid;
        gap: 3px;
        padding-inline-start: 12px;
    }

    .mp-admin-sidebar__footer {
        display: grid;
        gap: 6px;
        flex-shrink: 0;
        border-block-start: 1px solid var(--mp-nav-line);
        padding: 10px 14px 12px;
    }

    .mp-nav-help,
    .mp-sidebar-toggle {
        width: 100%;
        border: 0;
        background: transparent;
        color: var(--mp-nav-muted);
        cursor: pointer;
    }

    .mp-nav-help svg,
    .mp-sidebar-toggle svg {
        width: 20px;
        height: 20px;
        flex-shrink: 0;
    }

    .mp-admin-sidebar .mp-sidebar-edge-toggle {
        position: absolute;
        inset-block-start: 18px;
        inset-inline-end: -14px;
        z-index: 1;
        display: grid;
        width: 28px;
        min-height: 28px;
        height: 28px;
        place-items: center;
        gap: 0;
        border: 1px solid var(--mp-nav-line);
        border-radius: 50%;
        padding: 0;
        background: var(--mp-nav-surface);
        box-shadow: 0 2px 6px rgb(0 0 0 / .08);
        color: var(--mp-nav-muted);
        opacity: 0;
        transition: opacity 120ms ease, background-color 120ms ease, color 120ms ease;
    }

    .mp-admin-sidebar:hover .mp-sidebar-edge-toggle,
    .mp-sidebar-edge-toggle:hover,
    .mp-sidebar-edge-toggle:focus-visible {
        opacity: 1;
    }

    .mp-admin-sidebar .mp-sidebar-edge-toggle:hover {
        background: var(--mp-nav-hover);
        color: var(--mp-nav-accent);
    }

    .mp-sidebar-edge-toggle svg {
        width: 16px;
        height: 16px;
    }

    .mp-sidebar-edge-toggle [data-mp-collapse-label] {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip-path: inset(50%);
        white-space: nowrap;
    }

    .mp-nav-help:hover,
    .mp-nav-help.is-active,
    .mp-sidebar-toggle:hover {
        background: var(--mp-nav-hover);
        color: var(--mp-nav-active-text);
    }

    .mp-nav-link:focus-visible,
    .mp-nav-child:focus-visible,
    .mp-nav-disclosure:focus-visible,
    .mp-nav-help:focus-visible,
    .mp-sidebar-toggle:focus-visible,
    .mp-admin-drawer-toggle:focus-visible,
    .mp-admin-header [data-mp-theme-toggle]:focus-visible {
        outline: 2px solid #3b82f6;
        outline-offset: 2px;
    }

    .mp-admin-shell.sidebar-collapsed .mp-admin-sidebar {
        width: 70px;
    }

    .mp-admin-shell.sidebar-collapsed .mp-admin-sidebar__scroll {
        padding-inline: 10px;
    }

    .mp-admin-shell.sidebar-collapsed .mp-admin-sidebar .mp-nav-label {
        display: none;
    }

    .mp-admin-shell.sidebar-collapsed .mp-nav-section__title {
        overflow: hidden;
        height: 1px;
        margin: 0 8px 13px;
        border-block-start: 1px solid var(--mp-nav-line);
        padding: 0;
        color: transparent;
        font-size: 0;
    }

    .mp-admin-shell.sidebar-collapsed .mp-nav-section:first-child .mp-nav-section__title {
        display: none;
    }

    .mp-admin-shell.sidebar-collapsed .mp-admin-sidebar .mp-nav-link,
    .mp-admin-shell.sidebar-collapsed .mp-admin-sidebar .mp-nav-help,
    .mp-admin-shell.sidebar-collapsed .mp-sidebar-toggle {
        justify-content: center;
        gap: 0;
        padding-inline: 0;
    }

    .mp-admin-shell.sidebar-collapsed .mp-admin-sidebar .mp-nav-disclosure {
        position: absolute;
        inset-block-end: -2px;
        inset-inline-end: 0;
        width: 19px;
        min-height: 19px;
        margin: 0;
        border: 1px solid var(--mp-nav-line);
        border-radius: 6px;
        background: var(--mp-nav-surface);
    }

    .mp-admin-shell.sidebar-collapsed .mp-admin-sidebar__footer {
        padding-inline: 10px;
    }

    .mp-admin-shell.sidebar-collapsed .mp-sidebar-toggle svg {
        transform: scaleX(-1);
    }

    [dir="rtl"] .mp-sidebar-toggle svg {
        transform: scaleX(-1);
    }

    [dir="rtl"] .mp-nav-section__title {
        letter-spacing: 0;
    }

    [dir="rtl"] .mp-admin-shell.sidebar-collapsed .mp-sidebar-toggle svg {
        transform: none;
    }

    .mp-admin-navigation--mobile .mp-nav-link,
    .mp-admin-navigation--mobile .mp-nav-child,
    .mp-admin-navigation--mobile .mp-nav-help {
        min-height: 44px;
        font-size: 13px;
    }

    .mp-admin-navigation--mobile .mp-nav-disclosure {
        min-height: 44px;
        flex-basis: 36px;
    }

    .mp-admin-mobile-scroll {
        height: calc(100dvh - 100px);
    }

    .mp-admin-content {
        min-height: calc(100dvh - var(--mp-admin-header-height, 62px));
        transition: padding 180ms ease;
    }

    .mp-nav-support {
        margin-block-start: 24px;
        border-block-start: 1px solid var(--mp-nav-line);
        padding-block-start: 12px;
    }

    @media (max-width: 1023px) {
        .mp-admin-sidebar {
            display: none;
        }
    }

    @media (hover: none), (pointer: coarse) {
        .mp-admin-sidebar .mp-sidebar-edge-toggle {
            width: 44px;
            min-height: 44px;
            height: 44px;
            inset-inline-end: -22px;
            opacity: 1;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .mp-admin-sidebar,
        .mp-admin-content,
        .mp-admin-sidebar .mp-sidebar-edge-toggle,
        .mp-nav-row,
        .mp-nav-disclosure svg {
            transition: none;
        }
    }
</style>
