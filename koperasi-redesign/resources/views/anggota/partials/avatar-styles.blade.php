@include('anggota.partials.pinjaman-styles')

@once
    <style>
        .member-avatar-page__cards {
            display: grid;
            grid-template-columns: minmax(260px, .38fr) minmax(0, .62fr);
            align-items: stretch;
            gap: 20px;
        }

        .member-avatar-card {
            min-width: 0;
            padding: 28px;
            border-radius: 30px;
            box-shadow: 0 14px 28px rgba(21, 36, 28, .06);
        }

        html.js .member-avatar-card[data-reveal] {
            opacity: 0;
            transform: translateY(12px);
            transition: opacity .55s ease, transform .55s ease;
        }

        html.js .member-avatar-card[data-reveal].is-visible {
            opacity: 1;
            transform: none;
        }

        .member-avatar-card__identity {
            display: flex;
            min-height: 100%;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: #0e3b2b;
            color: #fff;
        }

        .member-avatar-card__photo {
            display: grid;
            width: 152px;
            height: 152px;
            place-items: center;
            overflow: hidden;
            border: 3px solid #7fad39;
            border-radius: 50%;
            background: #092d20;
            color: #c5dba5;
            font-family: 'DM Sans', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            letter-spacing: -.04em;
        }

        .member-avatar-card__photo img,
        .member-avatar-preview__circle img,
        .member-avatar-file__thumbnail img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .member-avatar-card__name {
            margin: 20px 0 6px;
            color: #fff;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .member-avatar-card__id {
            margin: 0;
            color: #e0ead8;
            font-size: .88rem;
        }

        .member-avatar-card__state {
            margin: 18px 0 0;
            color: #e0ead8;
            font-size: .83rem;
        }

        .member-avatar-delete {
            margin-top: 16px;
        }

        .member-avatar-delete__trigger,
        .member-avatar-delete__confirm button {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 14px;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #fff;
            font: inherit;
            font-size: .88rem;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
            cursor: pointer;
        }

        .member-avatar-delete__trigger svg,
        .member-avatar-delete__confirm svg {
            display: block;
            width: 18px;
            height: 18px;
            flex: 0 0 18px;
            stroke: currentColor;
        }

        .member-avatar-delete__trigger:focus-visible,
        .member-avatar-delete__confirm button:focus-visible,
        .member-avatar-file__change:focus-visible,
        .member-avatar-file__clear:focus-visible {
            outline: 3px solid #c5dba5;
            outline-offset: 2px;
        }

        .member-avatar-delete__confirm {
            display: none;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
            gap: 4px;
            color: #fff;
            font-size: .88rem;
        }

        .member-avatar-delete.is-confirming .member-avatar-delete__trigger {
            display: none;
        }

        .member-avatar-delete.is-confirming .member-avatar-delete__confirm {
            display: flex;
        }

        .member-avatar-delete__confirm button {
            padding-inline: 9px;
        }

        .member-avatar-upload {
            display: grid;
            align-content: start;
            gap: 18px;
            background: #fff;
        }

        .member-avatar-upload__title {
            margin: 0;
            color: #15241c;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -.03em;
        }

        .member-avatar-dropzone {
            position: relative;
            display: flex;
            min-height: 180px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 22px;
            border: 2px dashed #9dbb7c;
            border-radius: 20px;
            background: #f5f9ef;
            color: #214b34;
            text-align: center;
            cursor: pointer;
        }

        .member-avatar-dropzone:hover,
        .member-avatar-dropzone.is-dragging {
            border-color: #527d28;
            border-style: solid;
            background: #edf7dd;
        }

        .member-avatar-dropzone:focus-within {
            outline: 3px solid #6a9632;
            outline-offset: 3px;
        }

        .member-avatar-dropzone[hidden],
        .member-avatar-file[hidden],
        .member-avatar-preview[hidden] {
            display: none;
        }

        .member-avatar-dropzone__input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            clip-path: inset(50%);
        }

        .member-avatar-dropzone__icon {
            display: block;
            width: 24px;
            height: 24px;
            flex: 0 0 24px;
            stroke: currentColor;
        }

        .member-avatar-dropzone__heading {
            margin: 0;
            font-size: .98rem;
            font-weight: 800;
        }

        .member-avatar-dropzone__help {
            margin: 0;
            color: #52635a;
            font-size: .8rem;
        }

        .member-avatar-file {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border: 1px solid #dce9ce;
            border-radius: 16px;
            background: #f8faf5;
        }

        .member-avatar-file__thumbnail {
            display: block;
            width: 56px;
            height: 56px;
            flex: 0 0 56px;
            overflow: hidden;
            border-radius: 12px;
            background: #edf7dd;
        }

        .member-avatar-file__details {
            display: grid;
            min-width: 0;
            flex: 1;
            gap: 4px;
        }

        .member-avatar-file__name {
            overflow: hidden;
            color: #20372a;
            font-size: .88rem;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .member-avatar-file__size {
            color: #52635a;
            font-size: .78rem;
        }

        .member-avatar-file__actions {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .member-avatar-file__change {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            padding: 8px 10px;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #214b34;
            font: inherit;
            font-size: .82rem;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
            cursor: pointer;
        }

        .member-avatar-file__clear {
            display: grid;
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            place-items: center;
            border: 0;
            border-radius: 12px;
            background: transparent;
            color: #52635a;
            cursor: pointer;
        }

        .member-avatar-file__clear svg {
            display: block;
            width: 18px;
            height: 18px;
            stroke: currentColor;
        }

        .member-avatar-error {
            margin: -10px 0 0;
            color: #b42318;
            font-size: .82rem;
            font-weight: 600;
            line-height: 1.45;
        }

        .member-avatar-preview {
            display: grid;
            justify-items: start;
            gap: 12px;
            opacity: 0;
            transition: opacity .2s ease;
        }

        .member-avatar-preview.is-visible {
            opacity: 1;
        }

        .member-avatar-preview__title {
            margin: 0;
            color: #25382d;
            font-size: .88rem;
            font-weight: 800;
        }

        .member-avatar-preview__sizes {
            display: flex;
            align-items: end;
            gap: 20px;
        }

        .member-avatar-preview__item {
            display: grid;
            justify-items: center;
            gap: 7px;
            color: #52635a;
            font-size: .76rem;
            font-weight: 600;
        }

        .member-avatar-preview__circle {
            display: block;
            width: 88px;
            height: 88px;
            overflow: hidden;
            border: 2px solid #7fad39;
            border-radius: 50%;
            background: #edf7dd;
        }

        .member-avatar-preview__circle--mobile {
            width: 40px;
            height: 40px;
        }

        .member-avatar-preview__note {
            margin: 0;
            color: #52635a;
            font-size: .78rem;
        }

        .member-avatar-notes {
            display: grid;
            gap: 8px;
            padding: 14px 16px;
            border: 1px solid #dce9ce;
            border-radius: 16px;
            background: #edf7dd;
            color: #214b34;
            list-style: none;
        }

        .member-avatar-notes li {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: .8rem;
            line-height: 1.4;
        }

        .member-avatar-notes svg {
            display: block;
            width: 18px;
            height: 18px;
            flex: 0 0 18px;
            stroke: currentColor;
        }

        .member-avatar-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
        }

        .member-avatar-actions .loan-primary-button[aria-disabled="true"] {
            cursor: not-allowed;
            opacity: .58;
        }

        .member-avatar-actions .loan-primary-button:disabled {
            cursor: wait;
        }

        .member-avatar-actions__cancel {
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

        .member-avatar-actions .loan-primary-button svg {
            display: block;
            width: 18px;
            height: 18px;
            flex: 0 0 18px;
        }

        @media (max-width: 1023px) {
            .member-avatar-page__cards {
                grid-template-columns: minmax(0, 1fr);
            }

            .member-avatar-card__identity {
                min-height: 0;
            }

            .member-avatar-card__photo {
                width: 120px;
                height: 120px;
                font-size: 2.4rem;
            }
        }

        @media (max-width: 640px) {
            .member-avatar-card {
                padding: 22px;
                border-radius: 24px;
            }

            .member-avatar-dropzone {
                min-height: 150px;
                padding: 18px;
            }

            .member-avatar-file {
                gap: 9px;
                padding: 9px;
            }

            .member-avatar-file__actions {
                gap: 0;
            }

            .member-avatar-file__change {
                padding-inline: 6px;
                font-size: .78rem;
            }

            .member-avatar-actions {
                flex-direction: column-reverse;
                align-items: stretch;
                gap: 4px;
            }

            .member-avatar-actions .loan-primary-button {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            html.js .member-avatar-card[data-reveal] {
                opacity: 1;
                transform: none;
                transition: none;
            }

            .member-avatar-preview {
                transition: none;
            }
        }
    </style>
@endonce
