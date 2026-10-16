@pushOnce('styles')
    <style>
        .mp-category-view-switch { display: flex; gap: 4px; margin-block: 18px 16px; padding: 4px; width: fit-content; border: 1px solid #d1d5db; border-radius: 9px; background: #f3f4f6; }
        .mp-category-view-switch a { padding: 8px 14px; border-radius: 6px; color: #4b5563; font-size: 14px; text-decoration: none; }
        .mp-category-view-switch a[aria-current] { background: #fff; color: #1d4ed8; box-shadow: 0 1px 3px #0001; font-weight: 600; }
        .mp-category-tree { --mp-tree-border: #d1d5db; --mp-tree-muted: #6b7280; --mp-tree-text: #111827; --mp-tree-surface: #fff; --mp-tree-hover: #f3f4f6; border: 1px solid var(--mp-tree-border); border-radius: 12px; background: var(--mp-tree-surface); color: var(--mp-tree-text); overflow: hidden; }
        .mp-category-toolbar { display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; padding: 18px 20px 10px; flex-wrap: wrap; }
        .mp-category-search { width: min(380px, 100%); }
        .mp-category-search label { display: block; font-size: 13px; font-weight: 500; margin-block-end: 7px; }
        .mp-category-search input { box-sizing: border-box; width: 100%; min-height: 42px; border: 1px solid var(--mp-tree-border); border-radius: 7px; background: var(--mp-tree-surface); color: var(--mp-tree-text); padding: 9px 12px; font: inherit; font-size: 14px; }
        .mp-category-tree-controls { display: flex; gap: 8px; flex-wrap: wrap; }
        .mp-category-tree-controls button { min-height: 42px; padding: 8px 12px; border: 1px solid var(--mp-tree-border); border-radius: 7px; background: transparent; color: var(--mp-tree-text); font: inherit; font-size: 13px; cursor: pointer; }
        .mp-category-guidance { margin: 0; padding: 0 20px 16px; color: var(--mp-tree-muted); font-size: 13px; line-height: 1.6; }
        .mp-category-legend { display: flex; justify-content: space-between; gap: 20px; padding: 11px 20px; border-block: 1px solid var(--mp-tree-border); font-size: 12px; color: var(--mp-tree-muted); }
        .mp-category-menu-heading { margin-inline-end: 66px; }
        .mp-category-tree ul { list-style: none; margin: 0; }
        .mp-category-roots { padding: 10px 12px 16px; }
        .mp-category-children { margin-inline-start: 19px !important; padding-inline-start: 20px; border-inline-start: 1px solid var(--mp-tree-border); }
        .mp-category-row { position: relative; display: flex; align-items: center; gap: 9px; min-height: 42px; padding: 2px 10px 2px 0; border-radius: 6px; }
        .mp-category-row:hover { background: var(--mp-tree-hover); }
        .mp-category-children > .mp-category-node > .mp-category-row::before { content: ''; position: absolute; inset-inline-start: -20px; width: 20px; inset-block-start: 23px; border-block-start: 1px solid var(--mp-tree-border); }
        .mp-category-toggle, .mp-category-gutter { display: inline-flex; align-items: center; justify-content: center; width: 36px; min-width: 36px; height: 36px; border: 0; border-radius: 6px; background: transparent; color: var(--mp-tree-muted); }
        .mp-category-toggle { cursor: pointer; }
        .mp-category-toggle svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .mp-category-toggle[aria-expanded=false] svg { transform: rotate(-90deg); }
        [dir=rtl] .mp-category-toggle[aria-expanded=false] svg { transform: rotate(90deg); }
        .mp-category-folder { width: 20px; height: 20px; min-width: 20px; fill: #eab3081a; stroke: #b88716; stroke-width: 1.6; stroke-linejoin: round; }
        .mp-category-name { min-width: 0; font-size: 14px; line-height: 1.4; overflow-wrap: anywhere; }
        .mp-category-child-count { padding: 1px 5px; border-radius: 5px; background: var(--mp-tree-hover); font-size: 11px; color: var(--mp-tree-muted); white-space: nowrap; }
        .mp-category-status { margin-inline-start: auto; flex-shrink: 0; white-space: nowrap; border-radius: 99px; padding: 3px 8px; font-size: 11px; background: #16a34a12; color: #15803d; }
        .mp-category-status.is-hidden { background: var(--mp-tree-hover); color: var(--mp-tree-muted); }
        .mp-category-edit { margin-inline-start: 8px; font-size: 12px; color: #2563eb; text-decoration: none; flex-shrink: 0; padding: 8px; }
        .mp-category-result, .mp-category-empty { margin: 0; padding: 12px 20px; color: var(--mp-tree-muted); font-size: 12px; }
        .mp-category-result { border-block-start: 1px solid var(--mp-tree-border); }
        .mp-category-empty { font-size: 14px; padding-block: 24px; }
        .mp-category-tree [hidden] { display: none !important; }
        .mp-category-tree :is(button,input,a):focus-visible, .mp-category-view-switch a:focus-visible { outline: 2px solid #3b82f6; outline-offset: 3px; }
        .dark .mp-category-tree { --mp-tree-border: #293547; --mp-tree-muted: #9aaac1; --mp-tree-text: #edf2fa; --mp-tree-surface: #0f1724; --mp-tree-hover: #182437; }
        .dark .mp-category-view-switch { background: #0f1724; border-color: #293547; }
        .dark .mp-category-view-switch a { color: #9aaac1; }
        .dark .mp-category-view-switch a[aria-current] { background: #21334e; color: #b8d2ff; }
        .dark .mp-category-folder { stroke: #d7b15a; }
        .dark .mp-category-status.is-visible { color: #93dfb0; background: #103b2c; }
        .dark .mp-category-edit { color: #91b8ff; }
        @media (max-width: 640px) {
            .mp-category-toolbar { padding: 14px 12px 10px; gap: 10px; }
            .mp-category-search { width: 100%; }
            .mp-category-guidance, .mp-category-legend { padding-inline: 12px; }
            .mp-category-menu-heading { display: none; }
            .mp-category-roots { padding-inline: 6px; }
            .mp-category-children { padding-inline-start: 12px; margin-inline-start: 13px !important; }
            .mp-category-children > .mp-category-node > .mp-category-row::before { inset-inline-start: -12px; width: 12px; }
            .mp-category-row { gap: 5px; flex-wrap: wrap; padding-block: 5px; }
            .mp-category-toggle, .mp-category-gutter { width: 26px; min-width: 26px; height: 38px; }
            .mp-category-folder { width: 17px; min-width: 17px; }
            .mp-category-name { flex: 1 1 100px; font-size: 13px; }
            .mp-category-child-count { white-space: normal; }
            .mp-category-status { margin-inline-start: 31px; }
            .mp-category-edit { margin-inline-start: auto; }
            .mp-category-result { padding-inline: 12px; }
        }
    </style>
@endpushOnce
