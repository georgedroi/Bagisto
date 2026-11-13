<style>
    * { box-sizing: border-box; }
    html, body { margin: 0; }
    body { background: #f2f3f7; color: rgba(0, 0, 0, .65); font: 14px/18px Roboto, "Helvetica Neue", Helvetica, Tahoma, Arial, sans-serif; }
    a { color: inherit; text-decoration: none; }
    p, h1, h2, h3 { margin: 0; }
    button, input, select { font: inherit; }

    .msw-deal-dates, .msw-table small.msw-deal-dates { display: block; margin-top: 0; margin-bottom: 2px; color: #e02020; font-size: 11px; line-height: 16px; white-space: nowrap; }
    @media (min-width: 1024px) {
        html.msw-collapsed .msw-main { margin-left: 55px; }
    }

    .msw-main { min-height: 100vh; margin: 0 65px 0 220px; padding: 20px 16px 24px; }
    .msw-main-hero { padding-top: 120px; background: linear-gradient(#f71515 0, #f71515 240px, #f2f3f7 396px); background-repeat: no-repeat; }
    .msw-crumb { display: flex; align-items: center; height: 16px; line-height: 16px; color: #858b9c; }
    .msw-crumb .msw-icon { width: 12px; height: 12px; margin: 0 6px 0 5px; }
    .msw-crumb strong { font-weight: 700; color: #2e3346; }
    .msw h1 { margin-top: 16px; font-size: 24px; font-weight: 600; line-height: 24px; color: rgba(0, 0, 0, .65); }
    .msw-h1-strong { margin-top: 8px !important; line-height: 25px !important; font-weight: 700 !important; color: #2e3346 !important; }
    .msw-card-round { border-radius: 18px; padding: 20px; }
    [data-msw-page="fees"] .msw-main { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
    [data-msw-page="fees"] .msw-card { border-radius: 10px; padding: 13px; box-shadow: rgba(0, 0, 0, .1) 1px 1px 18px 1px; }
    [data-msw-page="fees"] .msw-field { height: 36px; border-color: #cbced5; border-radius: 4px; }
    [data-msw-page="fees"] .msw-field > span { line-height: 34px; }
    [data-msw-page="fees"] .msw-primary { height: 36px; border-radius: 4px; background: #2f6ff5; }
    [data-msw-page="fees"] .msw-table { border: 0; border-radius: 10px 10px 0 0; }
    [data-msw-page="fees"] .msw-table th { background: #f2f3f7; font-weight: 700; color: #333; }
    .msw-title-row { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 8px; }
    .msw-hero-title { position: absolute; top: 25px; margin: 0 0 0 42px !important; font-size: 40px !important; font-weight: 700 !important; line-height: 40px !important; color: #fff !important; }

    .msw-card { margin-top: 16px; padding: 16px 20px; border-radius: 16px; background: #fff; box-shadow: rgba(132, 152, 208, .07) 0 1px 12px 1px; }
    .msw-card h2 { font-size: 18px; font-weight: 700; line-height: 23px; color: rgba(0, 0, 0, .85); }
    .msw-card-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 8px; }
    .msw-blue { color: #1a71ff; }

    .msw-tabs { display: flex; gap: 32px; overflow-x: auto; margin: 23px 8px -1px; scrollbar-width: none; }
    .msw-card + .msw-tabs { margin-top: 19px; }
    .msw-tabs a { position: relative; flex: none; padding: 9px 0 14px; font-size: 16px; line-height: 20px; color: #4f5669; white-space: nowrap; }
    .msw-tabs a.is-active { font-weight: 500; color: #1a71ff; }
    .msw-tabs a.is-active::after { content: ""; position: absolute; left: -4px; right: -4px; bottom: 0; height: 3px; border-radius: 2px; background: #1a71ff; }
    .msw-tabs sup { display: inline-block; min-width: 16px; height: 16px; margin-left: 4px; padding: 0 4px; border-radius: 8px; background: #1a71ff; font-size: 12px; font-weight: 400; line-height: 16px; text-align: center; color: #fff; vertical-align: top; position: relative; top: -4px; }
    .msw-tabs-sm { gap: 0; margin: 0; border-bottom: 1px solid #eee; }
    .msw-tabs-sm a { padding: 12px 16px; font-size: 14px; line-height: 18px; }
    .msw-tabs-sm a.is-active::after { left: 0; right: 0; height: 2px; }
    .msw-page-tabs { margin: 12px 0 -5px; border-bottom: 0; }
    .msw-subtabs { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 15px; margin-top: 16px; }
    .msw-subtabs a { display: flex; align-items: center; justify-content: center; height: 56px; border-radius: 8px; background: #f5f8fd; font-size: 16px; font-weight: 500; color: rgba(0, 0, 0, .65); box-shadow: rgba(0, 0, 0, .04) 0 2px 10px; }

    .msw-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .msw-chip { display: inline-flex; align-items: center; height: 28px; padding: 0 12px; border: 1px solid #f2f3f7; border-radius: 8px; background: #f2f3f7; color: #4f5669; }
    .msw-chip.is-active { border-color: #1a71ff; background: #e8f1ff; color: #1a71ff; }
    .msw-filter { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin: 0; }
    .msw-field { display: flex; flex: 1 1 280px; min-width: 0; max-width: 451px; height: 32px; overflow: hidden; border: 1px solid #c4c6cf; border-radius: 8px; background: #fff; }
    .msw-field > span { flex: none; padding: 0 8px; background: #f2f3f7; line-height: 30px; color: #4f5669; white-space: nowrap; }
    .msw-field input, .msw-field select { flex: 1; min-width: 0; padding: 0 8px; border: 0; outline: 0; background: transparent; color: rgba(0, 0, 0, .65); }
    .msw-field .msw-icon { width: 18px; height: 18px; }
    .msw-field button { flex: none; width: 32px; padding: 0; border: 0; background: transparent; color: #4f5669; cursor: pointer; }
    .msw-button { display: inline-flex; align-items: center; justify-content: center; gap: 4px; height: 32px; padding: 0 12px; border: 1px solid #9fb7f9; border-radius: 8px; background: #fff; line-height: 14px; color: #1a71ff; white-space: nowrap; cursor: pointer; }
    .msw-button:disabled { opacity: .6; cursor: wait; }
    .msw-primary { border-color: transparent; background: #1a71ff; color: #fff; }

    .msw-summary { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; min-height: 64px; }
    .msw-toolbar { display: flex; align-items: center; min-height: 52px; padding: 0 0 16px 20px; }
    .msw-tasks { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
    .msw-task { position: relative; display: block; height: 60px; padding: 8px; border: 1px solid #eee; border-radius: 8px; }
    .msw-task b { display: flex; align-items: center; gap: 4px; height: 30px; font-size: 24px; font-weight: 500; color: #1a71ff; }
    .msw-task b .msw-icon { width: 24px; height: 24px; color: #2e3346; }
    .msw-task small { display: block; font-size: 12px; line-height: 16px; color: #595f6d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .msw-task > .msw-icon { position: absolute; top: 22px; right: 8px; color: #1a71ff; }
    .msw-task-groups { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0 60px; margin: 33px 16px 0; }
    .msw-task-groups h3 { display: flex; gap: 4px; height: 16px; font-size: 14px; font-weight: 500; line-height: 16px; color: rgba(0, 0, 0, .85); }
    .msw-task-line { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-top: 8px; min-height: 16px; line-height: 16px; color: #595f6d; }
    .msw-task-line b { display: flex; align-items: center; font-weight: 400; color: #1a71ff; }
    .msw-task-line .msw-icon { width: 14px; height: 14px; }
    .msw-top { display: grid; grid-template-columns: minmax(0, 1fr) 309px; gap: 16px; align-items: start; }
    .msw-top > * { min-width: 0; }
    .msw-top .msw-card { margin-top: 0; padding: 20px; }
    .msw-top > .msw-card { min-height: 302px; }
    .msw-top > div > .msw-card:last-child { padding-bottom: 15px; }
    .msw-top > div > .msw-card + .msw-card { margin-top: 18px; }
    .msw-title { font-size: 20px !important; font-weight: 600 !important; line-height: 23px !important; color: #2e3346 !important; }
    .msw-top .msw-profile { height: 130px; padding: 16px 20px; }
    .msw-profile-top { display: flex; gap: 16px; }
    .msw-avatar { display: flex; flex: none; align-items: center; justify-content: center; width: 43px; height: 43px; border: 1px solid #e5e7eb; border-radius: 50%; font-weight: 700; color: #1a71ff; }
    .msw-profile strong { display: block; font-size: 16px; line-height: 19px; color: rgba(46, 51, 70, .88); }
    .msw-tag { display: inline-block; margin-top: 4px; padding: 0 8px; border-radius: 6px; background: #f2f3f7; font-size: 12px; line-height: 19px; color: #6b7280; }
    .msw-profile-tools { display: flex; align-items: center; justify-content: flex-end; gap: 8px; margin-top: 23px; }
    .msw-profile-tools a { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; background: #f5f8fd; color: #2e3346; }
    .msw-profile-tools a:first-child { width: auto; height: 33px; margin-right: auto; padding: 0 8px; border-radius: 8px; font-size: 12px; color: rgba(0, 0, 0, .65); }

    .msw-grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
    .msw-grid-4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-top: 16px; }
    .msw-tile { display: block; min-width: 0; padding: 10px 10px 14px 16px; border-radius: 8px; background: #f5f8fd; }
    .msw-tile span { display: block; font-size: 16px; font-weight: 500; line-height: 24px; color: #2e3346; }
    .msw-tile strong { display: block; overflow-wrap: anywhere; font-size: 16px; font-weight: 500; line-height: 24px; color: #1a71ff; }
    .msw-tile-big { min-height: 160px; padding: 20px 12px 12px; background: #f4f8ff; box-shadow: rgba(132, 152, 208, .07) 0 1px 12px 1px; }
    .msw-tile-big span { line-height: 20px; color: #111; }
    .msw-tile-big strong { margin-top: 16px; font-size: 40px; font-weight: 700; line-height: 47px; }
    .msw-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .msw-grid-2 > .msw-card { min-width: 0; }
    .msw-tile .msw-change { display: block; margin-top: 2px; font-size: 12px; line-height: 18px; color: #858b9c; }
    .msw-change.is-up { color: #1d8a4e; }
    .msw-change.is-down, .msw-rank-row strong.is-down { color: #d93025; }
    a.msw-tile.is-active, .msw-subtabs a.is-active { outline: 2px solid #1a71ff; outline-offset: -2px; color: #1a71ff; }
    .msw-field-sm { flex: 0 1 auto; min-width: 0; }
    .msw-bars { display: flex; align-items: flex-end; gap: 3px; height: 180px; margin-top: 12px; padding: 0 2px 22px; border-bottom: 1px solid #e5e7ee; }
    .msw-bar { position: relative; display: flex; flex: 1 1 0; flex-direction: column; justify-content: flex-end; min-width: 0; height: 100%; }
    .msw-bar i { display: block; min-height: 2px; border-radius: 4px 4px 0 0; background: linear-gradient(180deg, #4d8fff 0%, #1a71ff 100%); }
    .msw-bar small { position: absolute; top: 100%; left: 50%; margin-top: 4px; font-size: 11px; line-height: 16px; white-space: nowrap; color: #858b9c; transform: translateX(-50%); }
    .msw-rank { margin-top: 12px; }
    .msw-rank-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; border-top: 1px solid #f0f1f5; }
    .msw-rank-row:first-child { border-top: 0; }
    .msw-rank-row > div { min-width: 0; }
    .msw-rank-row b { display: block; overflow-wrap: anywhere; color: #2e3346; }
    .msw-rank-row small { display: block; overflow-wrap: anywhere; font-size: 12px; line-height: 18px; color: #858b9c; }
    .msw-rank-row > strong { flex: none; font-variant-numeric: tabular-nums; color: #1a71ff; }
    .msw-card-tall { padding-top: 12px; }
    .msw-card-tall .msw-title { font-weight: 700 !important; }
    .msw-card-tall .msw-grid-4 { margin-top: 20px; }
    .msw-outline { min-height: 87px; padding: 14px 16px; border: 1px solid #d9d9d9; border-radius: 8px; background: #fff; }
    .msw-outline span { font-weight: 400; line-height: 25px; color: rgba(46, 51, 70, .88); }
    .msw-outline strong { margin-top: 6px; font-size: 20px; font-weight: 700; }
    .msw-outline.is-active { border-color: #1a71ff; }

    .msw-table { margin-top: 16px; overflow-x: auto; border: 1px solid #eee; border-radius: 18px; background: #fff; }
    .msw-table table { width: 100%; min-width: 760px; border-collapse: collapse; }
    .msw-table th { height: 58px; padding: 8px 16px; background: #fff; font-weight: 400; text-align: left; color: #2e3346; white-space: nowrap; }
    .msw-product-table th:first-child, .msw-product-table td:first-child { width: 33.2%; padding-left: 76px; }
    .msw-product-table th:nth-child(2) { width: 15.8%; }
    .msw-product-table th:nth-child(3) { width: 12.9%; }
    .msw-product-table th:nth-child(4) { width: 28.8%; }
    .msw-table td { padding: 16px; border-top: 1px solid #eee; vertical-align: top; overflow-wrap: anywhere; }
    .msw-table small { display: block; margin-top: 2px; font-size: 12px; color: #858b9c; }
    .msw-table td a { color: #1a71ff; }
    .msw-table-soft { border-radius: 10px; border-color: #f0f0f0; }
    .msw-table-soft th { height: 46px; background: #f5f8fd; font-weight: 600; line-height: 22px; color: rgba(46, 51, 70, .88); }
    .msw-product-table { overflow: visible; }
    .msw-product-table th { background: transparent; }
    .msw-product { display: flex; gap: 10px; }
    .msw-product img, .msw-product i { flex: none; width: 48px; height: 48px; border: 1px solid #eee; border-radius: 8px; background: #f5f8fd; object-fit: cover; }
    .msw-product b { display: block; font-weight: 500; color: #2e3346; }
    .msw-switch { display: block; width: 44px; height: 24px; padding: 2px; border-radius: 12px; background: #c4c6cf; }
    .msw-switch::after { content: ""; display: block; width: 20px; height: 20px; border-radius: 50%; background: #fff; }
    .msw-switch.is-on { background: #1a71ff; }
    .msw-switch.is-on::after { margin-left: 20px; }
    .msw-empty { padding: 48px 12px 120px; font-size: 38px; line-height: 44px; text-align: center; color: #cbced5; }
    .msw-table .msw-empty, .msw-card .msw-empty { padding: 32px 12px; font-size: 14px; line-height: 22px; color: #858b9c; }
    .msw-pill { display: inline-block; padding: 2px 8px; border-radius: 8px; background: #f2f3f7; font-size: 12px; white-space: nowrap; color: #4f5669; }
    .msw-note { margin-top: 16px; padding: 12px 16px; border: 1px solid #e8f1ff; border-radius: 8px; background: #e8f1ff; line-height: 20px; }
    .msw-pager { display: flex; align-items: center; gap: 4px; margin: 16px 0 0 -3px; }
    .msw-pager a { display: inline-flex; align-items: center; gap: 2px; height: 32px; padding: 0 8px; border: 1px solid #eee; border-radius: 8px; color: #1a71ff; }
    .msw-pager a[aria-disabled] { color: #cbced5; }
    .msw-pager strong { display: flex; align-items: center; justify-content: center; width: 30px; height: 32px; border-radius: 8px; background: #1a71ff; font-weight: 400; color: #fff; }

    .msw-order-head, .msw-order-row { display: grid; grid-template-columns: 636fr 120fr 160fr 230fr 190fr; gap: 0; padding: 12px 20px 12px 52px; }
    .msw-order-head { align-items: center; height: 42px; margin-top: 16px; border-radius: 16px; background: #fff; color: #4f5669; box-shadow: rgba(132, 152, 208, .07) 0 1px 12px 1px; }
    .msw-order-row { margin-top: 12px; border-radius: 16px; background: #fff; box-shadow: rgba(132, 152, 208, .07) 0 1px 12px 1px; }
    .msw-order-row > div { min-width: 0; overflow-wrap: anywhere; }
    .msw-order-row .msw-pill { max-width: 100%; white-space: normal; }
    .msw-order-row .msw-button { max-width: 100%; height: auto; min-height: 32px; padding-block: 8px; white-space: normal; text-align: center; }
    .msw-order-row small { display: block; margin-top: 2px; font-size: 12px; color: #858b9c; }
    .msw-order-row strong { font-weight: 500; color: #2e3346; }
    .msw-inline { margin: 6px 0 0; }

    .msw-profile-head { display: flex; gap: 16px; min-height: 110px; margin-top: -4px; padding-bottom: 16px; border-bottom: 1px solid #efefef; }
    .msw-profile-head .msw-avatar { width: 80px; height: 80px; border-color: #efefef; font-size: 28px; }
    .msw-profile-head h2 { font-size: 20px; font-weight: 600; line-height: 26px; color: rgba(0, 0, 0, .65); }
    .msw-set-title { margin-top: 32px; font-size: 20px !important; font-weight: 600 !important; line-height: 24px !important; color: #2e3346 !important; }
    .msw-profile-head + .msw-set-title { margin-top: 16px; }
    .msw-set { display: flex; align-items: center; gap: 12px; height: 68px; padding: 8px 12px; border-radius: 12px; background: #f5f8fd; }
    .msw-set > span { display: flex; flex: none; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 50%; background: #fff; color: #1a71ff; }
    .msw-set > span .msw-icon { width: 28px; height: 28px; }
    .msw-set b { display: block; font-size: 16px; font-weight: 500; line-height: 20px; color: #2e3346; }
    .msw-set small { display: block; margin-top: 4px; font-size: 12px; line-height: 14px; color: #595f6d; }

    .msw-return { display: grid; grid-template-columns: 456px 456px minmax(0, 1fr); gap: 8px 0; }
    .msw-return small { color: #858b9c; font-size: 14px; }
    .msw-return-foot { display: flex; justify-content: flex-end; margin-top: 16px; padding-top: 16px; border-top: 1px dashed #eee; }

    .msw-pencil { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; padding: 0; border: 0; background: none; color: #595f6d; vertical-align: middle; cursor: pointer; }
    .msw-pencil .msw-icon { width: 16px; height: 16px; }
    .msw-more > button { display: inline-flex; align-items: center; gap: 4px; margin-top: 4px; padding: 0; border: 0; background: none; font: inherit; color: #1a71ff; cursor: pointer; }
    .msw-more-menu { position: fixed; z-index: 5; width: 185px; padding: 6px 0; border: 1px solid #c4c6cf; border-radius: 8px; background: #fff; box-shadow: rgba(0, 0, 0, .1) 0 2px 8px; }
    .msw-more-menu[hidden] { display: none; }
    .msw-more-menu form { margin: 0; }
    .msw-more-menu button { display: flex; align-items: center; gap: 8px; width: 100%; min-height: 32px; padding: 0 16px; border: 0; background: none; font: inherit; font-size: 14px; text-align: start; color: #595f6d; cursor: pointer; }
    .msw-more-menu button:hover { background: #f5f8fd; }
    .msw-more-menu small:not(:empty) { padding: 0 16px 6px; }
    .msw-alert-badge { display: flex; align-items: center; gap: 4px; margin-top: 4px; font-size: 12px; color: #e72b00; }
    .msw-alert-badge .msw-icon { width: 14px; height: 14px; }
    .msw-alert-hint { display: block; margin-top: 4px; font-size: 12px; color: #858b9c; }
    .msw-modal.msw-modal-alert { width: 738px; }
    .msw-modal-alert .msw-modal-title { padding: 16px 40px 0; font-size: 18px; font-weight: 500; text-align: center; }
    .msw-modal-intro { margin: 12px 24px 0; font-size: 16px; line-height: 22px; text-align: center; color: #595f6d; }
    .msw-modal-alert .msw-modal-foot { justify-content: center; padding: 12px 24px 16px; }
    .msw-modal-alert .msw-modal-foot small { margin: 0; }
    .msw-modal-alert .msw-modal-table > thead > tr > th:nth-child(2), .msw-modal-alert .msw-modal-table > thead > tr > th:nth-child(3) { width: 200px; }
    .msw-alert-input { width: 120px; height: 32px; padding: 0 8px; border: 1px solid #c4c6cf; border-radius: 8px; font: inherit; color: #595f6d; }
    .msw-switch-field { position: relative; display: inline-block; cursor: pointer; }
    .msw-switch-field input { position: absolute; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; }
    .msw-switch-field input:checked + .msw-switch { background: #1a71ff; }
    .msw-switch-field input:checked + .msw-switch::after { margin-left: 20px; }
    .msw-switch-field input:focus-visible + .msw-switch { outline: 2px solid #1a71ff; outline-offset: 2px; }
    .msw-status { margin: 0; }
    button.msw-switch { box-sizing: border-box; border: 0; cursor: pointer; }
    .msw-preorder { display: inline-block; margin: 8px 0 0 48px; padding: 2px 8px; border-radius: 4px; background: #e8f1ff; font-size: 12px; color: #1a71ff; }
    .msw-preorder[hidden] { display: none; }
    .msw-help { display: inline-flex; align-items: center; gap: 2px; cursor: help; }
    .msw-help .msw-icon { width: 14px; height: 14px; }
    .msw-batch-date { display: flex; align-items: center; justify-content: space-between; gap: 4px; width: 146px; height: 24px; padding: 0 4px; border: 1px solid #c4c6cf; border-radius: 4px; background: #fff; font: inherit; font-size: 12px; color: #595f6d; cursor: pointer; }
    .msw-batch-date .msw-icon { width: 14px; height: 14px; }
    .msw-batch-link { padding: 0; border: 0; background: none; font: inherit; color: #1a71ff; cursor: pointer; }
    .msw-batch-link:disabled { color: #c4c6cf; cursor: default; }
    .msw-calendar.msw-batch-cal { position: fixed; z-index: 3; top: auto; width: 287px; max-width: calc(100vw - 16px); margin: 0; }
    .msw-was { display: block; margin-top: 4px; font-size: 14px; color: #9aa0ac; }
    .msw-price { font-weight: 600; color: #111827; }
    .msw-online { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
    .msw-online::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: #c4c6cf; }
    .msw-online.is-on::before { background: #1fb33e; }
    .msw-action { display: flex; flex-direction: column; align-items: flex-start; gap: 8px; }
    .msw .msw-more-menu a { display: flex; align-items: center; gap: 8px; min-height: 32px; padding: 0 16px; font-size: 14px; color: #595f6d; text-decoration: none; }
    .msw-more-menu a:hover { background: #f5f8fd; }

    .msw-modal { width: 900px; max-width: calc(100vw - 16px); max-height: calc(100dvh - 16px); margin: auto; padding: 0; overflow: auto; border: 1px solid transparent; border-radius: 16px; background: #fff; color: rgba(0, 0, 0, .65); box-shadow: rgba(132, 152, 208, .07) 0 1px 12px 1px; }
    .msw-modal-stock { width: 1100px; }
    .msw-modal::backdrop { background: rgba(0, 0, 0, .6); }
    .msw-modal form { position: relative; margin: 0; }
    .msw-modal-title { margin: 0; padding: 16px 40px 12px 24px; font-size: 20px; font-weight: 400; line-height: 26px; color: #2e3346; }
    .msw-modal-close, .msw-date-close { position: absolute; display: flex; align-items: center; justify-content: center; padding: 0; border: 0; background: none; color: #595f6d; cursor: pointer; }
    .msw-modal-close { top: 12px; right: 12px; width: 20px; height: 20px; }
    .msw-modal-body { padding: 16px 24px 12px; }
    .msw-modal-table { width: 100%; border: 1px solid #eee; border-radius: 16px; border-spacing: 0; border-collapse: separate; table-layout: fixed; font-size: 14px; }
    .msw-modal-table > thead > tr > th { padding: 16px; border-bottom: 1px solid #eee; background: #f5f8fd; font-weight: 700; line-height: 21px; text-align: start; color: rgba(0, 0, 0, .65); }
    .msw-modal-table > thead > tr > th:first-child { border-top-left-radius: 16px; }
    .msw-modal-table > thead > tr > th:last-child { border-top-right-radius: 16px; }
    .msw-modal-table > tbody > tr > td { padding: 12px 16px; vertical-align: top; }
    .msw-modal-price .msw-modal-table > thead > tr > th:first-child { width: 356px; }
    .msw-modal-price .msw-modal-table > thead > tr > th:nth-child(2) { width: 236px; }
    .msw-modal-stock .msw-modal-table > thead > tr > th:first-child { width: 298px; }
    .msw-modal-stock .msw-modal-table > thead > tr > th:nth-child(2) { width: 179px; }
    .msw-modal-stock .msw-modal-table > thead > tr > th:nth-child(3) { width: 119px; }
    .msw-modal-stock .msw-modal-table > thead > tr > th:nth-child(4), .msw-modal-stock .msw-modal-table > thead > tr > th:nth-child(5) { width: 179px; }
    .msw-modal-foot { display: flex; align-items: center; justify-content: flex-end; gap: 12px; padding: 12px 21px 13px 24px; }
    .msw-modal-foot small { margin-inline-end: auto; }
    .msw-modal-cancel { border-color: #c4c6cf; color: #595f6d; }

    .msw-product-info { display: flex; align-items: center; gap: 8px; min-width: 0; font-size: 12px; color: #9c9c9c; }
    .msw-product-info img, .msw-product-info i { flex: none; width: 40px; height: 40px; border: 1px solid #ebecf0; border-radius: 8px; background: #f2f3f7; object-fit: cover; }
    .msw-product-info span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .msw-copy { display: inline-flex; flex: none; padding: 0; border: 0; background: none; color: #858b9c; cursor: pointer; }
    .msw-copy .msw-icon { width: 14px; height: 14px; }
    .msw-copy.is-copied { color: #1a71ff; }

    .msw-money-row { display: flex; align-items: center; gap: 12px; }
    .msw-money { display: flex; flex: none; align-items: center; gap: 12px; width: 200px; height: 32px; padding: 0 12px; border: 1px solid #c4c6cf; border-radius: 8px; background: #fff; }
    .msw-money:focus-within, .msw-date-range > button:focus-visible, .msw-date-range > button.is-active { border-color: #1a71ff; box-shadow: 0 0 0 2px rgba(26, 113, 255, .15); }
    .msw-money span { font-size: 12px; color: #858b9c; }
    .msw-money input { flex: 1; min-width: 0; height: 28px; padding: 0; border: 0; outline: 0; background: none; font: inherit; color: #595f6d; appearance: textfield; }
    .msw-money input::-webkit-inner-spin-button, .msw-money input::-webkit-outer-spin-button { margin: 0; appearance: none; }
    .msw-clock { display: inline-flex; flex: none; padding: 0; border: 0; background: none; color: #1a71ff; cursor: pointer; }
    .msw-clock .msw-icon { width: 20px; height: 20px; stroke-width: 2.2; }

    .msw-stock-cell { padding: 12px 0 !important; }
    .msw-stock-table { width: 100%; border-spacing: 0; table-layout: fixed; }
    .msw-stock-table th { height: 45px; padding: 0 16px; background: #f1f3f7; font-weight: 700; text-align: start; color: rgba(0, 0, 0, .65); }
    .msw-stock-table th:first-child { width: 179px; border-top-left-radius: 16px; border-bottom-left-radius: 16px; }
    .msw-stock-table th:nth-child(2) { width: 119px; }
    .msw-stock-table th:nth-child(3), .msw-stock-table th:nth-child(4) { width: 179px; }
    .msw-stock-table th:last-child { border-top-right-radius: 16px; border-bottom-right-radius: 16px; }
    .msw-stock-table td { height: 45px; padding: 0 16px; }
    .msw-stock-table input { width: 120px; height: 24px; padding: 0 4px; border: 1px solid #c4c6cf; border-radius: 4px; font: inherit; font-size: 12px; color: #595f6d;; appearance: textfield; }
    .msw-stock-table input:focus { border-color: #1a71ff; outline: 0; box-shadow: 0 0 0 2px rgba(26, 113, 255, .15); }
    .msw-stock-table input::-webkit-inner-spin-button, .msw-stock-table input::-webkit-outer-spin-button { margin: 0; appearance: none; }

    .msw-date { position: fixed; z-index: 2; display: grid; gap: 12px; width: 390px; max-width: calc(100vw - 16px); padding: 12px 12px 16px; border-radius: 12px; background: #fff; box-shadow: rgba(0, 0, 0, .15) 0 2px 12px; }
    .msw-date[hidden], .msw-date [hidden] { display: none !important; }
    .msw-date > b { font-size: 14px; font-weight: 500; line-height: 20px; color: #2e3346; }
    .msw-date-close { top: 12px; right: 12px; width: 16px; height: 16px; }
    .msw-date-select { position: relative; width: 200px; }
    .msw-date-select > button, .msw-date-range > button { display: flex; align-items: center; justify-content: space-between; width: 100%; height: 32px; padding: 0 8px; border: 1px solid #c4c6cf; border-radius: 8px; background: #fff; font: inherit; font-size: 14px; color: #595f6d; cursor: pointer; }
    .msw-date-select > button[aria-expanded="true"] .msw-icon { transform: rotate(180deg); }
    .msw-date-select > button .is-empty { color: #858b9c; }
    .msw-date-options { position: absolute; z-index: 3; top: 36px; left: 0; width: 100%; padding: 4px 0; border: 1px solid #c4c6cf; border-radius: 8px; background: #fff; box-shadow: rgba(0, 0, 0, .1) 0 2px 8px; }
    .msw-date-options button { display: block; width: 100%; height: 32px; padding: 0 20px; border: 0; background: none; font: inherit; text-align: start; color: #595f6d; cursor: pointer; }
    .msw-date-options button:hover { background: #f5f8fd; }
    .msw-date-range { position: relative; }
    .msw-date-range > button { gap: 8px; }
    .msw-date-range > button span { flex: 1; min-width: 0; text-align: start; white-space: nowrap; }
    .msw-date-range > button span.is-empty { color: #858b9c; }
    .msw-date-range > button i { flex: none; font-style: normal; color: #858b9c; }
    .msw-date-foot { display: flex; justify-content: flex-end; gap: 12px; }

    .msw-calendar { position: absolute; z-index: 3; top: 36px; left: 0; width: 287px; max-width: 100%; border: 1px solid #eee; border-radius: 8px; background: #fff; box-shadow: rgba(0, 0, 0, .15) 0 2px 12px; }
    .msw-calendar-head { display: flex; align-items: center; height: 40px; padding: 0 8px; border-bottom: 1px solid #eee; }
    .msw-calendar-head strong { flex: 1; font-size: 14px; font-weight: 700; text-align: center; color: #2e3346; }
    .msw-calendar-head button { display: inline-flex; padding: 2px; border: 0; background: none; color: #595f6d; cursor: pointer; }
    .msw-calendar-head .msw-icon { width: 16px; height: 16px; }
    .msw-calendar-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); padding: 8px; font-size: 14px; text-align: center; }
    .msw-calendar-grid span { line-height: 32px; color: #595f6d; }
    .msw-calendar-grid button { height: 32px; padding: 0; border: 0; border-radius: 8px; background: none; font: inherit; color: #595f6d; cursor: pointer; }
    .msw-calendar-grid button:disabled { color: #c4c6cf; cursor: default; }
    .msw-calendar-grid button.is-range { border-radius: 0; background: #e8f0ff; }
    .msw-calendar-grid button.is-picked { background: #1a71ff; color: #fff; }
    .msw-calendar-foot { display: flex; justify-content: flex-end; padding: 8px; border-top: 1px solid #eee; }
    .msw-calendar-foot .msw-button { height: 24px; padding: 0 8px; font-size: 12px; }
    .msw-calendar-foot .msw-button:disabled { background: #f1f3f7; color: #c4c6cf; cursor: default; opacity: 1; }
    .msw-date-foot .msw-primary:disabled { opacity: .5; cursor: default; }

    .msw-add { display: grid; grid-template-columns: minmax(0, 1fr) 220px; gap: 15px; align-items: start; }
    .msw-add.is-full { grid-template-columns: minmax(0, 1fr); }
    .msw-add-main { min-width: 0; }
    .msw-add .msw-card { margin-top: 16px; }
    .msw-add-card { padding: 20px; }
    .msw-add-card h2 { font-size: 20px; line-height: 24px; color: rgba(0, 0, 0, .85); }
    .msw-add-label { display: block; margin: 12px 0 6px; font-size: 14px; font-weight: 500; line-height: 18px; color: #4f5669; }
    .msw-add-label b, .msw-add-table th b { font-weight: 400; color: #e72b00; }
    .msw-add-drop { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 16px; border-radius: 8px; background: #f5f8fd; }
    .msw-add-drop-half { max-width: 560px; }
    .msw-add-drop small { color: #595f6d; }
    .msw-add-tile, .msw-add-thumb { flex: none; width: 60px; height: 60px; padding: 0; border-radius: 8px; cursor: pointer; }
    .msw-add-tile { border: 1px dashed #858b9c; background: none; font-size: 32px; font-weight: 300; line-height: 1; color: #595f6d; }
    .msw-add-tile[hidden] { display: none; }
    .msw-add-thumb { border: 1px solid #ebecf0; background: #fff center / cover no-repeat; }
    .msw-add-input { display: flex; align-items: center; gap: 8px; width: 100%; min-height: 32px; padding: 0 8px; border: 1px solid #c4c6cf; border-radius: 8px; background: #fff; }
    .msw-add-input:focus-within { border-color: #1a71ff; }
    .msw-add-input input, .msw-add-input select { flex: 1; width: 100%; min-width: 0; height: 30px; padding: 0; border: 0; outline: 0; background: none; color: #2e3346; }
    .msw-add-input small, .msw-add-input > span { flex: none; font-size: 12px; color: #858b9c; }
    .msw-add-invalid { border-color: #e72b00 !important; }
    .msw-add-combo { position: relative; }
    .msw-add-list { position: absolute; z-index: 2; top: 36px; left: 0; right: 0; max-height: 280px; padding: 4px 0; overflow: auto; border: 1px solid #c4c6cf; border-radius: 8px; background: #fff; box-shadow: rgba(0, 0, 0, .1) 0 2px 8px; }
    .msw-add-list[hidden] { display: none; }
    .msw-add-list button { display: block; width: 100%; min-height: 32px; padding: 4px 12px; border: 0; background: none; text-align: start; color: #2e3346; cursor: pointer; }
    .msw-add-list button:hover { background: #f5f8fd; }
    .msw-add-list p { padding: 8px 12px; color: #858b9c; }
    .msw-add-recent { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 8px; margin-top: 6px; font-size: 12px; color: #4f5669; }
    .msw-add-recent[hidden] { display: none; }
    .msw-add-recent button { padding: 2px 9px; border: 0; border-radius: 10px; background: #f5f8fd; font-size: 12px; color: #2e3346; cursor: pointer; }
    .msw-add-title { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 12px; }
    .msw-add-title > b { padding: 0 6px; border-radius: 8px; background: #e72b00; font-weight: 500; line-height: 16px; color: #fff; }
    .msw-add-title > b.is-done { background: #1baf9d; }
    .msw-add-bar { display: block; width: 150px; height: 4px; border-radius: 2px; background: #dfe7fb; }
    .msw-add-bar i { display: block; height: 100%; border-radius: 2px; background: #1a71ff; }
    .msw-add-note { margin-top: 6px; font-size: 12px; color: #858b9c; }
    .msw-add-spec { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 16px; }
    .msw-add-tablewrap { border: 1px solid #eee; border-radius: 16px; }
    .msw-add-table { width: 100%; border-spacing: 0; table-layout: fixed; }
    .msw-add-table th { padding: 10px 12px; border-bottom: 1px solid #eee; background: #f5f8fd; font-weight: 700; text-align: center; color: #4f5669; }
    .msw-add-table th:first-child { border-top-left-radius: 16px; }
    .msw-add-table th:last-child { width: 100px; border-top-right-radius: 16px; }
    .msw-add-table th[hidden], .msw-add-table td[hidden] { display: none; }
    .msw-add-table td { padding: 12px; text-align: center; }
    .msw-add-tablewrap[hidden] { display: none; }
    [data-msw-price-card].is-variants [data-msw-simple] { display: none; }
    .msw-var-add { display: inline-block; padding: 8px 16px; border: 1px dashed #858b9c; border-radius: 8px; background: #fff; color: #1a71ff; font: inherit; cursor: pointer; }
    .msw-var-add[hidden], #msw-variations[hidden], #msw-var-list[hidden] { display: none; }
    .msw-var-box { position: relative; margin-bottom: 16px; padding: 12px 16px 16px; border-radius: 8px; background: #f5f5f5; }
    .msw-var-box .msw-add-label:first-of-type { margin-top: 0; }
    .msw-var-close { position: absolute; top: 12px; right: 12px; width: 28px; height: 28px; padding: 4px; border: 0; background: none; color: #595f6d; cursor: pointer; }
    .msw-var-close svg, .msw-var-tool svg { width: 16px; height: 16px; }
    .msw-var-name { max-width: 460px; }
    .msw-var-options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 24px; }
    .msw-var-option { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; min-width: 0; }
    .msw-var-fields { display: grid; flex: 1 1 0; grid-template-columns: repeat(2, minmax(0, 1fr)); min-width: 0; border: 1px solid #c4c6cf; border-radius: 8px; background: #fff; overflow: hidden; }
    .msw-var-fields input { width: 100%; min-width: 0; height: 32px; padding: 0 8px; border: 0; outline: 0; background: none; color: #2e3346; }
    .msw-var-fields input + input { border-left: 1px solid #ebecf0; }
    .msw-var-tool { flex: none; width: 24px; height: 24px; padding: 2px; border: 0; background: none; color: #858b9c; line-height: 1; cursor: grab; }
    .msw-var-tool + .msw-var-tool { cursor: pointer; }
    .msw-var-tool:disabled { color: #c4c6cf; cursor: default; }
    .msw-var-hint { display: block; flex-basis: 100%; margin-top: 2px; font-size: 12px; color: #e72b00; }
    .msw-var-all { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
    .msw-var-all-fields { display: grid; flex: 1; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; min-width: 0; }
    .msw-var-tablewrap { margin-bottom: 16px; border: 1px solid #eee; border-radius: 8px; overflow-x: auto; }
    .msw-var-table { width: 100%; min-width: 640px; border-spacing: 0; }
    .msw-var-table th { padding: 10px 12px; border-bottom: 1px solid #eee; background: #f5f8fd; font-weight: 700; text-align: center; color: #4f5669; }
    .msw-var-table th b { font-weight: 400; color: #e72b00; }
    .msw-var-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: center; }
    .msw-var-table tr:last-child td { border-bottom: 0; }
    .msw-var-first { width: 110px; border-right: 1px solid #eee; }
    .msw-var-second { width: 110px; border-right: 1px solid #eee; }
    .msw-var-table .is-linked { box-shadow: inset 0 0 0 1px #e72b00; }
    .msw-var-special-info { display: grid; gap: 4px; min-width: 0; }
    .msw-var-special-info span:first-child { color: #2e3346; font-size: 13px; }
    .msw-var-special-dates { display: block; margin-bottom: 2px; color: #e02020; font-size: 11px; line-height: 16px; white-space: nowrap; }
    .msw-var-table .msw-batch-link.msw-add-invalid { color: #e72b00; text-decoration: underline; }
    .msw-var-image { display: block; width: 60px; height: 60px; margin: 6px auto 0; padding: 0; border: 1px dashed #858b9c; border-radius: 8px; background: #fff center / cover no-repeat; font-size: 24px; font-weight: 300; color: #595f6d; cursor: pointer; }
    .msw-var-image.has-image { border-style: solid; border-color: #ebecf0; }
    .msw-add-flags { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 16px; }
    .msw-add-flags span:not(.msw-switch) { margin-right: 16px; }
    .msw-add-editor { border: 1px solid #eee; border-radius: 8px; }
    .msw-add-tools { display: flex; gap: 4px; padding: 6px 8px; border-bottom: 1px solid #eee; }
    .msw-add-tools button { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; padding: 0; border: 0; border-radius: 4px; background: none; color: #4f5669; cursor: pointer; }
    .msw-add-tools button:hover { background: #f2f3f7; }
    #msw-description { min-height: 240px; padding: 12px 20px; outline: 0; color: #2e3346; overflow-wrap: anywhere; }
    #msw-description.is-empty::before { content: attr(data-placeholder); color: #858b9c; }
    .msw-add-tools button:disabled { opacity: .4; cursor: progress; }
    #msw-description img { display: block; max-width: 100%; height: auto; margin: 8px 0; border-radius: 4px; cursor: pointer; }
    .msw-add-more { display: inline-flex; align-items: center; gap: 4px; margin-top: 12px; padding: 0; border: 0; background: none; color: #1a71ff; cursor: pointer; }
    .msw-add-more[aria-expanded="true"] .msw-icon { transform: rotate(180deg); }
    .msw-add-textarea { display: block; width: 100%; padding: 8px; border: 1px solid #c4c6cf; border-radius: 8px; outline: 0; font: inherit; color: #2e3346; resize: vertical; }
    .msw-add-weight { max-width: 300px; }
    .msw-add-foot { position: sticky; bottom: 0; z-index: 1; display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 16px 20px; }
    .msw-add-foot p { display: flex; flex: 1; align-items: center; gap: 8px; min-width: 240px; font-size: 12px; color: #4f5669; }
    .msw-add-foot p[hidden], .msw-add-foot button[hidden] { display: none; }
    .msw-add-foot .msw-icon { color: #f5a623; }
    .msw-add-foot small { color: #e72b00; font-size: 12px; }
    .msw-add-foot > div { display: flex; gap: 12px; margin-left: auto; }
    .msw-add-foot .msw-button:disabled { opacity: .5; cursor: default; }
    .msw-add-side { padding: 20px; }
    .msw-add-side[hidden] { display: none; }
    .msw-add-side h2 { color: #1a71ff; font-size: 18px; }
    .msw-add-side p { margin-top: 10px; line-height: 21px; color: #595f6d; }
    .msw-modal.msw-modal-special { width: 480px; }
    .msw-modal-special form, .msw-add-special { display: grid; gap: 12px; }
    .msw-add-special label { display: grid; gap: 4px; font-weight: 500; }
    .msw-add-special input { width: 100%; }
    [data-msw-message] { display: block; font-size: 12px; }
    [data-msw-message].is-error { color: #e72b00; }

    body {
        --msw-edge: rgba(17, 24, 39, .06);
        --msw-shine: inset 0 1px 0 rgba(255, 255, 255, .95);
        --msw-raise: var(--msw-shine), 0 0 0 1px var(--msw-edge), 0 1px 2px rgba(17, 24, 39, .04), 0 8px 16px -6px rgba(30, 41, 90, .08), 0 28px 48px -20px rgba(30, 41, 90, .24);
        --msw-raise-sm: var(--msw-shine), 0 0 0 1px var(--msw-edge), 0 1px 2px rgba(17, 24, 39, .05), 0 4px 10px -4px rgba(30, 41, 90, .12), 0 10px 20px -12px rgba(30, 41, 90, .18);
        --msw-raise-hover: var(--msw-shine), 0 0 0 1px rgba(26, 113, 255, .14), 0 2px 4px rgba(17, 24, 39, .05), 0 12px 24px -10px rgba(30, 41, 90, .22);
    }
    body { background: radial-gradient(1200px 600px at 70% -10%, #f8faff 0%, rgba(248, 250, 255, 0) 60%), linear-gradient(180deg, #eef1f6 0%, #f3f4f8 45%, #eceef4 100%) fixed; }
    .msw-card, .msw-order-head, .msw-order-row, .msw-table, [data-msw-page="fees"] .msw-card { border: 0; background: linear-gradient(180deg, #ffffff 0%, #fbfcfe 100%); box-shadow: var(--msw-raise); }
    .msw-table th { background: linear-gradient(180deg, #fbfcfe 0%, #f5f7fb 100%); box-shadow: inset 0 -1px 0 var(--msw-edge); }
    .msw-order-row { transition: transform .2s ease, box-shadow .2s ease; }
    .msw-order-row:hover { transform: translateY(-1px); box-shadow: var(--msw-raise-hover); }
    .msw-tile, .msw-tile-big, .msw-task, .msw-set, .msw-subtabs a, .msw-outline { border: 0; background: linear-gradient(180deg, #ffffff 0%, #f2f5fb 100%); box-shadow: var(--msw-raise-sm); }
    .msw-tile strong, .msw-task b { font-variant-numeric: tabular-nums; letter-spacing: -.01em; }
    .msw-tile-big strong { background: linear-gradient(180deg, #2f7dff 0%, #1257d6 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
    a.msw-tile, a.msw-task, a.msw-set, .msw-subtabs a { transition: transform .2s ease, box-shadow .2s ease; }
    a.msw-tile:hover, a.msw-task:hover, a.msw-set:hover, .msw-subtabs a:hover { transform: translateY(-2px); box-shadow: var(--msw-raise-hover); }
    a.msw-tile:active, a.msw-task:active, a.msw-set:active, .msw-subtabs a:active { transform: translateY(0); box-shadow: var(--msw-raise-sm); }
    .msw-set > span, .msw-avatar { background: linear-gradient(180deg, #ffffff 0%, #eef3fc 100%); box-shadow: var(--msw-raise-sm); }
    .msw-button { box-shadow: 0 1px 2px rgba(17, 24, 39, .06), inset 0 1px 0 rgba(255, 255, 255, .9); transition: transform .15s ease, box-shadow .15s ease, background-color .15s ease; }
    .msw-button:hover:not(:disabled) { box-shadow: 0 4px 10px -4px rgba(26, 113, 255, .35), inset 0 1px 0 rgba(255, 255, 255, .9); }
    .msw-primary, [data-msw-page="fees"] .msw-primary { background: linear-gradient(180deg, #3a87ff 0%, #1a6af2 55%, #155fdf 100%); box-shadow: inset 0 1px 0 rgba(255, 255, 255, .28), 0 1px 2px rgba(21, 95, 223, .35), 0 6px 14px -6px rgba(21, 95, 223, .55); }
    .msw-primary:hover:not(:disabled) { box-shadow: inset 0 1px 0 rgba(255, 255, 255, .3), 0 2px 4px rgba(21, 95, 223, .35), 0 10px 20px -8px rgba(21, 95, 223, .6); }
    .msw-button:active:not(:disabled) { transform: translateY(1px); }
    .msw-chip { border-color: transparent; background: linear-gradient(180deg, #ffffff 0%, #eef1f6 100%); box-shadow: 0 0 0 1px var(--msw-edge), 0 1px 2px rgba(17, 24, 39, .05); }
    .msw-field, .msw-money, .msw-alert-input { box-shadow: inset 0 1px 2px rgba(17, 24, 39, .06); }
    .msw-tabs a.is-active::after { background: linear-gradient(90deg, #3a87ff, #155fdf); box-shadow: 0 2px 8px rgba(26, 113, 255, .45); }
    .msw-switch { box-shadow: inset 0 1px 3px rgba(17, 24, 39, .18); }
    .msw-switch::after { box-shadow: 0 1px 3px rgba(17, 24, 39, .25), 0 0 0 .5px rgba(17, 24, 39, .06); }
    .msw-product img, .msw-product i, .msw-product-info img, .msw-product-info i { box-shadow: 0 1px 2px rgba(17, 24, 39, .06), 0 4px 10px -6px rgba(30, 41, 90, .25); }
    .msw-more-menu, .msw-date, .msw-date-options, .msw-calendar { border-color: rgba(17, 24, 39, .06); box-shadow: 0 0 0 1px var(--msw-edge), 0 8px 16px -6px rgba(30, 41, 90, .16), 0 28px 48px -20px rgba(30, 41, 90, .28); }
    .msw-modal { border: 0; box-shadow: 0 0 0 1px rgba(17, 24, 39, .06), 0 24px 48px -12px rgba(17, 24, 39, .28), 0 60px 100px -40px rgba(17, 24, 39, .35); }
    .msw-modal::backdrop { background: rgba(15, 23, 42, .38); backdrop-filter: blur(3px); }
    .msw-note { box-shadow: inset 0 1px 0 rgba(255, 255, 255, .7), 0 0 0 1px rgba(26, 113, 255, .08); }
    @media (prefers-reduced-motion: reduce) {
        .msw-main * { transition: none !important; transform: none !important; }
    }

    @media (max-width: 1023px) {
        .msw-main { min-height: 0; margin: 0; padding: 20px 16px 24px; }
        .msw-hero-title { position: static; margin: 0 0 16px !important; font-size: 28px !important; }
        .msw-top, .msw-grid-2, .msw-grid-3, .msw-grid-4 { grid-template-columns: minmax(0, 1fr); }
        .msw-bars { height: 140px; gap: 1px; }
        .msw-tasks { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .msw-task-groups { grid-template-columns: minmax(0, 1fr); gap: 24px; margin: 24px 0 0; }
        .msw-return { grid-template-columns: minmax(0, 1fr); }
        .msw-order-head { display: none; }
        .msw-order-row { grid-template-columns: minmax(0, 1fr); gap: 8px; padding: 16px; }
        .msw-empty { padding-bottom: 48px; font-size: 24px; line-height: 30px; }
        .msw-product-table { overflow-x: auto; }
        .msw-add, .msw-add-spec { grid-template-columns: minmax(0, 1fr); }
        .msw-add-input { min-height: 44px; }
        .msw-add-input input, .msw-add-input select { height: 42px; font-size: 16px; }
        .msw-add-tile, .msw-add-thumb { width: 72px; height: 72px; }
        .msw-add-list button { min-height: 44px; }
        .msw-add-recent button { min-height: 32px; }
        .msw-add-table, .msw-add-table tbody, .msw-add-table tr, .msw-add-table td { display: block; width: 100%; }
        .msw-add-table thead { display: none; }
        .msw-add-table td { padding: 8px 12px; text-align: start; }
        .msw-add-table td::before { content: attr(data-label); display: block; margin-bottom: 4px; font-weight: 700; }
        .msw-var-options, .msw-var-fields { grid-template-columns: minmax(0, 1fr); }
        .msw-var-fields input + input { border-top: 1px solid #ebecf0; border-left: 0; }
        .msw-var-name { margin-top: 14px; }
        .msw-var-all { flex-direction: column; align-items: stretch; }
        .msw-var-all-fields { grid-template-columns: minmax(0, 1fr); }
        .msw-var-fields input { height: 42px; font-size: 16px; }
        .msw-var-tool, .msw-var-close { width: 44px; height: 44px; padding: 12px; }
        .msw-var-close { top: 0; right: 0; }
        .msw-var-add { min-height: 44px; }
        .msw-add-tools button { width: 44px; height: 44px; }
        .msw-add-textarea, #msw-description { font-size: 16px; }
        .msw-add-foot { position: static; }
        .msw-add-foot > div { width: 100%; }
        .msw-add-foot .msw-button { flex: 1; }
        .msw-pencil { width: 44px; height: 44px; }
        .msw-more > button { min-height: 44px; }
        .msw-more-menu button { min-height: 44px; }
        .msw-modal-body { padding: 8px 16px; }
        .msw-modal-table, .msw-modal-table > tbody, .msw-modal-table > tbody > tr, .msw-modal-table > tbody > tr > td { display: block; width: 100%; }
        .msw-modal-table > thead { display: none; }
        .msw-modal-table > tbody > tr > td[data-label]::before { content: attr(data-label); display: block; margin-bottom: 4px; font-weight: 700; }
        .msw-stock-cell { padding: 0 0 12px !important; }
        .msw-stock-table, .msw-stock-table thead, .msw-stock-table tbody { display: block; }
        .msw-stock-table tr { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); align-items: center; gap: 4px 8px; padding: 8px; }
        .msw-stock-table thead tr { border-radius: 16px; background: #f1f3f7; }
        .msw-stock-table th, .msw-stock-table th:first-child, .msw-stock-table th:nth-child(2), .msw-stock-table th:nth-child(3), .msw-stock-table th:nth-child(4) { width: auto; height: auto; padding: 0; background: none; }
        .msw-stock-table th:empty, .msw-stock-table td:empty { display: none; }
        .msw-stock-table td { height: auto; padding: 0; }
        .msw-batch-date { width: 100%; height: 44px; font-size: 14px; }
        .msw-batch-link { min-height: 44px; }
        .msw-alert-input { height: 44px; font-size: 16px; }
        .msw-modal-intro { font-size: 14px; }
        .msw-money { flex: 1; width: auto; min-width: 0; height: 44px; }
        .msw-date { max-height: calc(100dvh - 16px); overflow: auto; }
        .msw-money input, .msw-stock-table input { font-size: 16px; }
        .msw-stock-table input { width: 100%; height: 44px; }
        .msw-clock, .msw-copy, .msw-modal-close, .msw-date-close { align-items: center; justify-content: center; min-width: 44px; min-height: 44px; }
        .msw-modal-close, .msw-date-close { top: 4px; right: 4px; }
        .msw-date-select { width: 100%; }
        .msw-date-select > button, .msw-date-range > button, .msw-date-options button { height: 44px; }
        .msw-calendar { position: static; width: 100%; margin-top: 4px; box-shadow: none; }
        .msw-calendar-grid button { height: 40px; }
        .msw-calendar-head button { min-width: 36px; min-height: 36px; align-items: center; justify-content: center; }
        .msw-calendar-foot .msw-button { height: 44px; padding: 0 16px; }
        .msw-field { flex-basis: 100%; max-width: none; }
        .msw-field input, .msw-field select { font-size: 16px; }
        .msw-button, .msw-chip, .msw-pager a, .msw-pager strong { min-height: 44px; }
    }
</style>
