<?php

return [
    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'dummy'),

    // Map internal payment methods to configured provider adapters.
    'method_map' => [
        'card' => env('PAYMENT_CARD_GATEWAY', 'paystack'),
        'bank_transfer' => env('PAYMENT_BANK_TRANSFER_GATEWAY', 'flutterwave'),
        'cash_on_delivery' => env('PAYMENT_COD_GATEWAY', 'dummy'),
    ],
];
