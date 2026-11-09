@once
    <style>
        .member-orders .member-orders-table {
            table-layout: fixed;
        }

        .member-orders .member-orders-table th:nth-child(1),
        .member-orders .member-orders-table td[data-column="id"] {
            width: 9%;
        }

        .member-orders .member-orders-table th:nth-child(2) {
            width: 27%;
        }

        .member-orders .member-orders-table th:nth-child(3) {
            width: 20%;
        }

        .member-orders .member-orders-table th:nth-child(4) {
            width: 24%;
        }

        .member-orders .member-orders-table th:nth-child(5) {
            width: 20%;
        }

        .member-orders .member-orders-table td[data-column="date"] {
            white-space: nowrap;
        }

        .member-orders .member-orders-table td[data-column="item_count"] {
            font-variant-numeric: tabular-nums;
        }

        .member-orders .member-orders-table td[data-column="total"] {
            font-weight: 700;
        }

        .member-orders .member-orders-table .member-table-action {
            min-height: 44px;
        }

        .member-orders .member-orders-table .member-table-action svg {
            width: 18px;
            height: 18px;
            flex-basis: 18px;
        }

        .member-orders .member-table-controls--top select {
            min-height: 44px;
        }

        .member-order-detail > .member-back-button {
            justify-self: start;
        }

        .member-order-items {
            width: 100%;
            border-collapse: collapse;
            color: #25382d;
            font-size: .94rem;
        }

        .member-order-items th {
            padding: 14px 16px;
            border-bottom: 1px solid #dfe8dc;
            color: #52635a;
            font-size: .74rem;
            font-weight: 800;
            letter-spacing: .11em;
            text-align: left;
            text-transform: uppercase;
        }

        .member-order-items td {
            padding: 16px;
            border-bottom: 1px solid #e8eee6;
            font-variant-numeric: tabular-nums;
        }

        .member-order-items tbody tr:last-child td {
            border-bottom: 0;
        }

        .member-order-items tbody tr:hover td {
            background: #f7faf4;
        }

        .member-order-items__product {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 14px;
            font-variant-numeric: normal !important;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .member-order-items__thumbnail {
            display: grid;
            width: 60px;
            height: 60px;
            flex: 0 0 60px;
            place-items: center;
            border-radius: 16px;
            background: #edf7dd;
            color: #214b34;
        }

        .member-order-items__thumbnail svg {
            display: block;
            width: 22px;
            height: 22px;
        }

        .member-order-items__total {
            color: #15241c;
            font-weight: 800;
            white-space: nowrap;
        }

        .member-order-empty {
            display: grid;
            min-height: 180px;
            place-items: center;
            color: #52635a;
            text-align: center;
        }

        .member-order-empty p {
            margin: 0;
        }

        @media (max-width: 1023px) {
            .member-orders .member-pagination__button,
            .member-orders .member-pagination__page {
                min-height: 44px;
            }

            .member-orders .member-form-table-wrap {
                overflow: visible;
            }

            .member-orders .member-orders-table,
            .member-orders .member-orders-table tbody {
                display: block;
            }

            .member-orders .member-orders-table thead {
                display: none;
            }

            .member-orders .member-orders-table tbody tr[data-table-row] {
                display: grid;
                grid-template-areas:
                    "date total"
                    "quantity quantity"
                    "action action";
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 0 14px;
                margin-bottom: 12px;
                padding: 10px 14px;
                border: 1px solid #e8eee6;
                border-radius: 18px;
                background: #fff;
            }

            .member-orders .member-orders-table tbody tr[data-table-row]:last-child {
                margin-bottom: 0;
            }

            .member-orders .member-orders-table tbody tr[data-table-row] td {
                display: flex;
                min-width: 0;
                min-height: 44px;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                padding: 9px 2px;
                border-bottom: 1px solid #edf1eb;
                font-size: .84rem;
            }

            .member-orders .member-orders-table tbody tr[data-table-row] td::before {
                color: #52635a;
                content: attr(data-label);
                font-size: .72rem;
                font-weight: 700;
                text-align: left;
                text-transform: uppercase;
            }

            .member-orders .member-orders-table tbody tr[data-table-row] td[data-column="id"] {
                display: none;
            }

            .member-orders .member-orders-table tbody tr[data-table-row] td[data-column="date"] {
                grid-area: date;
                white-space: normal;
            }

            .member-orders .member-orders-table tbody tr[data-table-row] td[data-column="total"] {
                grid-area: total;
                justify-content: flex-end;
                gap: 8px;
                border-bottom: 1px solid #edf1eb;
                color: #15241c;
                font-weight: 800;
                white-space: nowrap;
            }

            .member-orders .member-orders-table tbody tr[data-table-row] td[data-column="item_count"] {
                grid-area: quantity;
                justify-content: space-between;
            }

            .member-orders .member-orders-table tbody tr[data-table-row] td[data-column="action"] {
                grid-area: action;
                justify-content: flex-end;
                border-bottom: 0;
            }

            .member-orders .member-orders-table tbody tr[data-table-empty] {
                display: table-row;
                margin: 0;
                padding: 0;
                border: 0;
                background: transparent;
            }

            .member-orders .member-orders-table tbody tr[data-table-empty] td {
                display: table-cell;
                min-height: 110px;
                text-align: center;
            }

            .member-orders .member-orders-table tbody tr[data-table-empty] td::before {
                content: none;
            }

            .member-order-items,
            .member-order-items tbody {
                display: block;
            }

            .member-order-items thead {
                display: none;
            }

            .member-order-items tbody tr {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
                padding: 16px;
            }

            .member-order-items tbody tr td {
                min-width: 0;
                padding: 0;
                border: 0;
                text-align: center !important;
            }

            .member-order-items tbody tr td::before {
                display: block;
                margin-bottom: 6px;
                color: #52635a;
                content: attr(data-label);
                font-size: .7rem;
                font-weight: 700;
                letter-spacing: .03em;
            }

            .member-order-items tbody tr td.member-order-items__product {
                grid-column: 1 / -1;
                justify-content: flex-start;
                gap: 12px;
                padding-bottom: 14px;
                border-bottom: 1px solid #e8eee6;
                text-align: left !important;
            }

            .member-order-items tbody tr td.member-order-items__product::before {
                content: none;
            }

            .member-order-items__thumbnail {
                width: 56px;
                height: 56px;
                flex-basis: 56px;
                border-radius: 14px;
            }

            .member-order-items tbody tr td.member-order-items__total {
                white-space: normal;
            }
        }

        @media (max-width: 420px) {
            .member-orders .member-orders-table tbody tr[data-table-row] {
                grid-template-columns: minmax(0, 1fr);
                grid-template-areas:
                    "date"
                    "total"
                    "quantity"
                    "action";
            }

            .member-orders .member-orders-table tbody tr[data-table-row] td[data-column="total"] {
                justify-content: space-between;
            }
        }

        @media (max-width: 360px) {
            .member-order-items tbody tr {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .member-order-items tbody tr td.member-order-items__total {
                grid-column: 1 / -1;
            }
        }
    </style>
@endonce
