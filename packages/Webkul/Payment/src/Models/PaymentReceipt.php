<?php

namespace Webkul\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Payment\Contracts\PaymentReceipt as PaymentReceiptContract;
use Webkul\Sales\Models\OrderProxy;

class PaymentReceipt extends Model implements PaymentReceiptContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_receipts';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the order associated with the receipt.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderProxy::modelClass());
    }
}
