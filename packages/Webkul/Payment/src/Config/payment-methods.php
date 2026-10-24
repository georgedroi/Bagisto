<?php

use Webkul\Payment\Payment\BankTransfer;
use Webkul\Payment\Payment\CashOnDelivery;
use Webkul\Payment\Payment\CreditDebitCards;
use Webkul\Payment\Payment\CryptoTransfer;
use Webkul\Payment\Payment\DuitNowQR;
use Webkul\Payment\Payment\DuitNowQRAuto;
use Webkul\Payment\Payment\DuitNowQRManual;
use Webkul\Payment\Payment\FPX;
use Webkul\Payment\Payment\MoneyTransfer;
use Webkul\Payment\Payment\TnGeWallet;

return [
    'cashondelivery' => [
        'class' => CashOnDelivery::class,
        'code' => 'cashondelivery',
        'title' => 'Cash On Delivery',
        'description' => 'Cash On Delivery',
        'active' => true,
        'generate_invoice' => false,
        'sort' => 7,
    ],

    'moneytransfer' => [
        'class' => MoneyTransfer::class,
        'code' => 'moneytransfer',
        'title' => 'Money Transfer',
        'description' => 'Money Transfer',
        'active' => true,
        'generate_invoice' => false,
        'sort' => 8,
    ],

    'duitnowqr_manual' => [
        'class' => DuitNowQRManual::class,
        'code' => 'duitnowqr_manual',
        'title' => 'DuitNow QR (Manual Bank Transfer / QR)',
        'description' => 'Pay via DuitNow QR and upload your transfer receipt for verification.',
        'active' => false,
        'generate_invoice' => false,
        'sort' => 1,
    ],

    'banktransfer' => [
        'class' => BankTransfer::class,
        'code' => 'banktransfer',
        'title' => 'Bank Transfer',
        'description' => 'Pay by online bank transfer. Our bank details are shown after you select this option.',
        'active' => false,
        'generate_invoice' => false,
        'sort' => 1,
    ],

    'cryptotransfer' => [
        'class' => CryptoTransfer::class,
        'code' => 'cryptotransfer',
        'title' => 'Bitcoin / USDT',
        'description' => 'Pay with Bitcoin or USDT by sending the exact amount to our wallet.',
        'active' => false,
        'generate_invoice' => false,
        'sort' => 2,
    ],

    'duitnowqr' => [
        'class' => DuitNowQR::class,
        'code' => 'duitnowqr',
        'title' => 'DuitNow QR',
        'description' => 'Pay via DuitNow QR.',
        'active' => false,
        'generate_invoice' => false,
        'sort' => 2,
    ],

    'creditdebitcards' => [
        'class' => CreditDebitCards::class,
        'code' => 'creditdebitcards',
        'title' => 'Credit / Debit Cards',
        'description' => 'Pay securely with Visa or Mastercard.',
        'active' => false,
        'generate_invoice' => false,
        'sort' => 3,
    ],

    'fpx' => [
        'class' => FPX::class,
        'code' => 'fpx',
        'title' => 'FPX Online Banking',
        'description' => 'Pay directly via Malaysian online banking (FPX).',
        'active' => false,
        'generate_invoice' => false,
        'sort' => 4,
    ],

    'duitnowqr_auto' => [
        'class' => DuitNowQRAuto::class,
        'code' => 'duitnowqr_auto',
        'title' => 'DuitNow QR (Instant Automated)',
        'description' => 'Instant payment via dynamic DuitNow QR code.',
        'active' => false,
        'generate_invoice' => false,
        'sort' => 5,
    ],

    'tngewallet' => [
        'class' => TnGeWallet::class,
        'code' => 'tngewallet',
        'title' => 'Touch \'n Go eWallet',
        'description' => 'Pay using Touch \'n Go eWallet.',
        'active' => false,
        'generate_invoice' => false,
        'sort' => 6,
    ],
];
