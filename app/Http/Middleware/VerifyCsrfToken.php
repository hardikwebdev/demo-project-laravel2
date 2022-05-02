<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
       /* 'cart/paypal_success',
        'service/boost/paypal_success',
        'wallet/success_deposite_wallet',*/
        
        'cart/ipnNotify',
        'payoneer/signup/ipcn',
        'payoneer/signup/approved',
        'payoneer/signup/decline',
        'payoneer/payout_funded/ipcn',
        'payoneer/payout_failed/ipcn',
        'bluesnap/ipn',
        'skrill/ipn',
        'skrill/payout/ipn',
        'skrill/refund/ipn',
    ];
}
