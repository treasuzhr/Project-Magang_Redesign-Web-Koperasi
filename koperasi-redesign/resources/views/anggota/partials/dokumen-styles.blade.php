@once
    <style>
        .member-documents .loan-info-box {
            padding: 14px 18px;
            border: 1px solid #d5e6bf;
            border-radius: 16px;
            background: #edf7dd;
            color: #214b34;
            font-size: .92rem;
            font-weight: 700;
        }

        .member-documents-table {
            table-layout: fixed;
        }

        .member-documents-table th:nth-child(1),
        .member-documents-table td[data-column="id"] {
            width: 9%;
        }

        .member-documents-table th:nth-child(2) {
            width: 61%;
        }

        .member-documents-table th:nth-child(3) {
            width: 18%;
        }

        .member-documents-table th:nth-child(4) {
            width: 12%;
        }

        .member-documents-table td[data-column="title"] {
            color: #25382d;
            font-weight: 600;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .member-documents-table td[data-column="year"] {
            font-variant-numeric: tabular-nums;
        }

        .member-documents .member-table-action--document {
            min-height: 44px;
            border-color: #b7d19c;
            color: #173d2b;
            transition: background-color .15s ease, border-color .15s ease;
        }

        .member-documents .member-table-action--document:hover,
        .member-documents .member-table-action--document:focus-visible {
            border-color: #9abb74;
            background: #edf7dd;
        }

        .member-documents .member-table-action--document svg {
            width: 18px;
            height: 18px;
            flex-basis: 18px;
        }

        .member-documents .member-table-action--document:focus-visible {
            outline: 3px solid #6a9632;
            outline-offset: 3px;
        }

        @media (max-width: 1023px) {
            .member-documents .member-form-table-wrap {
                overflow: visible;
            }

            .member-documents .member-documents-table,
            .member-documents .member-documents-table tbody {
                display: block;
            }

            .member-documents .member-documents-table thead {
                display: none;
            }

            .member-documents .member-documents-table tbody tr[data-table-row] {
                display: grid;
                grid-template-areas:
                    "title title"
                    "year action";
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 8px 16px;
                margin-bottom: 12px;
                padding: 12px 14px;
                border: 1px solid #e8eee6;
                border-radius: 18px;
                background: #fff;
            }

            .member-documents .member-documents-table tbody tr[data-table-row]:last-child {
                margin-bottom: 0;
            }

            .member-documents .member-documents-table tbody tr[data-table-row] td {
                display: flex;
                min-width: 0;
                min-height: 44px;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                padding: 8px 2px;
                border-bottom: 0;
                font-size: .86rem;
                text-align: left !important;
            }

            .member-documents .member-documents-table tbody tr[data-table-row] td::before {
                color: #52635a;
                content: attr(data-label);
                font-size: .72rem;
                font-weight: 700;
                text-transform: uppercase;
            }

            .member-documents .member-documents-table tbody tr[data-table-row] td[data-column="id"] {
                display: none;
            }

            .member-documents .member-documents-table tbody tr[data-table-row] td[data-column="title"] {
                grid-area: title;
                display: block;
                padding-bottom: 10px;
                border-bottom: 1px solid #edf1eb;
                font-size: .9rem;
                overflow-wrap: anywhere;
            }

            .member-documents .member-documents-table tbody tr[data-table-row] td[data-column="title"]::before {
                content: none;
            }

            .member-documents .member-documents-table tbody tr[data-table-row] td[data-column="year"] {
                grid-area: year;
                justify-content: flex-start;
                gap: 8px;
            }

            .member-documents .member-documents-table tbody tr[data-table-row] td[data-column="action"] {
                grid-area: action;
                justify-content: flex-end;
            }

            .member-documents .member-documents-table .member-table-action--document {
                min-width: 44px;
                min-height: 44px;
            }

            .member-documents .member-documents-table tbody tr[data-table-empty] {
                display: table-row;
                margin: 0;
                padding: 0;
                border: 0;
                background: transparent;
            }

            .member-documents .member-documents-table tbody tr[data-table-empty] td {
                display: table-cell;
                min-height: 110px;
                text-align: center !important;
            }

            .member-documents .member-documents-table tbody tr[data-table-empty] td::before {
                content: none;
            }
        }

        @media (max-width: 420px) {
            .member-documents .member-documents-table tbody tr[data-table-row] {
                grid-template-areas:
                    "title"
                    "year"
                    "action";
                grid-template-columns: minmax(0, 1fr);
            }

            .member-documents .member-documents-table tbody tr[data-table-row] td[data-column="action"] {
                justify-content: stretch;
            }

            .member-documents .member-documents-table .member-table-action--document {
                width: 100%;
            }
        }
    </style>
@endonce
