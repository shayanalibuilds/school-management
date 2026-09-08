{{-- Shared panel CSS, injected by both panel providers before Filament's
     own styles. The hand-built page markup (ledgers, settings cards,
     attendance buttons, hints) declares no colours of its own: every
     surface and text colour lives here and carries an explicit dark-mode
     counterpart, so the custom markup follows Filament's theme switch. --}}
<style>
    /* Sidebar accordions: indent the Active / Inactive
       children so the parent relationship is visible. */
    .fi-sidebar-sub-group-items {
        margin-left: 1.125rem;
        padding-left: 0.75rem;
        border-left: 2px solid color-mix(in oklab, currentColor 14%, transparent);
    }

    /* Ledger canvas: the bordered card a custom table scrolls inside. */
    .ledger-card {
        overflow-x: auto;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        background-color: #ffffff;
    }
    .dark .ledger-card {
        border-color: #374151;
        background-color: #111827;
    }

    /* Ledger table: header band and rows mirror Filament's
       gray scale in both themes. */
    .ledger-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .ledger-table thead {
        background-color: #f9fafb;
    }
    .dark .ledger-table thead {
        background-color: #030712;
    }
    .ledger-table thead th {
        padding: 0.625rem 1rem;
        text-align: start;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        color: #6b7280;
    }
    .dark .ledger-table thead th {
        color: #9ca3af;
    }
    .ledger-table tbody tr {
        border-top: 1px solid #e5e7eb;
        color: #111827;
    }
    .dark .ledger-table tbody tr {
        border-color: #374151;
        color: #f3f4f6;
    }
    .ledger-table tbody td {
        padding: 0.625rem 1rem;
    }

    /* Text roles used outside tables too. */
    .ledger-muted { color: #6b7280; }
    .dark .ledger-muted { color: #9ca3af; }
    .ledger-strong { color: #111827; }
    .dark .ledger-strong { color: #f3f4f6; }
    .ledger-label { color: #374151; }
    .dark .ledger-label { color: #d1d5db; }
    .ledger-success { color: #16a34a; }
    .dark .ledger-success { color: #4ade80; }

    /* Attendance status picker: neutral pills that tint primary when
       pressed. Red for a missed day, yellow for sanctioned leave — the
       pressed fills are solid, so they read the same in both themes. */
    .att-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        padding: 0.3rem 0.85rem;
        font-size: 0.8125rem;
        font-weight: 500;
        line-height: 1.25rem;
        cursor: pointer;
        background-color: #ffffff;
        color: #4b5563;
        transition: background-color 150ms ease, border-color 150ms ease, color 150ms ease;
    }
    .dark .att-btn {
        border-color: #4b5563;
        background-color: #111827;
        color: #d1d5db;
    }
    .att-btn:hover {
        border-color: var(--primary-500, #3b82f6);
        color: var(--primary-600, #2563eb);
    }
    .att-btn:focus-visible {
        outline: 2px solid var(--primary-500, #3b82f6);
        outline-offset: 2px;
    }
    .att-btn[aria-pressed="true"] {
        background-color: var(--primary-600, #2563eb);
        border-color: var(--primary-600, #2563eb);
        color: #ffffff;
    }
    .att-btn[aria-pressed="true"]:hover { background-color: var(--primary-500, #3b82f6); }
    .att-btn[data-status="absent"][aria-pressed="true"] {
        background-color: #dc2626;
        border-color: #dc2626;
        color: #ffffff;
    }
    .att-btn[data-status="absent"][aria-pressed="true"]:hover { background-color: #ef4444; }
    .att-btn[data-status="leave"][aria-pressed="true"] {
        background-color: #eab308;
        border-color: #ca8a04;
        color: #422006;
    }
    .att-btn[data-status="leave"][aria-pressed="true"]:hover { background-color: #facc15; }
</style>
