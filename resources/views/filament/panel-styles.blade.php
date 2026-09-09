{{-- Shared panel CSS, injected by both panel providers before Filament's
     own styles. The hand-built page markup (ledgers, settings cards,
     attendance buttons, hints) declares no colours of its own: every
     surface and text colour resolves from Filament's palette CSS
     variables (literal hexes only as fallbacks), so the custom markup
     always sits on the exact same surfaces as the panel around it — in
     light mode and, once Filament toggles its dark class, in dark mode
     too. --}}
<style>
    /* Sidebar accordions: indent the Active / Inactive
       children so the parent relationship is visible. */
    .fi-sidebar-sub-group-items {
        margin-left: 1.125rem;
        padding-left: 0.75rem;
        border-left: 2px solid color-mix(in oklab, currentColor 14%, transparent);
    }

    /* Ledger canvas: the bordered card a custom table scrolls inside.
       Both themes resolve from Filament's own gray tokens, so the card
       mirrors the section it lives in — white on white in light mode,
       gray-900 on gray-900 in dark mode — and only the border
       delineates it, exactly like Filament's own cards. */
    .ledger-card {
        overflow-x: auto;
        border: 1px solid var(--gray-200, #e5e7eb);
        border-radius: 0.75rem;
        background-color: #ffffff;
    }
    .dark .ledger-card {
        border-color: var(--gray-700, #374151);
        background-color: var(--gray-900, #111827);
    }

    /* Ledger table: header band and rows mirror Filament's
       gray scale in both themes. */
    .ledger-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .ledger-table thead {
        background-color: var(--gray-50, #f9fafb);
    }
    .dark .ledger-table thead {
        background-color: var(--gray-950, #030712);
    }
    .ledger-table thead th {
        padding: 0.625rem 1rem;
        text-align: start;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        color: var(--gray-500, #6b7280);
    }
    .dark .ledger-table thead th {
        color: var(--gray-400, #9ca3af);
    }
    .ledger-table tbody tr {
        border-top: 1px solid var(--gray-200, #e5e7eb);
        color: var(--gray-900, #111827);
    }
    .dark .ledger-table tbody tr {
        border-color: var(--gray-700, #374151);
        color: var(--gray-100, #f3f4f6);
    }
    .ledger-table tbody td {
        padding: 0.625rem 1rem;
    }

    /* Text roles used outside tables too. */
    .ledger-muted { color: var(--gray-500, #6b7280); }
    .dark .ledger-muted { color: var(--gray-400, #9ca3af); }
    .ledger-strong { color: var(--gray-900, #111827); }
    .dark .ledger-strong { color: var(--gray-100, #f3f4f6); }
    .ledger-label { color: var(--gray-700, #374151); }
    .dark .ledger-label { color: var(--gray-300, #d1d5db); }
    .ledger-success { color: var(--success-600, #16a34a); }
    .dark .ledger-success { color: var(--success-400, #4ade80); }

    /* Attendance status picker: neutral pills that tint primary when
       pressed. Red for a missed day, yellow for sanctioned leave — the
       pressed fills are solid, so they read the same in both themes. */
    .att-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        border: 1px solid var(--gray-300, #d1d5db);
        padding: 0.3rem 0.85rem;
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1.25rem;
        cursor: pointer;
        background-color: #ffffff;
        color: var(--gray-600, #4b5563);
        transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease;
    }
    .dark .att-btn {
        border-color: var(--gray-600, #4b5563);
        background-color: var(--gray-900, #111827);
        color: var(--gray-300, #d1d5db);
    }
    .att-btn:hover {
        border-color: var(--primary-500, #23e7ba);
        color: var(--primary-600, #0f7861);
    }
    .att-btn:focus-visible {
        outline: 2px solid var(--primary-500, #23e7ba);
        outline-offset: 2px;
    }
    .att-btn[aria-pressed="true"] {
        background-color: var(--primary-600, #0f7861);
        border-color: var(--primary-600, #0f7861);
        color: #ffffff;
    }
    .att-btn[aria-pressed="true"]:hover { background-color: var(--primary-500, #23e7ba); }
    .att-btn[data-status="absent"][aria-pressed="true"] {
        background-color: var(--danger-600, #dc2626);
        border-color: var(--danger-600, #dc2626);
        color: #ffffff;
    }
    .att-btn[data-status="absent"][aria-pressed="true"]:hover { background-color: var(--danger-500, #ef4444); }
    .att-btn[data-status="leave"][aria-pressed="true"] {
        background-color: var(--warning-500, #eab308);
        border-color: var(--warning-600, #ca8a04);
        color: #422006;
    }
    .att-btn[data-status="leave"][aria-pressed="true"]:hover { background-color: var(--warning-400, #facc15); }
</style>
