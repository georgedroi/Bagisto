<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .msw-hidden { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
    .msw-icon { flex: none; width: 16px; height: 16px; }
    .msw-menu { position: fixed; inset: 0 auto 0 0; z-index: 3; display: flex; flex-direction: column; width: 220px; background: #fff; box-shadow: 1px 0 0 rgba(17, 24, 39, .06), 8px 0 30px -12px rgba(30, 41, 90, .14); color: rgba(0, 0, 0, .65); font: 14px/18px Roboto, "Helvetica Neue", Helvetica, Tahoma, Arial, sans-serif; }
    .msw-menu a { color: inherit; text-decoration: none; }
    .msw-logo { display: flex; align-items: center; height: 92px; padding: 0 12px; color: #1a71ff; font-size: 20px; }
    .msw-logo img { max-width: 196px; max-height: 64px; }
    .msw-logo-mark, .msw-menu-search-shortcut { display: none; }
    .msw-menu-search { position: relative; display: block; flex: none; width: 206px; margin: 0 0 8px 10px; color: #858b9c; }
    .msw-menu-search input { width: 100%; height: 28px; padding: 0 30px 0 12px; border: 1px solid #1a71ff; border-radius: 16px; outline: 0; background: #fff; color: rgba(0, 0, 0, .65); }
    .msw-menu-search input::placeholder { color: #858b9c; }
    .msw-menu-search .msw-icon { position: absolute; top: 6px; right: 12px; }
    .msw-menu-list { flex: 1; min-height: 0; overflow-y: auto; border-top: 1px solid #eee; scrollbar-width: thin; scrollbar-color: #c4c6cf transparent; overscroll-behavior: contain; }
    .msw-menu summary { display: flex; align-items: center; height: 38px; padding: 0 8px 0 17px; list-style: none; cursor: pointer; }
    .msw-menu summary::-webkit-details-marker { display: none; }
    .msw-menu summary:hover, .msw-menu-list a:hover { background: #f5f8fd; }
    .msw-menu summary:focus-visible, .msw-menu-list a:focus-visible, .msw-menu-search-shortcut:focus-visible { outline: 2px solid #1a71ff; outline-offset: -2px; }
    .msw-menu summary span { flex: 1; margin-left: 9px; }
    .msw-menu summary .msw-icon:first-child { width: 20px; height: 20px; }
    details:not([open]) > summary .msw-icon:last-child { transform: rotate(180deg); }
    .msw-menu-list a { display: flex; align-items: center; height: 36px; padding-left: 40px; font-size: 13px; line-height: 20px; }
    .msw-menu-list a:hover, .msw-menu-list a.is-active { color: #1a71ff; }
    .msw-menu-list a.is-active { font-weight: 500; }
    .msw-menu-list details:first-child a { padding-left: 44px; }
    .msw-menu-toggle { position: absolute; top: 70px; right: -12px; z-index: 4; display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; padding: 0; border: 0; border-radius: 50%; background: #fff; box-shadow: 0 2px 12px rgba(0, 0, 0, .16); color: #2e3346; cursor: pointer; opacity: 0; transition: opacity .15s; }
    .msw-menu-toggle .msw-icon { width: 14px; height: 14px; transform: rotate(180deg); }
    .msw-menu:hover .msw-menu-toggle, .msw-menu-toggle:focus-visible { opacity: 1; }
    @media (min-width: 1024px) and (pointer: coarse) {
        .msw-menu summary, .msw-menu-list a { height: 44px; }
        .msw-menu-toggle { opacity: 1; }
        .msw-menu-toggle::before { content: ""; position: absolute; inset: -10px; }
    }
    @media (min-width: 1024px) {
        html.msw-collapsed .msw-menu { width: 55px; }
        html.msw-collapsed .msw-menu-toggle .msw-icon { transform: none; }
        html.msw-collapsed .msw-logo { width: 55px; overflow: hidden; }
        html.msw-collapsed .msw-logo > :not(.msw-logo-mark) { display: none; }
        html.msw-collapsed .msw-logo .msw-logo-mark { display: block; width: 32px; height: 32px; object-fit: contain; }
        html.msw-collapsed .msw-menu-search-shortcut { display: flex; align-items: center; justify-content: center; flex: none; width: 44px; height: 32px; margin: 0 5px 8px; border: 0; background: transparent; color: #1a71ff; cursor: pointer; }
        html.msw-collapsed .msw-menu-search, html.msw-collapsed .msw-menu-foot, html.msw-collapsed .msw-menu summary span, html.msw-collapsed .msw-menu summary .msw-icon:last-child { display: none; }
        html.msw-collapsed .msw-menu summary { justify-content: center; width: 44px; margin-left: 5px; padding: 0; border-radius: 12px; }
        html.msw-collapsed .msw-menu details.is-fly > summary, html.msw-collapsed .msw-menu details:has(a.is-active) > summary { background: #eaf1ff; color: #1a71ff; }
        html.msw-collapsed .msw-menu details:not(.is-fly) .msw-menu-links { display: none; }
        html.msw-collapsed .msw-menu details.is-fly .msw-menu-links { position: fixed; left: 55px; z-index: 5; width: 200px; max-height: calc(100vh - 16px); overflow: auto; padding: 4px 0; background: #fff; box-shadow: 4px 2px 16px rgba(0, 0, 0, .12); }
        html.msw-collapsed .msw-menu details.is-fly .msw-menu-links a { padding-left: 16px; }
    }
    .msw-menu-foot { flex: none; display: flex; align-items: center; width: 200px; height: 60px; padding-left: 20px; border-top: 1px solid #eee; font-weight: 500; color: #2e3346; }

    .msw-rail { position: fixed; inset: 0 0 0 auto; z-index: 3; display: flex; flex-direction: column; align-items: center; gap: 12px; width: 65px; padding: 40px 0 16px; background: #fff; box-shadow: rgba(0, 0, 0, .1) 1px 1px 18px; }
    .msw-rail a, .msw-rail button { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; padding: 0; border: 0; border-radius: 50%; background: #f5f8fd; color: #1a71ff; cursor: pointer; }
    .msw-rail .msw-icon { width: 24px; height: 24px; }
    .msw-rail form { margin-top: auto; }
    .msw-rail button { background: transparent; color: #4f5669; }
    .msw-rail a, .msw-rail button { transition: transform .18s ease, box-shadow .18s ease; }
    .msw-rail a:hover, .msw-rail button:hover { transform: translateY(-1px); }
    @media (prefers-reduced-motion: reduce) {
        .msw-rail * { transition: none !important; transform: none !important; }
    }

    @media (max-width: 1023px) {
        .msw-rail { position: static; flex-direction: row; justify-content: center; width: 100%; padding: 8px; box-shadow: none; border-top: 1px solid #eee; }
        .msw-rail a, .msw-rail button { width: 44px; height: 44px; }
        .msw-rail form { margin: 0; }
        .msw-menu { position: static; width: 100%; }
        .msw-logo { height: 56px; }
        .msw-menu-search { width: auto; margin-right: 10px; }
        .msw-menu-list { display: flex; gap: 4px; overflow-x: auto; }
        .msw-menu-toggle { display: none; }
        .msw-menu-list details, .msw-menu-list details::details-content, .msw-menu-links { display: contents; content-visibility: visible; }
        .msw-menu-list details > summary, .msw-menu-list details:first-child a { display: none; }
        .msw-menu-list a { flex: none; height: 44px; padding: 0 12px; white-space: nowrap; }
        .msw-menu-foot { display: none; }
    }
</style>
<script>
    try { const saved = document.cookie.match(/(?:^|;\s*)sidebar_collapsed=([01])(?:;|$)/)?.[1] ?? localStorage.getItem('msw-menu-collapsed'); document.documentElement.classList.toggle('msw-collapsed', saved === '1'); } catch (error) {}
</script>
