<style>
    body.mp-age-gate-open {
        overflow: hidden !important;
    }

    body.mp-age-gate-open #app {
        visibility: hidden;
    }

    #manpleasure-age-gate {
        position: fixed !important;
        inset: 0 !important;
        z-index: 2147483647 !important;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        padding: 24px;
        overflow: auto;
        background: #08070a !important;
        isolation: isolate;
        font-family: Arial, sans-serif;
    }

    #manpleasure-age-gate[hidden] {
        display: none !important;
    }

    #manpleasure-age-gate > .mp-age-gate-panel {
        box-sizing: border-box;
        width: 100%;
        max-width: 440px;
        margin: auto;
        padding: 36px 28px;
        border: 1px solid #4b2b3b;
        border-radius: 16px;
        background: #151017;
        box-shadow: 0 24px 80px #000;
        text-align: center;
    }

    #manpleasure-age-gate-title {
        margin: 0;
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        line-height: 1.25;
    }

    #manpleasure-age-gate-description {
        margin: 18px 0 0;
        color: #ded4db;
        font-size: 15px;
        line-height: 1.7;
    }

    #manpleasure-age-gate .mp-age-gate-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px;
        margin-top: 28px;
    }

    #manpleasure-age-gate button {
        box-sizing: border-box;
        flex: 1 1 150px;
        min-height: 48px;
        padding: 12px 16px;
        border: 1px solid #ec297e;
        border-radius: 8px;
        background: transparent;
        color: #fff;
        font: inherit;
        font-weight: 600;
        line-height: 1.4;
        cursor: pointer;
    }

    #manpleasure-age-gate [data-age-gate-enter] {
        background: #ec297e;
    }

    #manpleasure-age-gate button:focus-visible {
        outline: 3px solid #fff;
        outline-offset: 4px;
    }

    @media (max-width: 480px) {
        #manpleasure-age-gate {
            padding: 20px;
        }

        #manpleasure-age-gate > .mp-age-gate-panel {
            padding: 28px 20px;
        }

        #manpleasure-age-gate-title {
            font-size: 25px;
        }
    }
</style>

<div
    id="manpleasure-age-gate"
    class="mp-age-gate"
    role="dialog"
    aria-modal="true"
    aria-labelledby="manpleasure-age-gate-title"
    aria-describedby="manpleasure-age-gate-description"
    hidden
>
    <div class="mp-age-gate-panel">
        <h2 id="manpleasure-age-gate-title" class="text-2xl font-bold text-gray-900">{{ trans('manpleasure.age-title') }}</h2>
        <p id="manpleasure-age-gate-description" class="mt-3 text-sm leading-6 text-gray-600">{{ trans('manpleasure.age-description') }}</p>
        <div class="mp-age-gate-actions">
            <button type="button" data-age-gate-leave class="secondary-button">{{ trans('manpleasure.age-leave') }}</button>
            <button type="button" data-age-gate-enter class="primary-button">{{ trans('manpleasure.age-enter') }}</button>
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script>
        (() => {
            const key = 'manpleasure_age_confirmed';
            const gate = document.getElementById('manpleasure-age-gate');
            const storefront = document.getElementById('app');
            const previousFocus = document.activeElement;
            let confirmed = false;

            try {
                confirmed = window.localStorage.getItem(key) === '1';
            } catch (error) {}

            if (! gate || confirmed) return;

            gate.hidden = false;
            document.body.classList.add('mp-age-gate-open');
            if (storefront) storefront.inert = true;
            gate.querySelector('[data-age-gate-enter]')?.focus();
            gate.addEventListener('keydown', (event) => {
                if (event.key !== 'Tab') return;

                const buttons = [...gate.querySelectorAll('button')];
                const next = event.shiftKey ? buttons[0] : buttons[buttons.length - 1];

                if (document.activeElement === next) {
                    event.preventDefault();
                    buttons[event.shiftKey ? buttons.length - 1 : 0].focus();
                }
            });

            gate.querySelector('[data-age-gate-enter]')?.addEventListener('click', () => {
                try {
                    window.localStorage.setItem(key, '1');
                } catch (error) {}

                gate.remove();
                document.body.classList.remove('mp-age-gate-open');
                if (storefront) storefront.inert = false;
                previousFocus?.focus();
            });
            gate.querySelector('[data-age-gate-leave]')?.addEventListener('click', () => {
                window.location.replace('about:blank');
            });
        })();
    </script>
@endPushOnce
