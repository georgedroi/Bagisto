<x-shop::layouts :has-feature="false">
    
    <x-slot:title>
        {{ $title ?? '' }}
    </x-slot>

    <div class="mp-account-page container px-[60px] max-lg:px-8 max-md:px-0">
        <x-shop::layouts.account.breadcrumb />

        <div class="mt-8 flex items-start gap-10 max-lg:gap-5 max-md:mt-5 max-md:grid ltr:max-md:gap-0 rtl:max-md:gap-0">
            {{ $slot }}
        </div>
    </div>
</x-shop::layouts>
