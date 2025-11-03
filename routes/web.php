<?php

use Illuminate\Support\Facades\Route;
use Mralston\Payment\Http\Controllers\AddressLookupController;
use Mralston\Payment\Http\Controllers\CashController;
use Mralston\Payment\Http\Controllers\FinanceController;
use Mralston\Payment\Http\Controllers\LeaseController;
use Mralston\Payment\Http\Controllers\PaymentController;
use Mralston\Payment\Http\Controllers\PaymentOptionsController;
use Mralston\Payment\Http\Controllers\RemoteSignController;
use Mralston\Payment\Http\Controllers\SatNoteController;
use Mralston\Payment\Http\Controllers\SurveyController;
use Mralston\Payment\Http\Controllers\PrequalController;
use Mralston\Payment\Http\Controllers\WebhookController;
use Mralston\Payment\Http\Controllers\FinanceSigningLinkController;
use Mralston\Payment\Http\Middleware\RedirectIfNewPaymentProhibited;
use Mralston\Payment\Http\Middleware\RedirectToActivePayment;
use Mralston\Payment\Http\Middleware\RedirectToSelectedOffer;

/**
 * Tandem webhook
 */
Route::post('webhook/tandem/{uuid}', [WebhookController::class, 'tandem'])
    ->name('payment.webhook.tandem');

/**
 * Hsbc webhook
 */
Route::post('webhook/hsbc/{uuid}', [WebhookController::class, 'hsbc'])
    ->name('payment.webhook.hsbc');

/**
 * Hometree webhook
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::post('payments/webhook/hometree', [WebhookController::class, 'hometree'])
        ->name('payment.webhook.hometree');
});

/**
 * Remote sign
 */
Route::get('payments/remote-sign/{payment}', [RemoteSignController::class, 'remoteSign'])
    ->name('payment.remote-sign');

Route::group(['middleware' => ['web', 'auth']], function () {

    Route::post(
        'payments/{payment}/upload-sat-note',
        [SatNoteController::class, 'uploadSatNote']
    )->name('payments.upload_sat_note');

    Route::post('payments/send-remote-sign-link/{payment}', [RemoteSignController::class, 'sendRemoteSignLink'])
        ->name('send-remote-sign-link');

    Route::resource('payments', PaymentController::class);

    Route::prefix('payment')
        ->name('payment.')
        ->group(function () {


            /**
             * Entrypoint to the payment journey. Serves as a standard jumping in point for the parent
             * application, which won't necessarily understand the inner workings of the payment journey's routes.
             */
            Route::get('{parent}', [PaymentController::class, 'start'])
                ->middleware([RedirectToActivePayment::class, RedirectToSelectedOffer::class, RedirectIfNewPaymentProhibited::class])
                ->name('start');

            /**
             * Shows a 'locked' page if the payment process is blocked for some reason
             */
            Route::get('{parent}/locked', [PaymentController::class, 'locked'])
                ->name('locked');

            Route::get('{parent}/surveys/{survey}/lease', [SurveyController::class, 'lease'])
                ->middleware([RedirectToActivePayment::class, RedirectIfNewPaymentProhibited::class])
                ->name('surveys.lease');

            Route::get('{parent}/surveys/{survey}/finance', [SurveyController::class, 'finance'])
                ->middleware([RedirectToActivePayment::class, RedirectIfNewPaymentProhibited::class])
                ->name('surveys.finance');

            Route::resource('{parent}/surveys', SurveyController::class)
                ->middlewareFor(['create', 'store', 'edit', 'update'], [RedirectToActivePayment::class, RedirectIfNewPaymentProhibited::class])
                ->names('surveys');

            Route::get('{parent}/options', [PaymentOptionsController::class, 'options'])
                ->middleware([RedirectToActivePayment::class, RedirectToSelectedOffer::class, RedirectIfNewPaymentProhibited::class])
                ->name('options');

            Route::post('{parent}/change-desposit/{paymentType}', [PaymentOptionsController::class, 'changeDeposit'])
                ->name('change-deposit');

            Route::post('{parent}/select', [PaymentOptionsController::class, 'select'])
                ->middleware(RedirectToActivePayment::class)
                ->name('select');

            Route::post('{parent}/unselect', [PaymentOptionsController::class, 'unselect'])
                ->middleware(RedirectToActivePayment::class)
                ->name('unselect');

            Route::post('{parent}/cancel/{payment}', [PaymentController::class, 'cancel'])
                ->name('cancel');

            Route::post('{parent}/prequal', PrequalController::class)
                ->name('prequal');

            Route::resource('{parent}/cash', CashController::class)
                ->middlewareFor(['create'], RedirectToActivePayment::class)
                ->names('cash');

            Route::resource('{parent}/finance', FinanceController::class)
                ->middlewareFor(['create', 'store'], [RedirectToActivePayment::class, RedirectIfNewPaymentProhibited::class])
                ->names('finance');

            Route::resource('{parent}/lease', LeaseController::class)
                ->middlewareFor(['create', 'store'], [RedirectToActivePayment::class, RedirectIfNewPaymentProhibited::class])
                ->names('lease');

            Route::get('address/lookup/{postCode}', [AddressLookupController::class, 'lookup'])
                ->name('address.lookup');

            Route::get('finance/{payment}/signing-link', [FinanceSigningLinkController::class, 'show'])
                ->name('finance.signing-link');
    });
});
