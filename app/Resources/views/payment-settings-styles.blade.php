@pushOnce('styles')
    <style>
        .mp-payment-settings { margin-top: 20px; color: var(--lux-cream); }
        .mp-payment-intro { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; color: var(--lux-cream-soft); line-height: 1.6; }
        .mp-payment-policy { flex-shrink: 0; border: 1px solid var(--lux-gold-faint); border-radius: 20px; padding: 5px 12px; color: var(--lux-gold-light); font-size: 12px; }
        .mp-payment-layout { display: grid; grid-template-columns: minmax(0, 1fr) 250px; gap: 20px; align-items: start; }
        .mp-payment-methods { display: grid; gap: 16px; min-width: 0; }
        .mp-payment-card { overflow: hidden; border: 1px solid var(--lux-gold-faint); border-radius: 12px; background: var(--lux-well); }
        .mp-payment-card[open] { border-color: var(--lux-gold-line); }
        .mp-payment-card summary { display: flex; align-items: center; gap: 14px; padding: 20px; cursor: pointer; list-style: none; min-height: 88px; }
        .mp-payment-card summary::-webkit-details-marker { display: none; }
        .mp-payment-card summary:focus-visible { outline: 2px solid var(--lux-gold-light); outline-offset: -4px; border-radius: 12px; }
        .mp-payment-icon { display: grid; place-items: center; width: 44px; height: 44px; flex-shrink: 0; border-radius: 10px; background: var(--lux-gold-wash); color: var(--lux-gold-light); font-size: 24px; }
        .mp-payment-heading { display: grid; gap: 5px; min-width: 0; flex: 1; }
        .mp-payment-title { font-size: 16px; font-weight: 600; line-height: 1.4; color: var(--lux-cream); }
        .mp-payment-subtitle { font-size: 12px; line-height: 1.5; color: var(--lux-cream-soft); }
        .mp-payment-status { border-radius: 20px; padding: 4px 9px; font-size: 11px; white-space: nowrap; background: rgba(255,255,255,.06); color: var(--lux-cream-soft); }
        .mp-payment-status-on { display: none; color: var(--lux-up); background: rgba(143,224,168,.1); }
        .mp-payment-card:has(input[type="checkbox"][name$="[active]"]:checked) .mp-payment-status-on { display: inline; }
        .mp-payment-card:has(input[type="checkbox"][name$="[active]"]:checked) .mp-payment-status-off { display: none; }
        .mp-payment-chevron { flex-shrink: 0; color: var(--lux-gold); transition: transform .15s; }
        .mp-payment-card[open] .mp-payment-chevron { transform: rotate(180deg); }
        .mp-payment-body { padding: 0 24px 24px; }
        .mp-payment-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 20px; }
        .mp-payment-field { min-width: 0; }
        .mp-payment-field-wide { grid-column: 1 / -1; }
        .mp-payment-field > div { margin-bottom: 0; }
        .mp-payment-field:not(:has(input:not([type="hidden"]), textarea, select)) { display: none; }
        .mp-payment-group:not(:has(input:not([type="hidden"]), textarea, select)) { display: none; }
        .mp-payment-options { padding: 16px; border-radius: 8px; background: var(--lux-gold-wash); }
        .mp-payment-group { padding-top: 24px; margin-top: 24px; border-top: 1px solid var(--lux-gold-faint); }
        .mp-payment-group h2 { margin-bottom: 16px; color: var(--lux-gold-light); font-size: 13px; font-weight: 600; letter-spacing: .02em; }
        .mp-payment-receiving-help { margin: -4px 0 20px; font-size: 12px; line-height: 1.6; color: var(--lux-cream-soft); }
        .mp-payment-settings input:not([type="checkbox"]):not([type="hidden"]), .mp-payment-settings select { min-height: 44px; max-width: 100%; }
        .mp-payment-settings textarea { min-height: 92px; }
        .mp-payment-settings .mp-config-toggle { min-height: 44px; min-width: 44px; }
        .mp-payment-settings .mp-config-toggle:has(input:focus-visible) { outline: 2px solid var(--lux-gold-light); outline-offset: 2px; border-radius: 5px; }
        .mp-payment-settings .mp-config-image { justify-content: flex-start; gap: 16px; }
        .mp-payment-settings .mp-config-image a { flex-shrink: 0; }
        .mp-payment-settings .mp-config-image img { width: 48px; height: 48px; object-fit: contain; margin: 0; background: white; border-radius: 6px; }
        .mp-payment-settings [data-payment-field="qr_image"] .mp-config-image img { width: 144px; height: 144px; }
        .mp-payment-settings input[type="file"] { min-width: 0; font-size: 12px; }
        .mp-payment-settings input[type="file"]::file-selector-button { min-height: 36px; cursor: pointer; }
        .mp-payment-settings .mp-payment-field p { font-style: normal; color: var(--lux-cream-soft); }
        .mp-payment-guide { padding: 22px; border: 1px solid var(--lux-gold-faint); border-radius: 12px; background: linear-gradient(145deg, var(--lux-navy-900), var(--lux-navy-950)); }
        .mp-payment-guide h2 { color: var(--lux-gold-light); font-size: 15px; font-weight: 600; }
        .mp-payment-guide ol { display: grid; gap: 20px; counter-reset: payment-step; margin-top: 24px; }
        .mp-payment-guide li { position: relative; padding-inline-start: 32px; font-size: 13px; line-height: 1.6; color: var(--lux-cream-soft); }
        .mp-payment-guide li::before { counter-increment: payment-step; content: counter(payment-step, decimal-leading-zero); position: absolute; inset-inline-start: 0; color: var(--lux-gold); font-size: 12px; }
        .mp-payment-guide-note { display: grid; gap: 10px; margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--lux-gold-faint); color: var(--lux-cream-soft); font-size: 12px; line-height: 1.7; }
        .mp-payment-guide-note > span { color: var(--lux-gold); font-size: 22px; }
        .mp-payment-footer a, .mp-payment-footer button { display: inline-flex; align-items: center; min-height: 44px; }
        @media (max-width: 1200px) { .mp-payment-layout { grid-template-columns: minmax(0, 1fr); } .mp-payment-guide ol { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 600px) {
            .mp-payment-intro { align-items: flex-start; flex-direction: column; gap: 10px; }
            .mp-payment-card summary { gap: 10px; padding: 16px; flex-wrap: wrap; }
            .mp-payment-icon { width: 36px; height: 36px; font-size: 20px; }
            .mp-payment-heading { flex-basis: calc(100% - 74px); }
            .mp-payment-status { margin-inline-start: 46px; }
            .mp-payment-chevron { margin-inline-start: auto; }
            .mp-payment-body { padding: 0 16px 20px; }
            .mp-payment-grid { grid-template-columns: minmax(0, 1fr); gap: 16px; }
            .mp-payment-settings [data-payment-field="qr_image"] .mp-config-image { flex-direction: column; align-items: flex-start; }
            .mp-payment-guide ol { grid-template-columns: minmax(0, 1fr); }
        }
    </style>
@endPushOnce
