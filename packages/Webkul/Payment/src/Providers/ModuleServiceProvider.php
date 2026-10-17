<?php

namespace Webkul\Payment\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Payment\Models\PaymentReceipt;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [
        PaymentReceipt::class,
    ];
}
