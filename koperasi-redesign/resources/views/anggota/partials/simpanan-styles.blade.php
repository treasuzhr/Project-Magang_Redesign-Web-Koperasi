@once
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;700&family=Figtree:wght@400;500;600;700&display=swap');

        .member-portal {
            overflow-x: clip;
            padding: 112px 0 80px;
            background: #f3f6ee;
            color: #15241c;
            font-family: 'Figtree', sans-serif;
        }

        .member-portal .page-width {
            width: min(100% - 32px, 1360px);
            margin-inline: auto;
        }

        .member-shell {
            display: grid;
            grid-template-columns: minmax(0, 290px) minmax(0, 1fr);
            align-items: start;
            gap: 32px;
        }

        .member-main {
            display: grid;
            min-width: 0;
            gap: 24px;
        }

        .member-page-header {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 20px;
        }

        .member-page-header__eyebrow {
            margin: 0 0 8px;
            color: #527d28;
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .member-page-header h1 {
            margin: 0;
            color: #15241c;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: clamp(2.15rem, 3vw, 3rem);
            font-weight: 700;
            letter-spacing: -.06em;
            line-height: 1.1;
        }

        .member-page-header h1 span {
            color: #6a9632;
        }

        .member-page-header__subtitle {
            margin: 10px 0 0;
            color: #52635a;
            font-size: 1rem;
        }

        .member-page-header__actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .member-back-button {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 16px;
            border: 1px solid rgba(14, 59, 43, .14);
            border-radius: 999px;
            background: #fff;
            color: #173d2b;
            font-size: .9rem;
            font-weight: 700;
            text-decoration: none;
            transition: transform .2s ease;
        }

        .member-back-button:hover,
        .member-back-button:focus-visible {
            border-color: #b7d19c;
            background: #edf7dd;
        }

        .member-back-button svg {
            display: block;
            width: 18px;
            height: 18px;
            flex: 0 0 18px;
            color: currentColor;
            stroke: currentColor;
        }

        .member-tabs {
            width: 100%;
            overflow: hidden;
            padding: 6px;
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 12px 26px rgba(21, 36, 28, .06);
        }

        .member-tabs__scroll {
            overflow-x: auto;
            overscroll-behavior-x: contain;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .member-tabs__scroll::-webkit-scrollbar {
            display: none;
        }

        .member-tabs__track {
            position: relative;
            display: flex;
            width: max-content;
            min-width: 100%;
            align-items: center;
            gap: 4px;
        }

        .member-tabs__indicator {
            position: absolute;
            z-index: 0;
            top: 0;
            bottom: 0;
            left: 0;
            width: 0;
            border-radius: 999px;
            background: #0e3b2b;
            pointer-events: none;
            transition: transform .3s ease;
        }

        .member-tab {
            position: relative;
            z-index: 1;
            display: inline-flex;
            min-height: 44px;
            flex: 1 0 auto;
            align-items: center;
            justify-content: center;
            padding: 10px 17px;
            border-radius: 999px;
            color: #25382d;
            font-size: .91rem;
            font-weight: 700;
            text-decoration: none;
        }

        .member-tab:hover,
        .member-tab:focus-visible {
            background: #edf7dd;
        }

        .member-tab.is-active {
            background: #0e3b2b;
            color: #fff;
        }

        .member-tabs__scroll.is-enhanced .member-tab.is-active {
            background: transparent;
        }

        .member-tab:focus-visible,
        .member-back-button:focus-visible,
        .member-table-action:focus-visible,
        .member-table-controls select:focus-visible,
        .member-pagination__button:focus-visible,
        .member-pagination__page:focus-visible {
            outline: 3px solid #6a9632;
            outline-offset: 3px;
        }

        .member-table-card {
            min-width: 0;
            padding: 22px;
            border-radius: 30px;
            background: #fff;
            box-shadow: 0 14px 28px rgba(21, 36, 28, .06);
        }

        .member-table-card__title {
            margin: 0 0 16px;
            color: #15241c;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -.03em;
        }

        .member-table-controls {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .member-table-controls--top {
            margin-bottom: 16px;
            color: #3e5146;
            font-size: .9rem;
            font-weight: 600;
        }

        .member-table-controls--top label {
            display: inline-flex;
            min-height: 40px;
            align-items: center;
            gap: 8px;
        }

        .member-table-controls select {
            min-width: 68px;
            min-height: 40px;
            padding: 6px 26px 6px 10px;
            border: 1px solid #d5dfd3;
            border-radius: 10px;
            background: #fff;
            color: #20372a;
            font: inherit;
        }

        .member-table-wrap {
            width: 100%;
        }

        .member-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
            color: #25382d;
            font-size: .94rem;
        }

        .member-table th {
            padding: 14px 16px;
            background: #edf7dd;
            color: #173d2b;
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .11em;
            text-align: left;
            text-transform: uppercase;
        }

        .member-table th:first-child {
            border-radius: 14px 0 0;
        }

        .member-table th:last-child {
            border-radius: 0 14px 0 0;
        }

        .member-table td {
            padding: 15px 16px;
            border-bottom: 1px solid #e8eee6;
            overflow-wrap: anywhere;
            text-align: left;
        }

        .member-table tbody tr:not([data-table-empty]):hover td {
            background: #f7faf4;
        }

        .member-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .member-table__center {
            text-align: center !important;
        }

        .member-table__right,
        .member-table__amount {
            text-align: right !important;
            font-variant-numeric: tabular-nums;
        }

        .member-table__amount {
            color: #15241c;
            font-weight: 700;
            white-space: nowrap;
        }

        .member-table-action {
            display: inline-flex;
            min-height: 36px;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 6px 12px;
            border: 1px solid #d7e3d2;
            border-radius: 999px;
            background: #fff;
            color: #214b34;
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .member-table-action:hover {
            background: #edf7dd;
        }

        .member-table-action svg {
            display: block;
            width: 16px;
            height: 16px;
            flex: 0 0 16px;
            color: currentColor;
            stroke: currentColor;
        }

        .member-table [data-table-empty] td {
            height: 110px;
            color: #52635a;
            text-align: center;
        }

        .member-table-info {
            margin: 0;
            color: #527d28;
            font-size: .84rem;
            font-weight: 700;
        }

        .member-pagination {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 6px;
        }

        .member-pagination[hidden] {
            display: none;
        }

        .member-pagination__button,
        .member-pagination__page {
            display: inline-flex;
            min-width: 40px;
            min-height: 40px;
            align-items: center;
            justify-content: center;
            padding: 7px 10px;
            border: 1px solid transparent;
            border-radius: 999px;
            background: #fff;
            color: #254234;
            font: inherit;
            font-size: .82rem;
            font-weight: 700;
            cursor: pointer;
        }

        .member-pagination__button:hover:not(:disabled),
        .member-pagination__page:hover:not(.is-current) {
            background: #edf7dd;
        }

        .member-pagination__button:disabled {
            color: #8d9990;
            cursor: not-allowed;
        }

        .member-pagination__page.is-current {
            background: #7fad39;
            color: #fff;
        }

        .member-pagination__pages {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .member-sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        @media (max-width: 1023px) {
            .member-shell {
                grid-template-columns: 1fr;
            }

            .member-page-header {
                align-items: flex-start;
            }
        }

        @media (max-width: 640px) {
            .member-portal {
                padding-top: 84px;
            }

            .member-page-header {
                display: grid;
                gap: 14px;
            }

            .member-page-header__actions {
                justify-content: flex-start;
            }

            .member-tabs {
                padding: 5px;
            }

            .member-tabs__track {
                min-width: max-content;
            }

            .member-tab {
                min-height: 44px;
                padding-inline: 15px;
            }

            .member-table-card {
                padding: 14px 10px;
                border-radius: 22px;
            }

            .member-table {
                font-size: .75rem;
            }

            .member-table th {
                padding: 11px 6px;
                font-size: .63rem;
                letter-spacing: .06em;
            }

            .member-table td {
                padding: 12px 6px;
                font-size: .74rem;
            }

            .member-table-action {
                min-width: 40px;
                min-height: 44px;
                padding: 8px;
            }

            .member-table-action span {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }

            .member-table-controls--bottom {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }

            .member-pagination {
                width: 100%;
                justify-content: space-between;
                gap: 2px;
            }

            .member-pagination__button,
            .member-pagination__page {
                min-width: 44px;
                min-height: 44px;
                padding-inline: 7px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .member-back-button,
            .member-tab,
            .member-tabs__indicator {
                transition: none;
            }
        }
    </style>
@endonce
