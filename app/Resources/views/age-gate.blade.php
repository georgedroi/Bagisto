<div
    id="manpleasure-age-gate"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-5"
    role="dialog"
    aria-modal="true"
    aria-labelledby="manpleasure-age-gate-title"
    hidden
>
    <div class="w-full max-w-md rounded-lg bg-white p-6 text-center shadow-xl">
        <h2 id="manpleasure-age-gate-title" class="text-2xl font-bold text-gray-900">Age Confirmation</h2>
        <p class="mt-3 text-sm leading-6 text-gray-600">This store contains adult wellness products. You must be of legal age in your location to continue.</p>
        <div class="mt-6 flex justify-center gap-3">
            <button type="button" data-age-gate-leave class="secondary-button">Leave</button>
            <button type="button" data-age-gate-enter class="primary-button">I am of legal age</button>
        </div>
    </div>
</div>

@pushOnce('scripts')
    <script>
        (() => {
            const key = 'manpleasure_age_confirmed';
            const gate = document.getElementById('manpleasure-age-gate');
            if (! gate || window.localStorage.getItem(key) === '1') return;

            gate.hidden = false;
            document.body.classList.add('overflow-hidden');
            gate.querySelector('[data-age-gate-enter]')?.addEventListener('click', () => {
                window.localStorage.setItem(key, '1');
                gate.remove();
                document.body.classList.remove('overflow-hidden');
            });
            gate.querySelector('[data-age-gate-leave]')?.addEventListener('click', () => {
                window.location.replace('about:blank');
            });
        })();
    </script>
@endPushOnce
