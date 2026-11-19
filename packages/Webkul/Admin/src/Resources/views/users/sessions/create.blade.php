<x-admin::layouts.anonymous>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.users.sessions.title')
    </x-slot>

    @php
        $loginDesign = app(\App\Repositories\LoginPageSettingRepository::class)->published();
        $loginSanitizer = app(\App\Support\ManPleasureLoginPageSanitizer::class);
    @endphp

    <div class="flex h-[100vh] items-center justify-center {{ $loginDesign ? 'msp-branded-login' : '' }}">
        @if ($loginDesign)
            <aside class="msp-login-branding" data-published-login-design v-pre>
                {!! $loginSanitizer->cleanHtml($loginDesign->published_html) !!}
            </aside>
        @endif
        <div class="flex flex-col items-center gap-5">
            <!-- Logo -->            
            @if ($logo = core()->getConfigData('general.design.admin_logo.logo_image'))
                <img
                    class="h-10 w-[110px]"
                    src="{{ Storage::url($logo) }}"
                    alt="{{ config('app.name') }}"
                />
            @else
                <img
                    class="w-max" 
                    src="{{ bagisto_asset('images/logo.svg') }}"
                    alt="{{ config('app.name') }}"
                />
            @endif

            <div class="box-shadow flex min-w-[300px] flex-col rounded-md bg-white dark:bg-gray-900">
                <!-- Login Form -->
                <x-admin::form :action="route('admin.session.store')">
                    <p class="p-4 text-xl font-bold text-gray-800 dark:text-white">
                        @lang('admin::app.users.sessions.title')
                    </p>

                    <div class="border-y p-4 dark:border-gray-800">
                        <!-- Email -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.users.sessions.email')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control 
                                type="email" 
                                class="w-[254px] max-w-full" 
                                id="email"
                                name="email" 
                                rules="required|email" 
                                :label="trans('admin::app.users.sessions.email')"
                                :placeholder="trans('admin::app.users.sessions.email')"
                            />

                            <x-admin::form.control-group.error control-name="email" />
                        </x-admin::form.control-group>

                        <!-- Password -->
                        <x-admin::form.control-group class="relative w-full">
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.users.sessions.password')
                            </x-admin::form.control-group.label>
                    
                            <x-admin::form.control-group.control 
                                type="password" 
                                class="w-[254px] max-w-full ltr:pr-12 rtl:pl-12"
                                id="password"
                                name="password" 
                                rules="required|min:6" 
                                :label="trans('admin::app.users.sessions.password')"
                                :placeholder="trans('admin::app.users.sessions.password')"
                            />
                    
                            <button
                                type="button"
                                class="icon-view absolute top-[42px] flex h-11 w-11 -translate-y-2/4 cursor-pointer items-center justify-center rounded text-2xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 ltr:right-0 rtl:left-0"
                                onclick="switchVisibility()"
                                id="visibilityIcon"
                                aria-label="{{ trans('admin::app.users.sessions.show-password') }}"
                                aria-controls="password"
                                aria-pressed="false"
                            >
                            </button>
                    
                            <x-admin::form.control-group.error control-name="password" />
                        </x-admin::form.control-group>
                    </div>

                    <div class="flex items-center justify-between p-4">
                        <!-- Forgot Password Link -->
                        <a 
                            class="cursor-pointer text-xs font-semibold leading-6 text-blue-600"
                            href="{{ route('admin.forget_password.create') }}"
                        >
                            @lang('admin::app.users.sessions.forget-password-link')
                        </a>

                        <!-- Submit Button -->
                        <button
                            class="cursor-pointer rounded-md border border-blue-700 bg-blue-600 px-3.5 py-1.5 font-semibold text-gray-50"
                            aria-label="{{ trans('admin::app.users.sessions.submit-btn')}}"
                        >
                            @lang('admin::app.users.sessions.submit-btn')
                        </button>
                    </div>
                </x-admin::form>
            </div>

        </div>
    </div>

    @push('scripts')
        @if ($loginDesign)
            <script type="module">
                const brandingStyle = document.createElement('style');
                brandingStyle.textContent = @js($loginSanitizer->cleanCss($loginDesign->published_css));
                document.head.appendChild(brandingStyle);
            </script>
        @endif
        <script>
            /**
             * Toggle password visibility and expose the current state to assistive technology.
             */
            function switchVisibility() {
                const passwordField = document.getElementById("password");
                const visibilityIcon = document.getElementById("visibilityIcon");

                passwordField.type = passwordField.type === "password" ? "text" : "password";
                visibilityIcon.classList.toggle("icon-view-close");
                visibilityIcon.setAttribute("aria-pressed", String(passwordField.type === "text"));
            }
        </script>
    @endpush

    @pushOnce('styles')
        <style>
            .msp-branded-login{height:auto!important;min-height:100vh;display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,440px);gap:32px;padding:32px;box-sizing:border-box}.msp-login-branding{min-width:0;overflow-wrap:anywhere}.msp-login-branding img{max-width:100%;height:auto}@media(max-width:760px){.msp-branded-login{grid-template-columns:minmax(0,1fr);padding:20px 12px;gap:20px}.msp-login-branding{max-width:100%}}
        </style>
    @endPushOnce
</x-admin::layouts.anonymous>
