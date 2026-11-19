@if (core()->getConfigData('general.content.footer.copyright_content'))
    {!! clean_content((string) core()->getConfigData('general.content.footer.copyright_content')) !!}
@else
    {{ trans('shop::app.customers.copyright', ['current_year' => date('Y')]) }}
@endif
