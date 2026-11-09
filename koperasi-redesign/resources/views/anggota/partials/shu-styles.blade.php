@once
    <style>
        .shu-page {
            min-width: 0;
        }

        .shu-summary {
            display: grid;
            grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr);
            align-items: center;
            gap: 30px;
            min-width: 0;
            padding: 28px 32px;
            overflow: visible;
            border-radius: 30px;
            background: #edf7dd;
        }

        .shu-summary__content {
            min-width: 0;
        }

        .shu-summary__label {
            display: block;
            margin-bottom: 8px;
            color: #214b34;
            font-size: .9rem;
            font-weight: 700;
        }

        .shu-summary__amount {
            display: block;
            color: #15241c;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: clamp(2.2rem, 4vw, 3.25rem);
            font-variant-numeric: tabular-nums;
            font-weight: 700;
            letter-spacing: -.055em;
            line-height: 1.1;
            white-space: nowrap;
        }

        .shu-summary__link {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            margin-top: 20px;
            padding: 10px 18px;
            border-radius: 999px;
            background: var(--brand, #7fad39);
            color: #15241c;
            font-size: .9rem;
            font-weight: 800;
            text-decoration: none;
            transition: transform .15s ease;
        }

        .shu-summary__link:hover {
            transform: translateY(-2px);
            background: #91bb50;
        }

        .shu-summary__link:focus-visible,
        .shu-summary .shu-chart__bar-button:focus-visible {
            outline: 3px solid #173d2b;
            outline-offset: 3px;
        }

        .shu-summary .shu-chart {
            display: grid;
            width: 100%;
            height: 220px;
            grid-template-columns: repeat(5, minmax(44px, 1fr));
            align-items: stretch;
            gap: 8px;
            overflow: visible;
            border: 0;
            padding: 0;
        }

        .shu-summary .shu-chart__bar-button {
            position: relative;
            display: flex;
            min-width: 0;
            min-height: 44px;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-end;
            padding: 0 3px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #52635a;
            font: inherit;
            cursor: pointer;
        }

        .shu-summary .shu-chart span {
            width: auto;
            height: auto;
            border: 0;
            border-radius: 0;
            background: transparent;
            cursor: inherit;
            filter: none;
            outline-offset: 0;
            transform: none;
            transform-origin: initial;
        }

        .shu-summary .shu-chart__bar-track {
            position: relative;
            display: flex;
            height: 184px;
            min-height: 44px;
            flex: 0 0 184px;
            align-items: flex-end;
            justify-content: center;
            overflow: visible;
        }

        .shu-summary .shu-chart .shu-chart__bar {
            display: block;
            width: min(68%, 42px);
            height: var(--bar-height);
            border-radius: 10px 10px 4px 4px;
            background: rgba(127, 173, 57, .48);
            cursor: inherit;
            filter: none;
            transform: scaleY(1);
            transform-origin: bottom;
        }

        .shu-summary .shu-chart .shu-chart__bar.is-current {
            background: var(--brand, #7fad39);
        }

        .shu-summary .shu-chart__year {
            display: flex;
            min-height: 28px;
            align-items: flex-end;
            justify-content: center;
            color: #52635a;
            font-size: .78rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .shu-data-table .member-table-action svg {
            width: 18px;
            height: 18px;
            flex-basis: 18px;
        }

        .shu-summary .shu-chart__tooltip {
            position: absolute;
            z-index: 3;
            bottom: calc(var(--bar-height) + 8px);
            left: 50%;
            width: max-content;
            max-width: min(170px, 78vw);
            padding: 7px 9px;
            border-radius: 9px;
            background: #173d2b;
            color: #fff;
            font-size: .75rem;
            font-weight: 700;
            line-height: 1.3;
            opacity: 0;
            pointer-events: none;
            transform: translate(-50%, 5px);
            white-space: nowrap;
        }

        .shu-summary .shu-chart__bar-button:first-child .shu-chart__tooltip {
            left: 0;
            transform: translate(0, 5px);
        }

        .shu-summary .shu-chart__bar-button:last-child .shu-chart__tooltip {
            right: 0;
            left: auto;
            transform: translate(0, 5px);
        }

        .shu-summary .shu-chart__bar-button:hover .shu-chart__tooltip,
        .shu-summary .shu-chart__bar-button:focus .shu-chart__tooltip,
        .shu-summary .shu-chart__bar-button.is-active .shu-chart__tooltip {
            opacity: 1;
            transform: translate(-50%, 0);
        }

        .shu-summary .shu-chart__bar-button:first-child:hover .shu-chart__tooltip,
        .shu-summary .shu-chart__bar-button:first-child:focus .shu-chart__tooltip,
        .shu-summary .shu-chart__bar-button:first-child.is-active .shu-chart__tooltip,
        .shu-summary .shu-chart__bar-button:last-child:hover .shu-chart__tooltip,
        .shu-summary .shu-chart__bar-button:last-child:focus .shu-chart__tooltip,
        .shu-summary .shu-chart__bar-button:last-child.is-active .shu-chart__tooltip {
            transform: translate(0, 0);
        }

        .shu-summary .shu-chart__bar-button:hover .shu-chart__bar,
        .shu-summary .shu-chart__bar-button:focus .shu-chart__bar,
        .shu-summary .shu-chart__bar-button.is-active .shu-chart__bar {
            background: #173d2b;
            filter: none;
            transform: scaleY(1);
        }

        .shu-summary .shu-chart span::after {
            content: none;
        }

        .shu-detail-table {
            font-variant-numeric: tabular-nums;
        }

        .shu-detail-table tbody td:nth-child(2) {
            font-weight: 700;
        }

        .shu-detail-table tfoot th,
        .shu-detail-table tfoot td {
            padding: 15px 16px;
            background: #173d2b;
            color: #fff;
            font-weight: 800;
        }

        .shu-detail-table tfoot th {
            border-radius: 0 0 0 14px;
            text-align: left;
            letter-spacing: 0;
            text-transform: none;
        }

        .shu-detail-table tfoot td:last-child {
            border-radius: 0 0 14px;
        }

        .shu-detail-table tfoot .member-table__amount {
            color: #fff;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 1.18rem;
        }

        @media (prefers-reduced-motion: no-preference) {
            html.js .shu-summary .shu-chart__bar {
                transition: transform .7s cubic-bezier(.2, .8, .2, 1);
                transition-delay: var(--bar-delay);
                transform: scaleY(0);
            }

            html.js .shu-summary .shu-chart.is-visible .shu-chart__bar {
                transform: scaleY(1);
            }

            .shu-summary .shu-chart__tooltip {
                transition: opacity .15s ease, transform .15s ease;
            }

            html.js .shu-summary .shu-chart span::after {
                content: none;
            }
        }

        @media (max-width: 1023px) {
            .shu-data-table .member-table--stack-mobile tbody tr[data-table-row] {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .shu-data-table .member-table--stack-mobile tbody tr[data-table-row] td[data-column="amount"],
            .shu-data-table .member-table--stack-mobile tbody tr[data-table-row] td[data-column="year"] {
                min-height: 70px;
                flex-direction: column;
                align-items: flex-start;
                justify-content: center;
                gap: 5px;
                text-align: left !important;
            }

            .shu-data-table .member-table--stack-mobile tbody tr[data-table-row] td[data-column="amount"] {
                order: -2;
            }

            .shu-data-table .member-table--stack-mobile tbody tr[data-table-row] td[data-column="year"] {
                align-items: flex-end;
                order: -1;
            }

            .shu-data-table .member-table--stack-mobile tbody tr[data-table-row] td[data-column="amount"]::before,
            .shu-data-table .member-table--stack-mobile tbody tr[data-table-row] td[data-column="year"]::before {
                font-size: .68rem;
            }

            .shu-data-table .member-table--stack-mobile tbody tr[data-table-row] td[data-column="name"],
            .shu-data-table .member-table--stack-mobile tbody tr[data-table-row] td[data-column="nip"],
            .shu-data-table .member-table--stack-mobile tbody tr[data-table-row] td[data-column="number"],
            .shu-data-table .member-table--stack-mobile tbody tr[data-table-row] td[data-column="action"] {
                grid-column: 1 / -1;
            }

            .shu-data-table .member-table--stack-mobile tbody tr[data-table-row] td[data-column="amount"] .member-table__amount {
                color: #173d2b;
                font-size: 1rem;
            }
        }

        @media (max-width: 640px) {
            .shu-summary {
                grid-template-columns: minmax(0, 1fr);
                gap: 24px;
                padding: 22px 18px;
                border-radius: 22px;
            }

            .shu-summary__amount {
                font-size: clamp(2.1rem, 10vw, 2.7rem);
            }

            .shu-summary__link {
                margin-top: 14px;
            }

            .shu-summary .shu-chart {
                height: 180px;
                gap: 4px;
            }

            .shu-summary .shu-chart__bar-track {
                height: 140px;
                min-height: 44px;
                flex-basis: 140px;
            }

            .shu-summary .shu-chart__year {
                font-size: .72rem;
            }

            .shu-detail-table {
                font-size: .68rem;
            }

            .shu-detail-table th,
            .shu-detail-table td {
                padding: 10px 4px;
                font-size: .68rem;
                letter-spacing: 0;
                overflow-wrap: normal;
                white-space: nowrap;
            }

            .shu-detail-table th:nth-child(1),
            .shu-detail-table td:nth-child(1) {
                width: 9%;
            }

            .shu-detail-table th:nth-child(2),
            .shu-detail-table td:nth-child(2) {
                width: 37%;
                white-space: normal;
            }

            .shu-detail-table th:nth-child(3),
            .shu-detail-table td:nth-child(3),
            .shu-detail-table th:nth-child(4),
            .shu-detail-table td:nth-child(4) {
                width: 27%;
            }

            .shu-detail-table tfoot th,
            .shu-detail-table tfoot td {
                padding: 12px 4px;
            }

            .shu-detail-table tfoot .member-table__amount {
                font-size: .91rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .shu-summary__link,
            .shu-summary .shu-chart__bar,
            .shu-summary .shu-chart__tooltip {
                transition: none;
            }
        }
    </style>
@endonce
