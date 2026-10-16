<?php

namespace Webkul\Payment\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Illuminate\Routing\Controller;

class PaymentRedirectController extends Controller
{
    /**
     * Handle credit/debit cards redirect.
     */
    public function creditdebitcards(): RedirectResponse
    {
        throw new ServiceUnavailableHttpException;
    }

    /**
     * Handle FPX redirect.
     */
    public function fpx(): RedirectResponse
    {
        throw new ServiceUnavailableHttpException;
    }

    /**
     * Handle automated DuitNow QR redirect.
     */
    public function duitnowqr_auto(): RedirectResponse
    {
        throw new ServiceUnavailableHttpException;
    }

    /**
     * Handle Touch 'n Go eWallet redirect.
     */
    public function tngewallet(): RedirectResponse
    {
        throw new ServiceUnavailableHttpException;
    }
}
