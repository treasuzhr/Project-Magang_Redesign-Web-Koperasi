@include('anggota.partials.pinjaman-styles')

@once
    <style>
        .member-password__form {
            width: 100%;
            display: grid;
            gap: 18px;
        }

        .member-password__card {
            gap: 20px;
        }

        html.js .member-password__card[data-reveal] {
            opacity: 0;
            transform: translateY(12px);
            transition: opacity .55s ease, transform .55s ease;
        }

        html.js .member-password__card[data-reveal].is-visible {
            opacity: 1;
            transform: none;
        }

        .member-password__title {
            display: flex;
            align-items: center;
            gap: 9px;
        }

        .member-password__title svg,
        .member-password__icon,
        .member-password__toggle svg,
        .member-password__submit svg {
            display: block;
            width: 20px;
            height: 20px;
            flex: 0 0 20px;
            color: currentColor;
            stroke: currentColor;
        }

        .member-password .loan-field__input-wrap,
        .member-password .loan-field__control {
            min-height: 48px;
        }

        .member-password .loan-field__control {
            padding-left: 46px;
            padding-right: 56px;
        }

        .member-password .loan-field__input-wrap.has-prefix .loan-field__control {
            padding-left: 46px;
        }

        .member-password .loan-field__input-wrap.has-suffix .loan-field__control {
            padding-right: 56px;
        }

        .member-password .loan-field__affix--prefix {
            left: 14px;
            display: flex;
            align-items: center;
        }

        .member-password .loan-field__affix--prefix svg {
            width: 18px;
            height: 18px;
        }

        .member-password__suffix-control {
            position: absolute;
            z-index: 2;
            top: 2px;
            right: 2px;
            display: grid;
            width: 44px;
            height: 44px;
            place-items: center;
        }

        .member-password__toggle {
            display: grid;
            width: 44px;
            height: 44px;
            place-items: center;
            padding: 0;
            border: 0;
            border-radius: 12px;
            background: transparent;
            color: #52635a;
            cursor: pointer;
            transition: color .12s ease, background-color .12s ease;
        }

        .member-password__toggle:hover,
        .member-password__toggle:focus-visible {
            background: #edf7dd;
            color: #173d2b;
        }

        .member-password__toggle:focus-visible {
            outline: 2px solid #6a9632;
            outline-offset: 1px;
        }

        .member-password__summary {
            border-color: #f2c5c2;
            background: #fef3f2;
            color: #912018;
        }

        .member-password__submit {
            gap: 8px;
        }

        .member-password__cancel {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            color: #214b34;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        .member-password__actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 14px;
        }

        @media (max-width: 640px) {
            .member-password__actions {
                flex-direction: column-reverse;
                align-items: stretch;
                gap: 6px;
            }

            .member-password__actions .loan-primary-button {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html.js .member-password__card[data-reveal] {
                opacity: 1;
                transform: none;
                transition: none;
            }

            .member-password__toggle {
                transition: none;
            }
        }
    </style>
@endonce
