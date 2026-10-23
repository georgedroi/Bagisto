<x-admin::layouts>
    <x-slot:title>Login Page Builder</x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-800 dark:text-white">
                    Login Page Builder
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Design the page layout around the existing secure admin login form.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button id="save-draft-button" type="button"
                    class="rounded bg-blue-600 px-4 py-2 font-semibold text-white">
                    Save Draft
                </button>

                <button id="publish-button" type="submit" form="publish-form"
                    class="rounded bg-green-700 px-4 py-2 font-semibold text-white">
                    Publish
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded border border-green-300 bg-green-50 p-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded border border-red-300 bg-red-50 p-3 text-red-800">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Status:
                <strong>{{ $settings->is_published ? 'Published' : 'Draft only' }}</strong>.
                Save your changes before publishing. Publishing does not activate the
                design on the login screen until the login-template integration is completed.
            </p>
        </div>

        <div id="manpleasure-login-builder"
            data-save-url="{{ route('admin.appearance.login_page_builder.save') }}"
            data-publish-url="{{ route('admin.appearance.login_page_builder.publish') }}"
            data-unpublish-url="{{ route('admin.appearance.login_page_builder.unpublish') }}">
        </div>

        <form id="save-form" method="POST"
            action="{{ route('admin.appearance.login_page_builder.save') }}" class="hidden">
            @csrf
            <input type="hidden" name="html" id="builder-html">
            <input type="hidden" name="css" id="builder-css">
            <input type="hidden" name="project_json" id="builder-project-json">
        </form>

        <form id="publish-form" method="POST"
            action="{{ route('admin.appearance.login_page_builder.publish') }}" class="hidden">
            @csrf
        </form>

        <form id="unpublish-form" method="POST"
            action="{{ route('admin.appearance.login_page_builder.unpublish') }}" class="hidden">
            @csrf
        </form>

        <script id="builder-project-data" type="application/json">@json($settings->draft_project_json ? json_decode($settings->draft_project_json, true) : null)</script>
        <script id="builder-html-data" type="application/json">@json($settings->draft_html)</script>
        <script id="builder-css-data" type="application/json">@json($settings->draft_css)</script>
    </div>

    @push('scripts')
        @vite(['resources/css/admin-login-builder.css', 'resources/js/admin-login-builder.js'])
    @endpush
</x-admin::layouts>