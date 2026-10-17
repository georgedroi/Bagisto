<x-admin::layouts>
    @php
        $updateLabels = [];

        foreach (['checking', 'failed', 'unavailable', 'ready', 'reconnecting'] as $label) {
            $updateLabels[$label] = trans('admin::system-updates.'.$label);
        }

        $resumeStatusUrl = $activeOperation ? route('admin.settings.system_updates.status', $activeOperation) : null;
    @endphp
    <x-slot:title>@lang('admin::system-updates.title')</x-slot>

    <div class="mp-updates" data-system-updates>
        <div class="mp-updates__heading">
            <h1>@lang('admin::system-updates.title')</h1>
            <form method="POST" action="{{ route('admin.settings.system_updates.check') }}" data-update-form="check">
                @csrf
                <button class="primary-button" type="submit">@lang('admin::system-updates.check')</button>
            </form>
        </div>

        <div class="mp-updates__grid">
            <section class="mp-updates__card">
                <h2>@lang('admin::system-updates.current')</h2>
                <p class="mp-updates__version">{{ $installed['version'] }}</p>
                <p class="mp-updates__muted" data-connection-message>
                    @lang('admin::system-updates.'.($capabilities['ready'] ? 'ready' : 'unavailable'))
                </p>
            </section>

            <section class="mp-updates__card" data-release-card hidden>
                <h2>@lang('admin::system-updates.latest')</h2>
                <p class="mp-updates__version" data-release-version></p>
                <form method="POST" action="{{ route('admin.settings.system_updates.install') }}" data-update-form="install">
                    @csrf
                    <input type="hidden" name="token" value="">
                    <button class="primary-button" type="submit" disabled>@lang('admin::system-updates.install')</button>
                </form>
            </section>
        </div>

        <p role="status" aria-live="polite" class="mp-updates__message" data-update-message></p>

        <section class="mp-updates__card" data-progress-card hidden>
            <h2>@lang('admin::system-updates.progress')</h2>
            <p data-progress-message></p>
        </section>

        <section class="mp-updates__card" data-notes-card hidden>
            <h2>@lang('admin::system-updates.notes')</h2>
            <pre data-release-notes></pre>
        </section>

        <form method="POST" action="{{ route('admin.settings.system_updates.rollback') }}" data-update-form="rollback" class="mp-updates__rollback">
            @csrf
            <button class="secondary-button" type="submit" @disabled(! $capabilities['rollback'] || $capabilities['busy'])>
                @lang('admin::system-updates.rollback')
            </button>
        </form>
    </div>

    @pushOnce('styles')
        <style>
            .mp-updates { max-width: 1100px; color: #334155; }
            .dark .mp-updates { color: #cbd5e1; }
            .mp-updates [hidden] { display: none !important; }
            .mp-updates__heading { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-block-end: 22px; }
            .mp-updates h1 { font-size: 22px; font-weight: 600; }
            .mp-updates h2 { margin-block-end: 12px; font-size: 14px; font-weight: 600; }
            .mp-updates__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 310px), 1fr)); gap: 16px; }
            .mp-updates__card { margin-block-end: 16px; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; background: #fff; }
            .dark .mp-updates__card { border-color: #263144; background: #101725; }
            .mp-updates__version { margin-block-end: 14px; overflow-wrap: anywhere; font-size: 18px; font-weight: 600; }
            .mp-updates__muted { color: #64748b; font-size: 13px; }
            .dark .mp-updates__muted { color: #94a3b8; }
            .mp-updates__message { min-height: 24px; margin-block: 8px 16px; overflow-wrap: anywhere; }
            .mp-updates pre { overflow-wrap: anywhere; white-space: pre-wrap; font: inherit; font-size: 13px; line-height: 1.8; }
            .mp-updates button:disabled { cursor: default; opacity: .5; }
            .mp-updates__rollback { margin-block-start: 24px; }
        </style>
    @endPushOnce

    @pushOnce('scripts')
        <script type="module">
            const updateLabels = @json($updateLabels);
            const resumeStatusUrl = @json($resumeStatusUrl);
            let updateCapabilities = @json($capabilities);
            let updateCandidate = null;
            let updateBusy = false;

            /**
             * Keep each deployment action disabled until its prerequisites are present.
             */
            function synchronizeUpdateControls() {
                document.querySelectorAll('[data-update-form]').forEach(form => {
                    const action = form.dataset.updateForm;
                    const button = form.querySelector('button[type="submit"]');

                    button.disabled = updateBusy || (action !== 'check' && (! updateCapabilities.ready || updateCapabilities.busy))
                        || (action === 'install' && ! updateCandidate?.available)
                        || (action === 'rollback' && ! updateCapabilities.rollback);
                });
            }

            /**
             * Display release notes as text and preserve the verified session token.
             */
            function renderUpdateCandidate(candidate) {
                updateCandidate = candidate;
                document.querySelector('[data-release-card]').hidden = ! candidate;
                document.querySelector('[data-notes-card]').hidden = ! candidate;
                document.querySelector('[data-release-version]').textContent = candidate?.version ?? '';
                document.querySelector('[data-release-notes]').textContent = candidate?.notes ?? '';
                document.querySelector('[data-update-form="install"] input[name="token"]').value = candidate?.token ?? '';
            }

            /**
             * Retry through a short maintenance window without repeating a deployment request.
             */
            async function pollUpdateOperation(statusUrl) {
                const deadline = Date.now() + 1800000;

                while (Date.now() < deadline) {
                    await new Promise(resolve => setTimeout(resolve, 3000));

                    try {
                        const response = await fetch(statusUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store' });

                        if (response.redirected || [401, 403, 404].includes(response.status)) {
                            throw new Error('session-ended');
                        }

                        if (! response.ok) {
                            if ([401, 403, 404].includes(response.status)) {
                                throw new Error('session-ended');
                            }

                            document.querySelector('[data-progress-message]').textContent = updateLabels.reconnecting;

                            continue;
                        }

                        if (! response.headers.get('content-type')?.includes('application/json')) {
                            throw new Error('session-ended');
                        }

                        const status = await response.json();

                        document.querySelector('[data-progress-message]').textContent = status.message;
                        updateCapabilities = status.capabilities ?? updateCapabilities;

                        if (status.terminal) {
                            if (['completed', 'rolled-back'].includes(status.phase)) {
                                location.reload();
                            }

                            return;
                        }
                    } catch (error) {
                        if (error.message === 'session-ended') {
                            throw error;
                        }

                        document.querySelector('[data-progress-message]').textContent = updateLabels.reconnecting;
                    }
                }

                throw new Error('operation-timeout');
            }

            /**
             * Submit explicit owner actions with the application's existing CSRF protection.
             */
            async function handleUpdateSubmit(event) {
                const form = event.target;

                if (! (form instanceof HTMLFormElement) || ! form.matches('[data-update-form]')) {
                    return;
                }

                event.preventDefault();

                if (updateBusy) {
                    return;
                }

                updateBusy = true;
                synchronizeUpdateControls();
                const message = document.querySelector('[data-update-message]');

                message.textContent = updateLabels.checking;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST', body: new FormData(form), credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });
                    const result = await response.json();

                    message.textContent = result.message ?? updateLabels.failed;

                    if (! response.ok) {
                        if (form.dataset.updateForm === 'check') {
                            renderUpdateCandidate(null);
                        }

                        return;
                    }

                    if (form.dataset.updateForm === 'check') {
                        updateCapabilities = result.capabilities ?? updateCapabilities;
                        renderUpdateCandidate(result.candidate);
                    } else {
                        document.querySelector('[data-progress-card]').hidden = false;
                        document.querySelector('[data-progress-message]').textContent = result.message;
                        renderUpdateCandidate(null);
                        await pollUpdateOperation(result.status_url);
                    }
                } catch {
                    message.textContent = updateLabels.failed;
                } finally {
                    updateBusy = false;
                    synchronizeUpdateControls();
                }
            }

            document.addEventListener('submit', handleUpdateSubmit);
            window.addEventListener('load', async () => {
                synchronizeUpdateControls();

                if (resumeStatusUrl) {
                    updateBusy = true;
                    synchronizeUpdateControls();
                    document.querySelector('[data-progress-card]').hidden = false;

                    try {
                        await pollUpdateOperation(resumeStatusUrl);
                    } catch {
                        document.querySelector('[data-update-message]').textContent = updateLabels.failed;
                    } finally {
                        updateBusy = false;
                        synchronizeUpdateControls();
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
