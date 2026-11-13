<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('admin::order-status.awb-label') }} — {{ $order->increment_id }}</title>
    <style>
        body { margin: 20px; font-family: sans-serif; text-align: center; }
        img { display: block; max-width: 100%; height: auto; margin: 20px auto; }
        button { padding: 12px 24px; cursor: pointer; }
        @media print { nav { display: none; } body { margin: 0; } img { margin: 0; max-height: 100vh; object-fit: contain; } }
    </style>
</head>
<body>
    <nav><button type="button" onclick="window.print()">{{ trans('admin::order-status.awb-print') }}</button></nav>
    <img src="{{ route('admin.sales.orders.awb.image', $order->id) }}" alt="{{ trans('admin::order-status.awb-label') }}">
</body>
</html>
