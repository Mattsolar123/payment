<?php

return [
    /*
     * The Blade template to use as the root Inertia view.
     * If null, the package's default will be used.
     */
    'inertia_root_view' => env('PAYMENT_INERTIA_ROOT_VIEW', null),

    // Tells the package how to resolve {parent} in routes with route model binding
    // The class used should implement \Mralston\Payment\Interfaces\PaymentParentModel
    'parent_model' => env('PAYMENT_PARENT_MODEL', null),

    // This is the name that the package will use to refer to the parent record
    // of the payments, for example 'Quote' or 'Contract'
    'parent_model_description' => env('PAYMENT_PARENT_MODEL_DESCRIPTION', 'Parent'),

    // The parent application's user model
    'user_model' => env('PAYMENT_USER_MODEL', 'App\Models\User'),

    // A helper class, provided by the parent application, which the Payment package can use to interface with the parent
    'helper' => env('PAYMENT_HELPER', null),

    'default_cash_deposit' => env('PAYMENT_CASH_DEPOSIT', 0),
    'default_finance_deposit' => env('PAYMENT_FINANCE_DEPOSIT', 0),
    'default_lease_deposit' => env('PAYMENT_LEASE_DEPOSIT', 0),

    /*
    |--------------------------------------------------------------------------
    | Tandem Finance
    |--------------------------------------------------------------------------
    |
    | The API key and endpoint to be used when talking to the Tandem Finance API
    |
    */

    'tandem' => [
        'api_key' => env('TANDEM_API_KEY'),
        'endpoint' => env('TANDEM_ENDPOINT', config('app.env')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Propensio Finance
    |--------------------------------------------------------------------------
    |
    | The IBC reference and endpoint to be used when talking to the Propensio Finance API
    |
    */

    'propensio' => [
        'api_key' => env('PROPENSIO_API_KEY') ?? '',
        'endpoint' => env('PROPENSIO_ENDPOINT', config('app.env')),
    ],

    'hsbc' => [
        'api_key' => env('HSBC_API_KEY'),
        'endpoint' => env('HSBC_ENDPOINT', config('app.env')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Hometree Finance
    |--------------------------------------------------------------------------
    |
    | The API key and endpoint to be used when talking to the Allium Money API
    |
    */

    'hometree' => [
        'api_key' => env('HOMETREE_API_KEY'),
        'client_id' => env('HOMETREE_CLIENT_ID'),
        'endpoint' => env('HOMETREE_ENDPOINT', config('app.env')),
    ],

];
