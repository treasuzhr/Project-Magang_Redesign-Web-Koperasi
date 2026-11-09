@include('anggota.partials.simpanan-styles')

@once
    <style>
        .loan-action-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            align-items: stretch;
            gap: 20px;
        }

        .loan-action-card,
        .loan-panel {
            min-width: 0;
            padding: 28px;
            border-radius: 30px;
            box-shadow: 0 14px 28px rgba(21, 36, 28, .06);
        }

        .loan-action-card {
            display: flex;
            min-height: 250px;
            flex-direction: column;
            align-items: flex-start;
            justify-content: space-between;
            gap: 26px;
            color: #15241c;
            text-decoration: none;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .loan-action-card:hover,
        .loan-action-card:focus-visible {
            transform: translateY(-3px);
            box-shadow: 0 18px 34px rgba(21, 36, 28, .1);
        }

        .loan-action-card--primary {
            background: #0e3b2b;
            color: #fff;
        }

        .loan-action-card--secondary {
            background: #fff;
        }

        .loan-action-card__icon {
            display: grid;
            width: 44px;
            height: 44px;
            place-items: center;
            border-radius: 14px;
            background: rgba(255, 255, 255, .13);
            color: currentColor;
        }

        .loan-action-card--secondary .loan-action-card__icon {
            background: #edf7dd;
            color: #173d2b;
        }

        .loan-action-card__icon svg,
        .loan-action-card__link svg,
        .loan-primary-button svg,
        .loan-simulation-row svg {
            display: block;
            width: 20px;
            height: 20px;
            flex: 0 0 20px;
            color: currentColor;
            stroke: currentColor;
        }

        .loan-action-card__title {
            margin: 18px 0 7px;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: -.035em;
        }

        .loan-action-card__description {
            max-width: 34ch;
            margin: 0;
            color: #52635a;
            line-height: 1.55;
        }

        .loan-action-card--primary .loan-action-card__description {
            color: rgba(255, 255, 255, .8);
        }

        .loan-action-card__link {
            display: inline-flex;
            min-height: 46px;
            align-items: center;
            gap: 9px;
            padding: 10px 18px;
            border-radius: 999px;
            background: #7fad39;
            color: #fff;
            font-size: .9rem;
            font-weight: 800;
        }

        .loan-action-card--secondary .loan-action-card__link {
            border: 1px solid #d5dfd3;
            background: #fff;
            color: #173d2b;
        }

        .loan-panel {
            background: #fff;
        }

        .member-portal .loan-panel {
            width: 100%;
            justify-self: stretch;
        }

        .loan-panel__title {
            margin: 0 0 18px;
            color: #15241c;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: -.03em;
        }

        .loan-info-box {
            box-sizing: border-box;
            width: 100%;
            padding: 18px 20px;
            border: 1px solid #dce9ce;
            border-radius: 18px;
            background: #edf7dd;
            color: #214b34;
            font-size: .95rem;
            font-weight: 600;
            line-height: 1.5;
        }

        .loan-panel--active {
            padding-inline: 20px;
        }

        .loan-form-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(290px, .85fr);
            align-items: start;
            gap: 24px 32px;
        }

        .loan-form-card {
            display: grid;
            gap: 22px;
        }

        .loan-form-card__fields {
            display: grid;
            gap: 18px;
        }

        .loan-field {
            min-width: 0;
        }

        .loan-field__label {
            display: block;
            margin-bottom: 8px;
            color: #25382d;
            font-size: .9rem;
            font-weight: 700;
        }

        .loan-field__input-wrap {
            position: relative;
            display: flex;
            min-height: 50px;
            align-items: center;
        }

        .loan-field__control {
            width: 100%;
            min-width: 0;
            min-height: 50px;
            padding: 12px 14px;
            border: 1px solid #cfdacd;
            border-radius: 14px;
            outline: none;
            background-color: #fff;
            color: #15241c;
            font: inherit;
            font-size: .94rem;
            transition: border-color .12s ease, box-shadow .12s ease;
        }

        .loan-field__control::placeholder {
            color: #819087;
            opacity: 1;
        }

        .loan-field__control:focus {
            border-color: #6a9632;
            box-shadow: 0 0 0 3px rgba(106, 150, 50, .2);
        }

        .loan-field__control.is-invalid {
            border-color: #b42318;
        }

        .loan-field__input-wrap.has-prefix .loan-field__control {
            padding-left: 48px;
        }

        .loan-field__input-wrap.has-suffix .loan-field__control {
            padding-right: 68px;
        }

        .loan-field__affix {
            position: absolute;
            z-index: 1;
            color: #52635a;
            font-size: .9rem;
            font-weight: 700;
            pointer-events: none;
        }

        .loan-field__affix--prefix {
            left: 15px;
        }

        .loan-field__affix--suffix {
            right: 15px;
        }

        .loan-field__select {
            appearance: none;
            padding-right: 42px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%2352635a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-position: right 13px center;
            background-repeat: no-repeat;
        }

        .loan-field__help,
        .loan-field__error {
            margin: 7px 0 0;
            font-size: .8rem;
            line-height: 1.4;
        }

        .loan-field__help {
            min-height: 1.1em;
            color: #52635a;
        }

        .loan-field__error {
            color: #b42318;
            font-weight: 600;
        }

        .loan-form-summary {
            grid-column: 1 / -1;
        }

        .loan-simulation-card {
            display: grid;
            gap: 10px;
            padding: 26px;
            border-radius: 26px;
            background: #0e3b2b;
            color: #fff;
        }

        .loan-simulation-card__title {
            margin: 0 0 7px;
            color: #fff;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .loan-simulation-row {
            display: flex;
            min-height: 50px;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, .15);
            color: rgba(255, 255, 255, .83);
            font-size: .9rem;
        }

        .loan-simulation-row strong {
            color: #fff;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .loan-simulation-row--emphasis {
            margin-top: 5px;
            padding: 14px 12px;
            border: 0;
            border-radius: 14px;
            background: #7fad39;
            color: #fff;
        }

        .loan-simulation-row--emphasis strong {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 1.15rem;
        }

        .loan-simulation-card__note {
            margin: 7px 0 0;
            color: rgba(255, 255, 255, .76);
            font-size: .79rem;
            line-height: 1.5;
        }

        .loan-form-submit {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
        }

        .loan-primary-button {
            display: inline-flex;
            min-width: 190px;
            min-height: 50px;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 12px 22px;
            border: 0;
            border-radius: 999px;
            background: #7fad39;
            color: #fff;
            font: inherit;
            font-size: .94rem;
            font-weight: 800;
            cursor: pointer;
            transition: background-color .12s ease, box-shadow .12s ease;
        }

        .loan-primary-button:hover,
        .loan-primary-button:focus-visible {
            background: #6b8f31;
        }

        .loan-primary-button:focus-visible {
            outline: 3px solid #0e3b2b;
            outline-offset: 3px;
        }

        .loan-primary-button:disabled {
            cursor: wait;
            opacity: .7;
        }

        .member-badge {
            display: inline-flex;
            min-height: 30px;
            align-items: center;
            padding: 5px 11px;
            border-radius: 999px;
            background: #edf7dd;
            color: #214b34;
            font-size: .8rem;
            font-weight: 700;
        }

        .member-table--stack-mobile {
            width: 100%;
        }

        .member-table--stack-mobile .member-table__action {
            text-align: right !important;
        }

        .member-table--stack-mobile .member-table-action {
            min-height: 40px;
        }

        @media (max-width: 1023px) {
            .member-form-table-wrap {
                overflow: visible;
            }

            .member-table--stack-mobile,
            .member-table--stack-mobile tbody {
                display: block;
            }

            .member-table--stack-mobile thead {
                display: none;
            }

            .member-table--stack-mobile tbody tr[data-table-row] {
                display: grid;
                margin-bottom: 12px;
                padding: 6px 12px;
                border: 1px solid #e8eee6;
                border-radius: 18px;
                background: #fff;
            }

            .member-table--stack-mobile tbody tr[data-table-row]:last-child {
                margin-bottom: 0;
            }

            .member-table--stack-mobile tbody tr[data-table-row] td {
                display: flex;
                min-width: 0;
                min-height: 44px;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                padding: 9px 2px;
                border-bottom: 1px solid #edf1eb;
                text-align: right !important;
            }

            .member-table--stack-mobile tbody tr[data-table-row] td:last-child {
                border-bottom: 0;
            }

            .member-table--stack-mobile tbody tr[data-table-row] td::before {
                flex: 0 1 auto;
                color: #52635a;
                content: attr(data-label);
                font-size: .73rem;
                font-weight: 700;
                text-align: left;
                text-transform: uppercase;
            }

            .member-table--stack-mobile .member-table__amount {
                white-space: normal;
            }

            .member-table--stack-mobile .member-table-action {
                min-width: 44px;
                min-height: 44px;
            }

            .member-table--stack-mobile .member-table-action span {
                position: static;
                width: auto;
                height: auto;
                margin: 0;
                overflow: visible;
                clip: auto;
                white-space: normal;
            }

            .member-table--stack-mobile tbody tr[data-table-empty] {
                display: table-row;
                margin: 0;
                padding: 0;
                border: 0;
                background: transparent;
            }

            .member-table--stack-mobile tbody tr[data-table-empty] td {
                display: table-cell;
                min-height: 110px;
                text-align: center !important;
            }

            .member-table--stack-mobile tbody tr[data-table-empty] td::before {
                content: none;
            }
        }

        @media (max-width: 640px) {
            .loan-action-grid,
            .loan-form-grid {
                grid-template-columns: 1fr;
            }

            .loan-action-card,
            .loan-panel {
                padding: 22px;
                border-radius: 24px;
            }

            .loan-panel--active {
                padding-inline: 16px;
            }

            .loan-action-card {
                min-height: 220px;
            }

            .loan-form-card,
            .loan-simulation-card {
                padding: 20px;
                border-radius: 22px;
            }

            .loan-form-summary,
            .loan-form-submit {
                grid-column: auto;
            }

            .loan-form-submit .loan-primary-button {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .loan-action-card,
            .loan-field__control,
            .loan-primary-button {
                transition: none;
            }
        }
    </style>
@endonce
