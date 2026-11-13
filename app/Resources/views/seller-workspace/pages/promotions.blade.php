<nav class="msw-crumb" aria-label="{{ trans('admin::seller-workspace.home') }}">
    <a href="{{ $overviewUrl }}">{{ trans('admin::seller-workspace.home') }}</a>
    @include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])
    <strong>{{ trans('admin::seller-workspace.pages.promotions') }}</strong>
</nav>
<h1 class="msw-hidden">{{ trans('admin::seller-workspace.titles.promotions') }}</h1>
<div class="msw-tabs msw-tabs-sm msw-page-tabs" style="margin-top: 20px">
    <a class="is-active" href="{{ route('admin.seller_workspace.promotions') }}">{{ trans('admin::seller-workspace.pages.promotions') }}</a>
</div>

@foreach ([['cart', $cartRules, 'cart_rules'], ['catalog', $catalogRules, 'catalog_rules']] as [$kind, $rules, $route])
    <section class="msw-card">
        <div class="msw-card-head">
            <h2 class="msw-title">{{ trans('admin::seller-workspace.promotions.'.$kind) }}</h2>
            <div class="msw-chips">
                <a class="msw-button" href="{{ route('admin.marketing.promotions.'.$route.'.index') }}">{{ trans('admin::seller-workspace.promotions.manage') }}</a>
                <a class="msw-button msw-primary" href="{{ route('admin.marketing.promotions.'.$route.'.create') }}">{{ trans('admin::seller-workspace.promotions.create') }}</a>
            </div>
        </div>
        <div class="msw-table msw-table-soft"><table>
            <thead><tr>
                @foreach (['name', 'discount', 'starts', 'ends', 'status', 'action'] as $column)
                    <th>{{ trans('admin::seller-workspace.columns.'.$column) }}</th>
                @endforeach
            </tr></thead>
            <tbody>
                @forelse ($rules as $rule)
                    <tr>
                        <td>{{ $rule->name }}</td>
                        <td>{{ number_format((float) $rule->discount_amount, 2) }}</td>
                        <td>{{ $rule->starts_from ?: '—' }}</td>
                        <td>{{ $rule->ends_till ?: '—' }}</td>
                        <td><span class="msw-pill">{{ trans($rule->status ? 'admin::seller-workspace.products.active' : 'admin::seller-workspace.products.inactive') }}</span></td>
                        <td><a href="{{ route('admin.marketing.promotions.'.$route.'.edit', $rule->id) }}">{{ trans('admin::seller-workspace.edit') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><p class="msw-empty">{{ trans('admin::seller-workspace.empty') }}</p></td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
@endforeach
